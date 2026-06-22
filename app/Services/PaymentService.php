<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Support\Settings;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Refund;
use Stripe\PaymentIntent;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('payment.stripe.secret_key'));
    }

    /**
     * Create a Stripe checkout session for a booking.
     */
    /**
     * Crea una sessione di checkout Stripe.
     *
     * @param  string  $intent  'full' (intero) | 'deposit' (acconto) | 'balance' (saldo)
     */
    public function createCheckoutSession(Booking $booking, string $intent = 'full'): array
    {
        // Determina importo, line item e label in base all'intento.
        [$amount, $lineItems] = $this->checkoutAmountAndItems($booking, $intent);

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('payment.success', $booking->uuid) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel', $booking->uuid),
            'customer_email' => $booking->customer_email,
            'metadata' => [
                'booking_number' => $booking->booking_number,
                'booking_id' => $booking->id,
                'intent' => $intent,
            ],
            'expires_at' => now()->addMinutes(30)->timestamp,
            'locale' => $booking->locale ?? 'it',
        ]);

        // Create payment record (importo = quanto richiesto in questo step)
        Payment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'currency' => 'EUR',
            'gateway' => 'stripe',
            'gateway_payment_id' => $session->id,
            'payment_method_type' => 'card',
            'status' => PaymentStatus::PENDING,
            'gateway_response' => ['session_id' => $session->id, 'intent' => $intent],
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
    }

    /**
     * Importo e line items per l'intento di pagamento.
     *
     * @return array{0: float, 1: array}
     */
    protected function checkoutAmountAndItems(Booking $booking, string $intent): array
    {
        $tourName = $booking->tour?->name ?? 'Tour';

        if ($intent === 'deposit') {
            $amount = (float) ($booking->deposit_amount ?: 0);
            return [$amount, [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => "Acconto · {$tourName}", 'description' => '#' . $booking->booking_number],
                    'unit_amount' => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]]];
        }

        if ($intent === 'balance') {
            $amount = (float) ($booking->balance_amount ?: 0);
            return [$amount, [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => "Saldo · {$tourName}", 'description' => '#' . $booking->booking_number],
                    'unit_amount' => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]]];
        }

        // full: importo intero, line items dettagliati (tour + addon)
        return [(float) $booking->total_amount, $this->buildLineItems($booking)];
    }

    /**
     * Build line items for Stripe checkout.
     */
    protected function buildLineItems(Booking $booking): array
    {
        $booking->loadMissing(['tour', 'departure', 'addons.addon']);
        $items = [];

        $tourName = $booking->tour?->name ?? 'Tour';
        $date = $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') : '';
        $time = $booking->departure?->start_time
            ? \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i')
            : '';
        $description = trim(sprintf('%s · %s · %d posti', $date, $time, (int) $booking->seats), ' ·');

        $totalForMain = (float) $booking->total_amount;
        // Sottrai eventuali addons riportati come line item separato
        $addonsTotal = 0;
        foreach ($booking->addons as $bookingAddon) {
            $addonsTotal += (float) ($bookingAddon->total_price ?? 0);
        }
        $mainAmount = max(0, $totalForMain - $addonsTotal);

        $items[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $tourName,
                    'description' => $description ?: null,
                ],
                'unit_amount' => (int) round($mainAmount * 100),
            ],
            'quantity' => 1,
        ];

        foreach ($booking->addons as $bookingAddon) {
            $name = $bookingAddon->addon->name ?? ($bookingAddon->name ?? 'Servizio aggiuntivo');
            $unit = (float) ($bookingAddon->total_price ?? 0);
            if ($unit <= 0) {
                continue;
            }
            $items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => $name],
                    'unit_amount' => (int) round($unit * 100),
                ],
                'quantity' => 1,
            ];
        }

        return $items;
    }

    /**
     * Verify a checkout session and complete payment.
     */
    public function verifyCheckoutSession(string $sessionId): array
    {
        try {
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return [
                    'success' => false,
                    'message' => 'Il pagamento non è stato completato.',
                ];
            }

            $payment = Payment::where('gateway_payment_id', $sessionId)->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Pagamento non trovato.',
                ];
            }

            // Update payment record
            $payment->update([
                'status' => PaymentStatus::SUCCEEDED,
                'paid_at' => now(),
                'gateway_payment_intent' => $session->payment_intent,
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    ['payment_intent' => $session->payment_intent]
                ),
            ]);

            // Update booking status
            $payment->booking->update(['status' => BookingStatus::CONFIRMED]);

            return [
                'success' => true,
                'payment' => $payment,
                'booking' => $payment->booking,
            ];
        } catch (\Exception $e) {
            report($e);
            return [
                'success' => false,
                'message' => 'Errore durante la verifica del pagamento.',
            ];
        }
    }

    /**
     * Process a refund for a booking.
     */
    public function refund(Booking $booking, ?float $amount = null): array
    {
        $payment = $booking->payments()
            ->where('status', PaymentStatus::SUCCEEDED)
            ->latest('paid_at')
            ->first();

        if (!$payment) {
            return [
                'success' => false,
                'message' => 'Nessun pagamento da rimborsare.',
            ];
        }

        try {
            $paymentIntent = $payment->gateway_payment_intent
                ?? ($payment->gateway_response['payment_intent'] ?? null);

            if (!$paymentIntent) {
                return [
                    'success' => false,
                    'message' => 'ID pagamento non trovato.',
                ];
            }

            $refundParams = [
                'payment_intent' => $paymentIntent,
            ];

            // If partial refund
            if ($amount && $amount < $payment->amount) {
                $refundParams['amount'] = (int) ($amount * 100);
            }

            $refund = Refund::create($refundParams);

            $refundedAmount = $refund->amount / 100;
            $isFullRefund = $refundedAmount >= $payment->amount;

            $payment->update([
                'status' => $isFullRefund ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED,
                'refunded_amount' => $refundedAmount,
                'refunded_at' => now(),
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    ['refund_id' => $refund->id]
                ),
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refundedAmount,
                'status' => $refund->status,
            ];
        } catch (\Exception $e) {
            report($e);
            return [
                'success' => false,
                'message' => 'Errore durante il rimborso: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calcola l'importo rimborsabile in base alla politica di storno configurata
     * nelle Impostazioni (fasce in giorni). La percentuale si applica all'importo
     * EFFETTIVAMENTE VERSATO dal cliente (amount_paid), non al totale.
     *
     * Ritorna: refundable, percentage, amount (rimborso), penalty (trattenuto),
     * paid (versato), days_until (giorni alla partenza), reason.
     */
    public function calculateRefundAmount(Booking $booking): array
    {
        $paid = $this->amountPaid($booking);

        if ($paid <= 0) {
            return [
                'refundable' => false,
                'amount' => 0.0,
                'penalty' => 0.0,
                'paid' => 0.0,
                'percentage' => 0,
                'days_until' => null,
                'reason' => 'Nessun importo versato da rimborsare.',
            ];
        }

        // Giorni interi mancanti alla partenza (negativo se già passata).
        $daysUntil = (int) floor(now()->startOfDay()->diffInDays(
            \Illuminate\Support\Carbon::parse($booking->booking_date)->startOfDay(),
            false
        ));

        $policy = Settings::cancellationPolicy(); // fasce ordinate desc per giorni
        $refundPercentage = 0;
        foreach ($policy as $tier) {
            if ($daysUntil >= $tier['days']) {
                $refundPercentage = (int) $tier['refund'];
                break;
            }
        }

        $refundAmount = round($paid * $refundPercentage / 100, 2);
        $penalty = round($paid - $refundAmount, 2);

        return [
            'refundable' => $refundPercentage > 0,
            'amount' => $refundAmount,
            'penalty' => $penalty,
            'paid' => round($paid, 2),
            'percentage' => $refundPercentage,
            'days_until' => $daysUntil,
            'reason' => "Cancellazione a {$daysUntil} giorni dalla partenza · rimborso {$refundPercentage}%",
        ];
    }

    /**
     * Percentuale di rimborso prevista dalla policy in base ai giorni alla partenza.
     */
    public function refundPercentageFor(Booking $booking): int
    {
        $daysUntil = (int) floor(now()->startOfDay()->diffInDays(
            \Illuminate\Support\Carbon::parse($booking->booking_date)->startOfDay(),
            false
        ));
        foreach (Settings::cancellationPolicy() as $tier) {
            if ($daysUntil >= $tier['days']) {
                return (int) $tier['refund'];
            }
        }
        return 0;
    }

    /**
     * Applica la policy di penale a un importo ARBITRARIO (es. somma dei
     * partecipanti/extra rimossi da una modifica), senza considerare l'incassato.
     *
     * @return array{base:float, amount:float, penalty:float, percentage:int, days_until:int}
     */
    public function refundForRemovedAmount(Booking $booking, float $base): array
    {
        $base = round(max(0, $base), 2);
        $daysUntil = (int) floor(now()->startOfDay()->diffInDays(
            \Illuminate\Support\Carbon::parse($booking->booking_date)->startOfDay(),
            false
        ));
        $pct = $this->refundPercentageFor($booking);
        $amount = round($base * $pct / 100, 2);

        return [
            'base' => $base,
            'amount' => $amount,
            'penalty' => round($base - $amount, 2),
            'percentage' => $pct,
            'days_until' => $daysUntil,
        ];
    }

    /**
     * Importo effettivamente incassato per la prenotazione.
     * Usa booking.amount_paid se valorizzato, altrimenti somma i pagamenti riusciti
     * al netto di quanto già rimborsato.
     */
    public function amountPaid(Booking $booking): float
    {
        $tracked = (float) ($booking->amount_paid ?? 0);
        if ($tracked > 0) {
            return $tracked;
        }

        return (float) $booking->payments()
            ->whereIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->get()
            ->sum(fn ($p) => (float) $p->amount - (float) $p->refunded_amount);
    }

    /**
     * Metodo di pagamento "prevalente" della prenotazione: il gateway dell'ultimo
     * pagamento riuscito; fallback su booking.payment_type, poi 'manual'.
     * Valori: 'stripe' | 'bank_transfer' | 'manual'.
     */
    public function primaryPaymentMethod(Booking $booking): string
    {
        $last = $booking->payments()
            ->whereIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->latest('paid_at')
            ->first();

        if ($last && $last->gateway) {
            return $last->gateway === 'stripe' ? 'stripe'
                : ($last->gateway === 'bank_transfer' ? 'bank_transfer' : 'manual');
        }

        return match ($booking->payment_type) {
            'bank_transfer' => 'bank_transfer',
            default => 'manual',
        };
    }

    /**
     * Esegue un rimborso di un dato importo per una prenotazione e registra la
     * penale trattenuta sul booking. Se il pagamento è via carta (Stripe) esegue
     * il refund reale; se è bonifico, registra solo lo stato (l'accredito è manuale).
     *
     * @return array{executed:bool, manual:bool, amount:float, penalty:float, message?:string}
     */
    public function applyCancellationRefund(Booking $booking, float $refundAmount, ?string $note = null): array
    {
        $paid = $this->amountPaid($booking);
        $refundAmount = max(0, min($refundAmount, $paid));
        $penalty = round(max(0, $paid - $refundAmount), 2);

        // Registra la penale trattenuta (audit) a prescindere dall'esito.
        $booking->update(['penalty_amount' => $penalty]);

        if ($refundAmount <= 0) {
            return ['executed' => false, 'manual' => false, 'amount' => 0.0, 'penalty' => $penalty,
                    'message' => 'Nessun importo da rimborsare (penale 100%).'];
        }

        // Bonifico (o nessun pagamento Stripe): rimborso manuale.
        $stripePayment = $booking->payments()
            ->where('gateway', 'stripe')
            ->whereIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->latest('paid_at')
            ->first();

        if (! $stripePayment) {
            // Marca i pagamenti non-Stripe come (parzialmente) rimborsati per traccia.
            foreach ($booking->payments()->whereIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::PARTIALLY_REFUNDED])->get() as $p) {
                $isFull = $refundAmount >= (float) $p->amount - 0.005;
                $p->update([
                    'status' => $isFull ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED,
                    'refunded_amount' => round(min($refundAmount, (float) $p->amount), 2),
                    'refunded_at' => now(),
                ]);
            }
            return ['executed' => true, 'manual' => true, 'amount' => $refundAmount, 'penalty' => $penalty,
                    'message' => 'Rimborso da effettuare manualmente (bonifico).'];
        }

        // Carta: refund reale su Stripe.
        $result = $this->refund($booking, $refundAmount);
        return [
            'executed' => (bool) ($result['success'] ?? false),
            'manual' => false,
            'amount' => $refundAmount,
            'penalty' => $penalty,
            'message' => $result['message'] ?? null,
        ];
    }

    /**
     * Get payment status from Stripe.
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
