<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Accesso in sola lettura alle impostazioni salvate in storage/app/settings.json.
 * Condivide la stessa cache ('app_settings') usata da SettingsController.
 */
class Settings
{
    public static function all(): array
    {
        return Cache::remember('app_settings', 3600, function () {
            $path = storage_path('app/settings.json');

            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true) ?: [];
            }

            return [];
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Modalità "coming soon" / manutenzione attiva.
     */
    public static function comingSoon(): bool
    {
        return (bool) self::get('maintenance_mode', false);
    }

    /**
     * Indirizzo email a cui inviare le notifiche operative all'amministratore.
     * Fallback: l'email del sito, poi l'indirizzo aziendale.
     */
    public static function adminNotificationEmail(): string
    {
        $email = trim((string) self::get('admin_notification_email', ''));

        if ($email === '') {
            $email = trim((string) self::get('site_email', ''));
        }

        return $email !== '' ? $email : 'info@solaryatravel.com';
    }

    /**
     * Politica di storno: fasce ordinate dalla più lontana alla più vicina alla
     * partenza. Ogni fascia: ['days' => giorni minimi prima della partenza,
     * 'refund' => % rimborso]. L'ultima (days=0) è il caso "sotto la soglia minima".
     *
     * @return array<int, array{days:int, refund:int}>
     */
    public static function cancellationPolicy(): array
    {
        return [
            ['days' => (int) self::get('cancel_penalty_days_1', 14), 'refund' => (int) self::get('cancel_penalty_refund_1', 70)],
            ['days' => (int) self::get('cancel_penalty_days_2', 7),  'refund' => (int) self::get('cancel_penalty_refund_2', 50)],
            ['days' => 0, 'refund' => (int) self::get('cancel_penalty_refund_under', 0)],
        ];
    }

    public static function depositEnabled(): bool
    {
        return (bool) self::get('deposit_enabled', false);
    }

    public static function depositPercentage(): int
    {
        $pct = (int) self::get('deposit_percentage', 50);
        return max(1, min(99, $pct));
    }

    public static function balanceDueHours(): int
    {
        return max(1, (int) self::get('balance_due_hours', 12));
    }

    /**
     * Anticipo minimo di prenotazione: una partenza è prenotabile solo fino a
     * N ore prima dell'orario di partenza. 0 = nessun limite (prenotabile
     * fino all'ultimo, purché non già passata).
     */
    public static function bookingCutoffHours(): int
    {
        return max(0, (int) self::get('booking_cutoff_hours', 0));
    }

    public static function bankTransferEnabled(): bool
    {
        return (bool) self::get('bank_transfer_enabled', false);
    }

    public static function bankTransferDetails(): string
    {
        return trim((string) self::get('bank_transfer_details', ''));
    }

    /**
     * Ore entro cui un bonifico istantaneo va confermato prima che la
     * prenotazione scada e i posti tornino disponibili.
     */
    public static function bankTransferExpiryHours(): int
    {
        return max(1, (int) self::get('bank_transfer_expiry_hours', 24));
    }

    /**
     * Numero minimo di partecipanti per confermare la partenza.
     */
    public static function minParticipants(): int
    {
        return max(1, (int) self::get('min_participants', 6));
    }

    /**
     * Etichetta del termine entro cui si verifica il raggiungimento del minimo
     * (es. "48 ore prima della partenza"). Usata nell'avviso al cliente.
     */
    public static function minParticipantsDeadlineLabel(): string
    {
        $label = trim((string) self::get('min_participants_deadline_label', ''));
        return $label !== '' ? $label : '48 ore prima della partenza';
    }

    /**
     * Testo completo dell'avviso sul minimo partecipanti, costruito dai valori
     * impostabili in admin. Centralizzato così da mostrarlo identico in pagina
     * escursione, checkout ed email di conferma.
     */
    public static function minParticipantsNotice(): string
    {
        return sprintf(
            'La partenza è confermata al raggiungimento di un minimo di %d partecipanti. '
            . 'In caso di mancato raggiungimento entro %s, la crociera non verrà effettuata '
            . 'e l\'importo sarà interamente rimborsato.',
            self::minParticipants(),
            self::minParticipantsDeadlineLabel()
        );
    }
}
