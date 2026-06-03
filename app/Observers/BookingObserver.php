<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Mail\AdminNewBooking;
use App\Mail\BookingTickets;
use App\Models\Booking;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingObserver
{
    /**
     * Quando una prenotazione passa allo stato CONFIRMED:
     *  - spedisce i biglietti al cliente (idempotente via tickets_sent_at);
     *  - notifica l'amministratore della nuova prenotazione.
     * È il punto centrale: scatta da qualsiasi origine (pagamento online,
     * conferma manuale admin, ecc.).
     */
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return;
        }
        // Già notificato in precedenza: evita duplicati.
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

        // Notifica all'amministratore: nuova prenotazione confermata.
        try {
            Mail::to(Settings::adminNotificationEmail())->send(new AdminNewBooking($booking));
        } catch (\Throwable $e) {
            Log::error('Notifica admin nuova prenotazione fallita', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
