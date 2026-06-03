<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica all'amministratore: rimborso effettuato o pagamento fallito.
 */
class AdminBookingRefunded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?float $amount = null,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💸 Rimborso · #' . $this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.admin.booking-refunded',
            with: [
                'booking' => $this->booking,
                'amount' => $this->amount,
                'note' => $this->note,
            ],
        );
    }
}
