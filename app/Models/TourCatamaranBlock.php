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
     * Id dei catamarani bloccati in una certa data (e, se indicata, in una certa
     * FASCIA ORARIA), indipendentemente dal tour. Un catamarano riservato/bloccato
     * è fisicamente occupato per QUALSIASI tour → disponibilità calcolata globalmente.
     *
     * Se $startTime/$endTime sono indicati, un blocco conta solo se la sua fascia
     * oraria [start_time, end_time) si SOVRAPPONE a quella richiesta: due fasce
     * disgiunte nello stesso giorno (es. 09:00–12:30 e 12:30–18:00) non collidono.
     * Un blocco senza orari è considerato "intera giornata" (collide sempre).
     *
     * @return array<int,int>
     */
    public static function blockedCatamaranIdsOn(
        string|\DateTimeInterface $date,
        ?string $startTime = null,
        ?string $endTime = null
    ): array {
        $d = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : \Carbon\Carbon::parse($date)->format('Y-m-d');

        $blocks = static::whereDate('start_date', '<=', $d)
            ->whereDate('end_date', '>=', $d)
            ->get(['catamaran_id', 'start_time', 'end_time']);

        // Nessuna finestra oraria richiesta → comportamento "intera giornata".
        $reqStart = $startTime !== null ? self::toMinutes($startTime) : null;
        $reqEnd = $endTime !== null ? self::toMinutes($endTime) : null;

        return $blocks
            ->filter(function ($b) use ($reqStart, $reqEnd) {
                // Senza finestra richiesta, o blocco senza orari → collide sempre.
                if ($reqStart === null || $reqEnd === null) {
                    return true;
                }
                if (empty($b->start_time) || empty($b->end_time)) {
                    return true; // blocco intera giornata
                }
                $bStart = self::toMinutes($b->start_time);
                $bEnd = self::toMinutes($b->end_time);
                // Sovrapposizione di intervalli semiaperti [start, end):
                // collidono solo se reqStart < bEnd && bStart < reqEnd.
                return $reqStart < $bEnd && $bStart < $reqEnd;
            })
            ->pluck('catamaran_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Converte 'HH:MM' o 'HH:MM:SS' in minuti dall'inizio giornata. */
    protected static function toMinutes(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');
        return ((int) $h) * 60 + (int) $m;
    }
}
