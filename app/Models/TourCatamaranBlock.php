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
     * Un catamarano riservato/bloccato è fisicamente occupato per QUALSIASI tour,
     * quindi la disponibilità va calcolata globalmente (non per singolo tour).
     *
     * @return array<int,int>
     */
    public static function blockedCatamaranIdsOn(string|\DateTimeInterface $date): array
    {
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
