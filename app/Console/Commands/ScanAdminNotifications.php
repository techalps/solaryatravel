<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\AdminNotification;
use App\Models\Booking;
use App\Services\AdminNotificationService;
use App\Support\BookingLog;
use Illuminate\Console\Command;

/**
 * Notifiche che non nascono da un evento ma da una CONDIZIONE nel tempo:
 * prenotazioni che stanno per scadere, saldi scaduti, documenti mancanti
 * a partenza vicina.
 *
 * Gli eventi immediati (nuova prenotazione, incasso, annullamento) li genera
 * l'observer: quelli si sanno nell'istante in cui accadono.
 *
 * Idempotente: prima di creare controlla che non esista già una notifica dello
 * stesso tipo per quella prenotazione. Il cron su OVH gira ogni ora, e senza
 * questo controllo l'admin troverebbe la stessa segnalazione ripetuta.
 */
class ScanAdminNotifications extends Command
{
    protected $signature = 'admin:scan-notifications';

    protected $description = 'Genera le notifiche admin basate su scadenze e condizioni (non pagate, saldi, documenti)';

    public function __construct(private AdminNotificationService $notifiche)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $creati = 0;
        $creati += $this->prenotazioniInScadenza();
        $creati += $this->saldiScaduti();
        $creati += $this->documentiMancanti();

        BookingLog::info('admin_notifications_scan', 'Scansione notifiche admin completata', null, [
            'created' => $creati,
        ]);

        $this->info("Notifiche create: {$creati}");

        return self::SUCCESS;
    }

    /** Non pagate con la scadenza entro le prossime 6 ore. */
    private function prenotazioniInScadenza(): int
    {
        $n = 0;

        Booking::query()
            ->where('status', BookingStatus::PENDING)
            ->whereNotNull('payment_deadline')
            ->whereBetween('payment_deadline', [now(), now()->addHours(6)])
            ->with('tour')
            ->each(function (Booking $b) use (&$n) {
                if ($this->giaNotificata('payment_expiring', $b)) {
                    return;
                }

                $this->notifiche->notify(
                    'payment_expiring',
                    'Prenotazione in scadenza #'.$b->booking_number,
                    $b->customer_full_name.' · scade '.$b->payment_deadline->format('d/m/Y H:i'),
                    $b,
                );
                $n++;
            });

        return $n;
    }

    /** Acconto versato ma saldo oltre il termine. */
    private function saldiScaduti(): int
    {
        $n = 0;

        Booking::query()
            ->whereIn('status', [BookingStatus::DEPOSIT_PAID, BookingStatus::CONFIRMED])
            ->whereNotNull('balance_due_at')
            ->where('balance_due_at', '<', now())
            ->whereColumn('amount_paid', '<', 'total_amount')
            ->each(function (Booking $b) use (&$n) {
                if ($this->giaNotificata('balance_overdue', $b)) {
                    return;
                }

                $residuo = (float) $b->total_amount - (float) $b->amount_paid;

                $this->notifiche->notify(
                    'balance_overdue',
                    'Saldo scaduto #'.$b->booking_number,
                    $b->customer_full_name.' · residuo € '.number_format($residuo, 2, ',', '.'),
                    $b,
                );
                $n++;
            });

        return $n;
    }

    /**
     * Partenza entro 3 giorni con passeggeri senza documento completo.
     *
     * Il documento è un obbligo contrattuale: va segnalato con margine per
     * poterlo chiedere al cliente prima dell'imbarco.
     */
    private function documentiMancanti(): int
    {
        $n = 0;

        Booking::query()
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::DEPOSIT_PAID])
            ->whereNotNull('booking_date')
            ->whereBetween('booking_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->with('seatRecords')
            ->each(function (Booking $b) use (&$n) {
                if (! $b->hasMissingDocuments() || $this->giaNotificata('missing_documents', $b)) {
                    return;
                }

                $this->notifiche->notify(
                    'missing_documents',
                    'Documenti mancanti #'.$b->booking_number,
                    $b->customer_full_name.' · partenza '.$b->booking_date->format('d/m/Y'),
                    $b,
                );
                $n++;
            });

        return $n;
    }

    /** Una notifica di quel tipo per quella prenotazione esiste già? */
    private function giaNotificata(string $type, Booking $booking): bool
    {
        return AdminNotification::where('type', $type)
            ->where('booking_id', $booking->getKey())
            ->exists();
    }
}
