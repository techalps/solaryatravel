<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Mail\AdminNewBooking;
use App\Mail\BookingTickets;
use App\Models\Booking;
use App\Support\BookingLog;
use App\Services\AdminNotificationService;
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

        // Canale B2B: se la prenotazione esce dagli stati "validi" (annullata,
        // rimborsata, no-show), la commissione va stornata. La penale è ricavo di
        // Solarya, non base provvigionale. Punto unico: copre annullamenti da
        // admin, da cliente e da qualsiasi altro flusso. Idempotente.
        if ($booking->b2b_user_id
            && in_array($booking->status, [BookingStatus::CANCELLED, BookingStatus::REFUNDED, BookingStatus::NO_SHOW], true)) {
            app(\App\Services\CommissionService::class)
                ->reverse($booking, 'Prenotazione '.$booking->status->value);
        }

        // Notifiche in campanella: PRIMA dei return che seguono, altrimenti
        // annullamenti e rimborsi (che non sono CONFIRMED) non arriverebbero
        // mai all'operatore.
        $precedente = $booking->getOriginal('status');
        $this->notificheDiStato($booking, $precedente instanceof BookingStatus
            ? $precedente
            : BookingStatus::from((string) $precedente));

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

    /**
     * Nuova prenotazione: notifica in campanella per gli admin.
     *
     * Qui e non in `updated`: una prenotazione dal sito nasce PENDING e viene
     * confermata solo dopo il pagamento, ma l'admin vuole saperlo subito.
     * `caused_by` è chi era autenticato: se l'ha creata un admin, a lui il
     * toast non compare (l'ha fatta lui).
     */
    public function created(Booking $booking): void
    {
        $this->notifiche()->notify(
            type: 'booking_created',
            title: 'Nuova prenotazione #'.$booking->booking_number,
            body: $this->riepilogo($booking),
            booking: $booking,
            causedBy: auth()->id(),
            data: ['source' => $booking->attribution_source],
        );
    }

    /**
     * Notifiche legate ai passaggi di stato: incassi, annullamenti, rimborsi,
     * bonifici da verificare.
     *
     * Separato dall'invio email di sopra perché quello riguarda il CLIENTE,
     * questo l'operatore: gli eventi rilevanti sono diversi.
     */
    private function notificheDiStato(Booking $booking, BookingStatus $precedente): void
    {
        $riepilogo = $this->riepilogo($booking);
        $numero = '#'.$booking->booking_number;
        $autore = auth()->id();
        $n = $this->notifiche();

        match (true) {
            // Confermata dopo un pagamento: l'incasso è arrivato.
            $booking->status === BookingStatus::CONFIRMED
                && in_array($precedente, [BookingStatus::PENDING, BookingStatus::AWAITING_TRANSFER, BookingStatus::DEPOSIT_PAID], true)
                => $n->notify('payment_received', 'Pagamento ricevuto '.$numero, $riepilogo, $booking, $autore),

            $booking->status === BookingStatus::AWAITING_TRANSFER
                => $n->notify('transfer_to_verify', 'Bonifico da verificare '.$numero, $riepilogo, $booking, $autore),

            $booking->status === BookingStatus::CANCELLED
                => $n->notify('booking_cancelled', 'Prenotazione annullata '.$numero, $riepilogo, $booking, $autore),

            $booking->status === BookingStatus::REFUNDED
                => $n->notify('booking_refunded', 'Rimborso eseguito '.$numero, $riepilogo, $booking, $autore),

            default => null,
        };
    }

    /** Riga di riepilogo usata nel corpo delle notifiche. */
    private function riepilogo(Booking $booking): string
    {
        $parti = [$booking->customer_full_name];

        if ($tour = $booking->tour?->name) {
            $parti[] = $tour;
        }
        if ($booking->booking_date) {
            $parti[] = $booking->booking_date->format('d/m/Y');
        }
        $parti[] = '€ '.number_format((float) $booking->total_amount, 2, ',', '.');

        return implode(' · ', $parti);
    }

    private function notifiche(): AdminNotificationService
    {
        return app(AdminNotificationService::class);
    }
}
