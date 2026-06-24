<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Mail\AdminNewBooking;
use App\Mail\BookingTickets;
use App\Models\Booking;
use App\Support\BookingLog;
use App\Support\Settings;
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
            BookingLog::info('email_send', 'Biglietti inviati al cliente', $booking, [
                'to' => $booking->customer_email,
            ]);
        } catch (\Throwable $e) {
            BookingLog::failure('email_send', 'Invio biglietti fallito (observer)', $booking, $e, [
                'to' => $booking->customer_email,
            ]);
        }

        // Notifica all'amministratore: nuova prenotazione confermata.
        try {
            \App\Support\AdminMailer::send(new AdminNewBooking($booking));
            BookingLog::info('email_send', 'Notifica admin nuova prenotazione inviata', $booking);
        } catch (\Throwable $e) {
            BookingLog::failure('email_send', 'Notifica admin nuova prenotazione fallita', $booking, $e);
        }
    }
}
