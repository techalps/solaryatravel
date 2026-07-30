<?php

namespace App\Mail;

use App\Models\Booking;
use App\Mail\Concerns\SendsInBookingLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reminder al cliente per il pagamento del saldo (prenotazioni con acconto).
 */
class BookingBalanceReminder extends Mailable
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
            subject: __('emails.balance_reminder.subject', ['number' => $this->booking->booking_number]),
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.bookings.balance-reminder',
            with: ['booking' => $this->booking],
        );
    }
}
