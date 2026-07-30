<?php

namespace App\Mail;

use App\Models\Booking;
use App\Mail\Concerns\SendsInBookingLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminder24h extends Mailable
{
    use Queueable, SerializesModels;
    use SendsInBookingLocale;

    public function __construct(public Booking $booking)
    {
        $this->useBookingLocale($booking->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.reminder_24h.subject', ['number' => $this->booking->booking_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.reminder-24h',
            with: [
                'booking' => $this->booking,
            ],
        );
    }
}
