<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica all'amministratore: prenotazione cancellata.
 */
class AdminBookingCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Prenotazione annullata · #' . $this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.admin.booking-cancelled',
            with: [
                'booking' => $this->booking,
                'reason' => $this->reason ?: $this->booking->cancellation_reason,
            ],
        );
    }
}
