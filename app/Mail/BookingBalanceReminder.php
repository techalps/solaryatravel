<?php

namespace App\Mail;

use App\Models\Booking;
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

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Salda la tua prenotazione #' . $this->booking->booking_number,
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
