<?php

namespace App\Mail;

use App\Models\Booking;
use App\Mail\Concerns\SendsInBookingLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaymentLink extends Mailable
{
    use Queueable, SerializesModels;
    use SendsInBookingLocale;

    /**
     * @param  string|null  $checkoutUrl  link Stripe diretto. Normalmente NON va
     *   passato: l'email punta alla nostra pagina di pagamento, che genera una
     *   sessione fresca a ogni apertura e quindi non scade mai. Una sessione
     *   Stripe dura al massimo 24 ore, quindi un link diretto messo in una email
     *   può arrivare (o essere aperto) quando è già morto.
     */
    public function __construct(
        public Booking $booking,
        public ?string $checkoutUrl = null
    )
    {
        $this->useBookingLocale($booking->locale);
    }

    /**
     * URL su cui puntare il pulsante dell'email: una pagina NOSTRA, permanente
     * e sempre valida, che crea la sessione Stripe al click.
     *
     * Se la prenotazione ha già l'acconto versato, il link deve portare alla
     * pagina del saldo: /pagamento/{uuid} accetta solo le prenotazioni in stato
     * "pending" e rimanderebbe altrove senza far pagare nulla.
     */
    public function payUrl(): string
    {
        return $this->booking->hasBalanceDue()
            ? route('booking.balance', $this->booking->uuid)
            : route('payment.show', $this->booking->uuid);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.payment_link.subject', ['number' => $this->booking->booking_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.payment-link',
            with: [
                'booking' => $this->booking,
                // Il template usa payUrl (pagina nostra, non scade). Manteniamo
                // checkoutUrl per compatibilità con eventuali usi esistenti.
                'payUrl' => $this->payUrl(),
                'checkoutUrl' => $this->checkoutUrl,
            ],
        );
    }
}
