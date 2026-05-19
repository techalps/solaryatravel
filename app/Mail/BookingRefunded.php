<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRefunded extends Mailable
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
            subject: 'Rimborso effettuato · Prenotazione #' . $this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.bookings.refunded',
            with: [
                'booking' => $this->booking,
                'amount' => $this->amount ?? (float) $this->booking->total_amount,
                'note' => $this->note,
            ],
        );
    }
}
