<?php

namespace App\Http\Controllers;

use App\Mail\BookingTickets;
use App\Models\Booking;
use App\Models\Payment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
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
            $session = $this->paymentService->createCheckoutSession($booking);
            $booking->update(['checkout_url' => $session['url']]);
            return redirect($session['url']);
        } catch (\Exception $e) {
            report($e);
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
                    if ($payment && $payment->status !== PaymentStatus::SUCCEEDED) {
                        $payment->update([
                            'status' => PaymentStatus::SUCCEEDED,
                            'paid_at' => now(),
                            'gateway_payment_intent' => $session->payment_intent,
                            'gateway_response' => array_merge(
                                $payment->gateway_response ?? [],
                                ['payment_intent' => $session->payment_intent]
                            ),
                        ]);
                    }

                    if ($booking->status !== BookingStatus::CONFIRMED) {
                        $booking->update([
                            'status' => BookingStatus::CONFIRMED,
                            'confirmed_at' => now(),
                        ]);
                    }

                    $this->sendTicketsEmail($booking);

                    return view('payments.success', compact('booking'));
                }
            } catch (\Exception $e) {
                report($e);
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
            return response()->json(['error' => 'Webhook verification failed'], 400);
        }

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
            $payment->update([
                'status' => PaymentStatus::SUCCEEDED,
                'paid_at' => now(),
                'gateway_payment_intent' => $session->payment_intent,
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    ['payment_intent' => $session->payment_intent]
                ),
            ]);

            $booking = $payment->booking;
            if ($booking->status !== BookingStatus::CONFIRMED) {
                $booking->update([
                    'status' => BookingStatus::CONFIRMED,
                    'confirmed_at' => now(),
                ]);
            }
            $this->sendTicketsEmail($booking);
        }
    }

    /**
     * Handle successful payment intent.
     */
    protected function handlePaymentSucceeded($paymentIntent): void
    {
        $payment = Payment::where('gateway_payment_intent', $paymentIntent->id)->first();

        if ($payment && $payment->status !== PaymentStatus::SUCCEEDED) {
            $payment->update([
                'status' => PaymentStatus::SUCCEEDED,
                'paid_at' => now(),
            ]);

            $booking = $payment->booking;
            if ($booking->status !== BookingStatus::CONFIRMED) {
                $booking->update([
                    'status' => BookingStatus::CONFIRMED,
                    'confirmed_at' => now(),
                ]);
            }
            $this->sendTicketsEmail($booking);
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
        }
    }
}
