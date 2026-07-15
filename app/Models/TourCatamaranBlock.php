<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourCatamaranBlock extends Model
{
    protected $table = 'tour_catamaran_blocks';

    protected $fillable = [
        'tour_id',
        'catamaran_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function catamaran(): BelongsTo
    {
        return $this->belongsTo(Catamaran::class);
    }

    /**
     * Id dei catamarani bloccati per una partenza in una certa data (e, se
     * indicata, in una certa fascia oraria), indipendentemente dal tour: una barca
     * riservata è fisicamente occupata per QUALSIASI tour → disponibilità globale.
     *
     * Semantica del blocco come INTERVALLO CONTINUO tra due ISTANTI assoluti:
     *   inizio  = start_date + start_time   (00:00 se start_time mancante)
     *   fine    = end_date   + end_time     (24:00 = fine giornata se end_time mancante)
     * Collide con la partenza se gli intervalli [depStart,depEnd) e [blkStart,blkEnd)
     * si sovrappongono. Casi coperti:
     *  - Giorno singolo con orari (09:00–12:30): una partenza pomeridiana (14:00)
     *    NON collide → il catamarano è libero fuori dalla fascia.
     *  - Multi-giorno (20/07 09:00 → 21/07 18:00): occupato in continuo. Il 21/07
     *    una partenza dalle 18:00 in poi è libera; prima no. I giorni intermedi
     *    sono occupati per intero.
     * Se non viene passata una finestra oraria della partenza, il confronto è per
     * sola data (comportamento intera giornata, usato dal calendario).
     *
     * @return array<int,int>
     */
    public static function blockedCatamaranIdsOn(
        string|\DateTimeInterface $date,
        ?string $startTime = null,
        ?string $endTime = null
    ): array {
        $d = $date instanceof \DateTimeInterface
            ? \Carbon\Carbon::parse($date->format('Y-m-d'))
            : \Carbon\Carbon::parse($date)->startOfDay();
        $dStr = $d->format('Y-m-d');

        // Blocchi la cui giornata copre la data richiesta (per data, prima soglia).
        $blocks = static::whereDate('start_date', '<=', $dStr)
            ->whereDate('end_date', '>=', $dStr)
            ->get(['catamaran_id', 'start_date', 'start_time', 'end_date', 'end_time']);

        // Nessuna finestra oraria richiesta → comportamento "intera giornata"
        // (usato dal calendario che ragiona solo per data).
        if ($startTime === null || $endTime === null) {
            return $blocks->pluck('catamaran_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        }

        // Istante della partenza (sulla data richiesta).
        $depStart = self::at($d, $startTime);
        $depEnd = self::at($d, $endTime);

        return $blocks
            ->filter(function ($b) use ($depStart, $depEnd) {
                // Istante iniziale/finale del blocco come intervallo CONTINUO.
                $blkStart = self::at(\Carbon\Carbon::parse($b->start_date), $b->start_time ?: '00:00');
                // end_time mancante → fino a fine giornata dell'end_date (24:00).
                $blkEnd = $b->end_time
                    ? self::at(\Carbon\Carbon::parse($b->end_date), $b->end_time)
                    : \Carbon\Carbon::parse($b->end_date)->endOfDay();
                // Overlap di intervalli semiaperti: depStart < blkEnd && blkStart < depEnd.
                return $depStart->lt($blkEnd) && $blkStart->lt($depEnd);
            })
            ->pluck('catamaran_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Combina una data (Carbon) con un orario 'HH:MM'/'HH:MM:SS' in un istante. */
    protected static function at(\Carbon\Carbon $date, string $time): \Carbon\Carbon
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return $date->copy()->startOfDay()->setTime((int) $h, (int) $m);
    }
}
