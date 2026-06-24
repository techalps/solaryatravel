<?php

namespace App\Http\Controllers;

use App\Mail\BookingTickets;
use App\Models\Booking;
use App\Models\Payment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
use App\Support\BookingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
        Stripe::setApiKey(config('payment.stripe.secret_key'));
    }

    /**
     * Show the payment page for a booking.
     */
    public function show(Booking $booking): View|RedirectResponse
    {
        $booking->loadMissing(['tour', 'departure', 'addons']);

        // Check if booking is still payable
        if (!$booking->isPending()) {
            return redirect()
                ->route('booking.show', $booking->uuid)
                ->with('info', 'Questa prenotazione è già stata pagata o non è più valida.');
        }

        return view('payments.show', compact('booking'));
    }

    /**
     * Create a Stripe Checkout session.
     */
    public function process(Booking $booking): RedirectResponse
    {
        if (!$booking->isPending()) {
            return redirect()
                ->route('booking.show', $booking->uuid)
                ->with('error', 'Questa prenotazione non può essere pagata.');
        }

        try {
            // Se è una prenotazione con acconto, al primo pagamento si versa l'acconto.
            $intent = ($booking->payment_type === 'deposit' && (float) $booking->deposit_amount > 0)
                ? 'deposit'
                : 'full';

            $session = $this->paymentService->createCheckoutSession($booking, $intent);
            $booking->update(['checkout_url' => $session['url']]);
            BookingLog::info('payment_checkout', 'Sessione Stripe creata', $booking, [
                'intent' => $intent,
                'session_id' => $session['session_id'] ?? null,
            ]);
            return redirect($session['url']);
        } catch (\Exception $e) {
            report($e);
            BookingLog::failure('payment_checkout', 'Creazione sessione Stripe fallita', $booking, $e);
            return redirect()
                ->back()
                ->with('error', 'Si è verificato un errore durante la creazione del pagamento. Riprova.');
        }
    }

    /**
     * Handle successful payment return.
     */
    public function success(Request $request, Booking $booking): View|RedirectResponse
    {
        $sessionId = $request->get('session_id');

        if ($sessionId) {
            try {
                $session = Session::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $payment = Payment::where('gateway_payment_id', $sessionId)->first();
                    if ($payment) {
                        $this->applySuccessfulPayment($payment, $session->payment_intent);
                    }

                    return view('payments.success', compact('booking'));
                }
            } catch (\Exception $e) {
                report($e);
                BookingLog::failure('payment_return', 'Verifica pagamento al ritorno fallita', $booking, $e, [
                    'session_id' => $sessionId,
                ]);
            }
        }

        return redirect()
            ->route('booking.show', $booking->uuid)
            ->with('info', 'Stiamo verificando il tuo pagamento. Riceverai una conferma via email.');
    }

    /**
     * Handle cancelled payment.
     */
    public function cancel(Booking $booking): View
    {
        return view('payments.cancel', compact('booking'));
    }

    /**
     * Invia (idempotente) l'email con i biglietti QR al cliente.
     */
    protected function sendTicketsEmail(Booking $booking): void
    {
        $booking->refresh();
        if ($booking->tickets_sent_at) {
            return;
        }
        try {
            Mail::to($booking->customer_email)->send(new BookingTickets($booking));
            $booking->update(['tickets_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Invio biglietti fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }
        // Nota: la notifica admin di nuova prenotazione è gestita centralmente
        // da BookingObserver quando lo status passa a CONFIRMED (qualunque origine).
    }

    /**
     * Applica un pagamento Stripe riuscito alla prenotazione, gestendo i tre
     * intenti (full/deposit/balance): aggiorna il Payment, l'importo incassato
     * (amount_paid) e lo stato del booking. È idempotente (controlla lo stato
     * del Payment) e centralizza la logica usata da success() e dai webhook.
     */
    protected function applySuccessfulPayment(Payment $payment, ?string $paymentIntentId = null): void
    {
        if ($payment->status === PaymentStatus::SUCCEEDED) {
            return; // già elaborato (idempotenza: webhook + ritorno possono arrivare entrambi)
        }

        $payment->update([
            'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => now(),
            'gateway_payment_intent' => $paymentIntentId ?? $payment->gateway_payment_intent,
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                $paymentIntentId ? ['payment_intent' => $paymentIntentId] : []
            ),
        ]);

        $booking = $payment->booking;
        $intent = $payment->gateway_response['intent'] ?? 'full';

        // Aggiorna l'importo effettivamente incassato.
        $booking->update(['amount_paid' => (float) $booking->amount_paid + (float) $payment->amount]);

        BookingLog::info('payment_succeeded', 'Pagamento Stripe registrato', $booking, [
            'intent' => $intent,
            'amount' => (float) $payment->amount,
            'amount_paid_tot' => (float) $booking->amount_paid,
            'payment_intent' => $paymentIntentId,
        ]);

        if ($intent === 'deposit' && (float) $booking->balance_amount > 0) {
            // Acconto versato: confermato con saldo in sospeso.
            if ($booking->status !== BookingStatus::DEPOSIT_PAID) {
                $booking->update([
                    'status' => BookingStatus::DEPOSIT_PAID,
                    'confirmed_at' => $booking->confirmed_at ?? now(),
                ]);
            }
            // Biglietti emessi solo a saldo completo: qui niente invio.
            return;
        }

        if ($intent === 'balance') {
            // Saldo pagato: azzera il residuo e conferma definitivamente.
            $booking->update(['balance_amount' => 0]);
        }

        if ($booking->status !== BookingStatus::CONFIRMED) {
            $booking->update([
                'status' => BookingStatus::CONFIRMED,
                'confirmed_at' => $booking->confirmed_at ?? now(),
            ]);
        }

        $this->sendTicketsEmail($booking);
    }

    /**
     * Handle Stripe webhooks.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('payment.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            BookingLog::failure('payment_webhook', 'Verifica firma webhook Stripe fallita', null, $e);
            return response()->json(['error' => 'Webhook verification failed'], 400);
        }

        BookingLog::info('payment_webhook', 'Webhook Stripe ricevuto', null, [
            'event_type' => $event->type,
            'event_id' => $event->id ?? null,
        ]);

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutCompleted($session);
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailed($paymentIntent);
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                $this->handleRefund($charge);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle checkout session completed.
     */
    protected function handleCheckoutCompleted($session): void
    {
        $payment = Payment::where('gateway_payment_id', $session->id)->first();

        if ($payment && $session->payment_status === 'paid') {
            $this->applySuccessfulPayment($payment, $session->payment_intent);
        } elseif (! $payment) {
            BookingLog::warning('payment_webhook', 'checkout.completed: nessun Payment per la sessione', null, [
                'session_id' => $session->id,
            ]);
        }
    }

    /**
     * Handle successful payment intent.
     */
    protected function handlePaymentSucceeded($paymentIntent): void
    {
        $payment = Payment::where('gateway_payment_intent', $paymentIntent->id)->first();

        if ($payment) {
            $this->applySuccessfulPayment($payment, $paymentIntent->id);
        }
    }

    /**
     * Handle failed payment.
     */
    protected function handlePaymentFailed($paymentIntent): void
    {
        $payment = Payment::where('gateway_payment_intent', $paymentIntent->id)->first();

        if ($payment) {
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'failure_message' => $paymentIntent->last_payment_error?->message,
            ]);
            BookingLog::warning('payment_failed', 'Pagamento Stripe fallito', $payment->booking, [
                'payment_intent' => $paymentIntent->id,
                'reason' => $paymentIntent->last_payment_error?->message,
            ]);
        }
    }

    /**
     * Handle refund.
     */
    protected function handleRefund($charge): void
    {
        $payment = Payment::where('gateway_payment_intent', $charge->payment_intent)->first();

        if ($payment) {
            $refundedAmount = $charge->amount_refunded / 100;

            if ($charge->refunded) {
                $payment->update([
                    'status' => PaymentStatus::REFUNDED,
                    'refunded_amount' => $refundedAmount,
                    'refunded_at' => now(),
                ]);
            } else {
                $payment->update([
                    'status' => PaymentStatus::PARTIALLY_REFUNDED,
                    'refunded_amount' => $refundedAmount,
                ]);
            }
            BookingLog::info('payment_refund', 'Rimborso Stripe ricevuto (webhook)', $payment->booking, [
                'refunded_amount' => $refundedAmount,
                'fully_refunded' => (bool) $charge->refunded,
            ]);
        }
    }
}
