<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica all'admin che un'agenzia ha richiesto l'annullamento o la modifica di
 * una prenotazione. La richiesta NON è effettiva: l'admin la valuta e la
 * conferma/rifiuta applicando l'eventuale penale.
 */
class AdminB2bRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $type,           // cancellation | modification
        public ?string $reason = null,
        public ?User $agency = null,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->type === 'cancellation' ? 'annullamento' : 'modifica';

        return new Envelope(
            subject: '🔔 Richiesta '.$label.' (agenzia) · #'.$this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.admin.b2b-request',
            with: [
                'booking' => $this->booking,
                'type' => $this->type,
                'reason' => $this->reason,
                'agency' => $this->agency,
            ],
        );
    }
}
