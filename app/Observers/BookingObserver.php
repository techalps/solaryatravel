<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Mail\BookingTickets;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingObserver
{
    /**
     * Quando una prenotazione passa allo stato CONFIRMED, spedisci i biglietti.
     * Idempotente grazie al campo tickets_sent_at sul Booking.
     */
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return;
        }
        if ($booking->tickets_sent_at) {
            return;
        }

        try {
            Mail::to($booking->customer_email)->send(new BookingTickets($booking));
            $booking->updateQuietly(['tickets_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Invio biglietti fallito (observer)', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
