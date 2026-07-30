<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourAgeBracket extends Model
{
    use HasFactory;
    use Concerns\HasTranslations;

    protected $fillable = [
        'translations',
        'tour_id',
        'tour_period_id',
        'label',
        'min_age',
        'max_age',
        'price',
        'counts_as_seat',
        'sort_order',
    ];

    /**
     * Campi che il cliente può tradurre dall'admin (admin → Impostazioni
     * per le lingue attive). L'italiano resta nelle colonne normali ed è
     * il fallback quando una traduzione manca.
     *
     * @var array<int, string>
     */
    protected array $translatable = [
        'label',
    ];

    protected $casts = [
        'translations' => 'array',
        'price' => 'decimal:2',
        'counts_as_seat' => 'boolean',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TourPeriod::class, 'tour_period_id');
    }

    public function getRangeLabelAttribute(): string
    {
        if ($this->min_age === 0 && is_null($this->max_age)) {
            return 'Tutte le età';
        }
        if (is_null($this->max_age)) {
            return $this->min_age . '+ anni';
        }
        return $this->min_age . '-' . $this->max_age . ' anni';
    }
}
