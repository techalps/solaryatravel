<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Riallinea le scadenze salvate quando l'app girava (per errore) in UTC.
 *
 * config/app.php forzava 'UTC' ignorando APP_TIMEZONE=Europe/Rome: now()
 * restituiva l'ora UTC, veniva salvata così com'era e poi stampata a video come
 * se fosse ora locale. Risultato: una scadenza a +30 minuti nasceva già 1h30
 * nel passato (2h di scarto in CEST).
 *
 * Ora che l'app è su Europe/Rome i timestamp nuovi sono corretti, ma quelli già
 * a database restano indietro dell'offset. Qui li spostiamo in avanti, ma SOLO
 * per le prenotazioni ancora in attesa: sono le uniche in cui la scadenza è
 * ancora operativa (blocca posti, decide l'annullamento automatico). Lo storico
 * di prenotazioni chiuse o annullate si lascia com'è: riscriverlo non cambia
 * nulla e falserebbe l'audit.
 *
 * L'offset è calcolato sull'istante di ciascuna riga, non fisso a 2h: così le
 * righe create in inverno (CET, +1h) vengono spostate di 1h e quelle estive
 * (CEST, +2h) di 2h.
 */
return new class extends Migration
{
    /** Stati in cui la scadenza è ancora viva. */
    protected const OPEN_STATUSES = ['pending', 'awaiting_transfer', 'deposit_paid'];

    /** Colonne di scadenza da riallineare. */
    protected const COLUMNS = ['payment_deadline', 'balance_due_at'];

    public function up(): void
    {
        $this->shift(1);
    }

    public function down(): void
    {
        $this->shift(-1);
    }

    /**
     * Sposta le scadenze aperte di ±(offset locale) ore.
     *
     * @param  int  $direction  1 per applicare, -1 per annullare
     */
    protected function shift(int $direction): void
    {
        $tz = new DateTimeZone(config('app.timezone', 'Europe/Rome'));

        $bookings = DB::table('bookings')
            ->whereIn('status', self::OPEN_STATUSES)
            ->where(function ($q) {
                foreach (self::COLUMNS as $column) {
                    $q->orWhereNotNull($column);
                }
            })
            ->get(array_merge(['id'], self::COLUMNS));

        foreach ($bookings as $booking) {
            $updates = [];

            foreach (self::COLUMNS as $column) {
                if (empty($booking->{$column})) {
                    continue;
                }

                // Il valore salvato è ora UTC scritta come se fosse locale:
                // l'offset da recuperare è quello in vigore a quella data.
                $stored = new DateTimeImmutable((string) $booking->{$column}, $tz);
                $offsetHours = intdiv($tz->getOffset($stored), 3600);

                if ($offsetHours === 0) {
                    continue;
                }

                $updates[$column] = $stored
                    ->modify(sprintf('%+d hours', $direction * $offsetHours))
                    ->format('Y-m-d H:i:s');
            }

            if ($updates !== []) {
                DB::table('bookings')->where('id', $booking->id)->update($updates);
            }
        }
    }
};
