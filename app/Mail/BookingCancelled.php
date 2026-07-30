<?php

namespace App\Mail;

use App\Models\Booking;
use App\Mail\Concerns\SendsInBookingLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelled extends Mailable
{
    use Queueable, SerializesModels;
    use SendsInBookingLocale;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null,
        public ?array $refundCalc = null,
    )
    {
        $this->useBookingLocale($booking->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.cancelled.subject', ['number' => $this->booking->booking_number]),
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.bookings.cancelled',
            with: [
                'booking' => $this->booking,
                'reason' => $this->reason ?: $this->booking->cancellation_reason,
                'refundCalc' => $this->refundCalc,
            ],
        );
    }
}
