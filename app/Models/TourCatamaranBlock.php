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
     * Id dei catamarani bloccati in una certa data, indipendentemente dal tour.
     * Un catamarano riservato/bloccato è fisicamente occupato per l'INTERA GIORNATA
     * e per QUALSIASI tour → disponibilità calcolata globalmente.
     *
     * IMPORTANTE: un blocco occupa SEMPRE tutta la giornata, anche se ha
     * start_time/end_time valorizzati. Gli orari su un blocco descrivono solo
     * l'andata/ritorno della riserva a uso esclusivo (a scopo informativo); NON
     * devono liberare le altre fasce orarie della stessa barca nello stesso
     * giorno. Trattarli come finestra oraria causava OVERBOOKING: una partenza
     * normale in una fascia diversa vedeva il catamarano come libero.
     *
     * I parametri $startTime/$endTime sono mantenuti per compatibilità con i
     * chiamanti ma NON filtrano più: qualunque blocco attivo sulla data collide.
     * Le riserve su più date bloccano ogni giorno del periodo (start_date..end_date).
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

        return static::whereDate('start_date', '<=', $d)
            ->whereDate('end_date', '>=', $d)
            ->pluck('catamaran_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
