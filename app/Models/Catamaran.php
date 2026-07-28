<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Posti occupati sul catamarano in una data e FASCIA ORARIA, su QUALSIASI
     * tour e partenza.
     *
     * Una barca è fisica: se è impegnata da un altro tour in un orario che si
     * sovrappone, quei posti non sono disponibili nemmeno per il tour che stiamo
     * prenotando. seatsBookedOnDeparture() conta invece solo la singola riga
     * tour_departures, e da lì nascevano sia l'incoerenza con l'uso esclusivo
     * sia il rischio di vendere due volte la stessa barca.
     *
     * Il confronto è per SLOT, non per giornata: Daily Escape 10:00-17:00 e
     * Sunset Escape 18:00-21:00 possono usare la stessa barca lo stesso giorno.
     * Le riserve per l'intera giornata o in uso esclusivo restano gestite dai
     * blocchi (TourCatamaranBlock::blockedCatamaranIdsOn), che gli chiamanti
     * verificano a parte.
     *
     * Senza fascia oraria ($startTime/$endTime null) conta l'intera giornata.
     */
    public function seatsBookedOnDate(
        string|\DateTimeInterface $date,
        ?string $startTime = null,
        ?string $endTime = null
    ): int {
        $d = is_string($date) ? $date : $date->format('Y-m-d');

        $seats = $this->bookingSeats()
            ->whereNull('cancelled_at')
            ->whereHas('booking', function ($q) use ($d) {
                $q->whereDate('booking_date', $d)
                  ->whereNotIn('status', ['cancelled', 'refunded', 'no_show']);
            })
            ->with(['booking.departure', 'booking.tour'])
            ->get();

        // Nessuna finestra richiesta → intera giornata.
        if ($startTime === null || $endTime === null) {
            return $seats->count();
        }

        $reqStart = Carbon::parse($d.' '.$startTime);
        $reqEnd = Carbon::parse($d.' '.$endTime);

        return $seats->filter(function ($seat) use ($d, $reqStart, $reqEnd) {
            $dep = $seat->booking?->departure;

            // Senza partenza collegata non sappiamo l'orario: contiamo il posto
            // (prudenziale, meglio non vendere che sovrapporre).
            if (! $dep || ! $dep->start_time) {
                return true;
            }

            $start = Carbon::parse($d.' '.Carbon::parse($dep->start_time)->format('H:i'));

            if ($dep->end_time) {
                $end = Carbon::parse($d.' '.Carbon::parse($dep->end_time)->format('H:i'));
            } else {
                $durationMin = (int) round(((float) ($seat->booking->tour?->duration_hours ?? 1)) * 60);
                $end = $start->copy()->addMinutes($durationMin);
            }

            // Overlap di intervalli semiaperti: slot disgiunti non collidono.
            return $reqStart->lt($end) && $start->lt($reqEnd);
        })->count();
    }
}
