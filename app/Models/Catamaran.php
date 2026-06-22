<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catamaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'description_short',
        'capacity',
        'length_meters',
        'features',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'length_meters' => 'decimal:2',
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
        return $this->hasMany(CatamaranImage::class)->orderBy('sort_order');
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tour_catamaran')
            ->withPivot('priority');
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /**
     * Prenotazioni con almeno un posto su questo catamarano.
     * Usabile sia come $catamaran->bookings sia come $catamaran->bookings()->count().
     * Nota: può contenere duplicati se la prenotazione ha più posti sullo stesso catamarano:
     * usare ->distinct() o groupBy('bookings.id') quando serve un conteggio univoco.
     */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Booking::class,
            BookingSeat::class,
            'catamaran_id', // FK su booking_seats verso catamarans
            'id',           // PK di bookings
            'id',           // PK di catamarans
            'booking_id'    // FK su booking_seats verso bookings
        )->distinct();
    }

    public function unavailability(): HasMany
    {
        return $this->hasMany(CatamaranUnavailability::class);
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
    public function getPrimaryImageAttribute(): ?CatamaranImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    /**
     * Verifica se il catamarano è disponibile in una data specifica
     * (nessun blocco di unavailability copre la data).
     */
    public function isAvailableOn(string|\DateTimeInterface $date): bool
    {
        $d = is_string($date) ? $date : $date->format('Y-m-d');
        return !$this->unavailability()
            ->where('date_start', '<=', $d)
            ->where('date_end', '>=', $d)
            ->exists();
    }

    /**
     * Posti già occupati da prenotazioni attive su una specifica partenza.
     */
    public function seatsBookedOnDeparture(int $tourDepartureId): int
    {
        return (int) $this->bookingSeats()
            ->whereNull('cancelled_at') // i posti disdetti non occupano più
            ->whereHas('booking', function ($q) use ($tourDepartureId) {
                $q->where('tour_departure_id', $tourDepartureId)
                  ->whereNotIn('status', ['cancelled', 'refunded', 'no_show']);
            })
            ->count();
    }
}
