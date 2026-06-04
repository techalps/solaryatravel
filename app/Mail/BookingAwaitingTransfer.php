<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email al cliente con le istruzioni per il pagamento tramite bonifico bancario.
 */
class BookingAwaitingTransfer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public float $amountDue,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Istruzioni per il bonifico · Prenotazione #' . $this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure']);

        return new Content(
            view: 'emails.bookings.awaiting-transfer',
            with: [
                'booking' => $this->booking,
                'amountDue' => $this->amountDue,
                'bankDetails' => Settings::bankTransferDetails(),
            ],
        );
    }
}
