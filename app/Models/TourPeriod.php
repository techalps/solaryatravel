<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourPeriod extends Model
{
    use HasFactory;
    use Concerns\HasTranslations;

    protected $fillable = [
        'translations',
        'tour_id',
        'label',
        'start_date',
        'end_date',
        'weekdays',
        'times',
        'base_price',
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
        'start_date' => 'date',
        'end_date' => 'date',
        'weekdays' => 'array',
        'times' => 'array',
        'base_price' => 'decimal:2',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function ageBrackets(): HasMany
    {
        return $this->hasMany(TourAgeBracket::class)->orderBy('sort_order');
    }
}
