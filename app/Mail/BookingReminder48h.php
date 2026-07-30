<?php

namespace App\Mail;

use App\Models\Booking;
use App\Mail\Concerns\SendsInBookingLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminder48h extends Mailable
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
            subject: __('emails.reminder_48h.subject', ['number' => $this->booking->booking_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.reminder-48h',
            with: [
                'booking' => $this->booking,
            ],
        );
    }
}
