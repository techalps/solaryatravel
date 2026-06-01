<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'description_short',
        'duration_hours',
        'departure_point',
        'itinerary',
        'included',
        'excluded',
        'season_start',
        'season_end',
        'min_capacity',
        'max_capacity',
        'is_active',
        'booking_on_request',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'included' => 'array',
        'excluded' => 'array',
        'season_start' => 'date',
        'season_end' => 'date',
        'is_active' => 'boolean',
        'booking_on_request' => 'boolean',
        'duration_hours' => 'decimal:1',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Relationships
    public function images(): HasMany
    {
        return $this->hasMany(TourImage::class)->orderBy('sort_order');
    }

    public function ageBrackets(): HasMany
    {
        return $this->hasMany(TourAgeBracket::class)->orderBy('sort_order');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(TourPeriod::class)->orderBy('sort_order')->orderBy('start_date');
    }

    public function catamaranBlocks(): HasMany
    {
        return $this->hasMany(TourCatamaranBlock::class)->orderBy('start_date');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(TourDeparture::class);
    }

    /**
     * Catamarani associati esplicitamente a questo tour.
     * Se vuoto, il tour può essere operato da TUTTI i catamarani attivi.
     */
    public function catamarans(): BelongsToMany
    {
        return $this->belongsToMany(Catamaran::class, 'tour_catamaran')
            ->withPivot('priority')
            ->orderByPivot('priority');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getPrimaryImageAttribute(): ?TourImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    /**
     * Restituisce i catamarani utilizzabili per il tour (espliciti se presenti,
     * altrimenti tutti i catamarani attivi).
     *
     * @return \Illuminate\Support\Collection<int, Catamaran>
     */
    public function operatingCatamarans()
    {
        if ($this->catamarans()->exists()) {
            return $this->catamarans()->where('is_active', true)->get();
        }

        return Catamaran::active()->ordered()->get();
    }

    /**
     * Capacità totale teorica = somma capacity dei catamarani operativi.
     */
    public function getTotalCapacityAttribute(): int
    {
        return (int) $this->operatingCatamarans()->sum('capacity');
    }

    /**
     * Prezzo "from" (più basso tra le fasce d'età, escludendo le gratuite).
     * Considera tutte le fasce associate al tour (sia legate a un periodo
     * sia "orfane" — vecchio sistema).
     */
    public function getPriceFromAttribute(): ?float
    {
        $prices = $this->periods->where('base_price', '>', 0)->pluck('base_price');
        return $prices->isEmpty() ? null : (float) $prices->min();
    }
}
