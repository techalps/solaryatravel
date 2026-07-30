<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\Concerns\SendsInBookingLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingTickets extends Mailable
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
            subject: __('emails.tickets.subject', ['number' => $this->booking->booking_number]),
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'departure', 'seatRecords.ageBracket', 'seatRecords.catamaran']);

        return new Content(
            view: 'emails.bookings.tickets',
            with: [
                'booking' => $this->booking,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $this->booking->loadMissing(['tour', 'departure', 'seatRecords.ageBracket', 'seatRecords.catamaran']);

        $qr = app(QrCodeService::class);
        $tickets = $this->booking->seatRecords->map(fn ($seat) => [
            'seat' => $seat,
            'qr_data' => $qr->pngDataUri($seat->qr_code, 320, 10),
        ]);

        $pdf = Pdf::loadView('pdf.tickets', [
            'booking' => $this->booking,
            'tickets' => $tickets,
        ])->setPaper('a4');

        $filename = 'biglietti-' . $this->booking->booking_number . '.pdf';

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
