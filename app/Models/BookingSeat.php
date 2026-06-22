<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingSeat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'seat_number',
        'catamaran_id',
        'tour_age_bracket_id',
        'price_paid',
        'guest_first_name',
        'guest_last_name',
        'guest_date_of_birth',
        'tax_code',
        'is_primary',
        'qr_code',
        'boarded_at',
        'boarded_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    public function hasGuestDetails(): bool
    {
        return !empty($this->guest_first_name) && !empty($this->guest_last_name);
    }

    protected $casts = [
        'is_primary' => 'boolean',
        'price_paid' => 'decimal:2',
        'guest_date_of_birth' => 'date',
        'created_at' => 'datetime',
        'boarded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** Posto disdetto: resta per storico ma non conta più. */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /** Solo i posti attivi (non disdetti). */
    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $seat) {
            if (empty($seat->qr_code)) {
                $seat->qr_code = static::generateUniqueQrCode();
            }
        });
    }

    public static function generateUniqueQrCode(): string
    {
        do {
            $code = 'SLY-S-' . strtoupper(Str::random(10));
        } while (static::where('qr_code', $code)->exists());
        return $code;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function catamaran(): BelongsTo
    {
        return $this->belongsTo(Catamaran::class);
    }

    public function ageBracket(): BelongsTo
    {
        return $this->belongsTo(TourAgeBracket::class, 'tour_age_bracket_id');
    }

    public function boardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'boarded_by');
    }

    public function getGuestFullNameAttribute(): string
    {
        return trim(($this->guest_first_name ?? '') . ' ' . ($this->guest_last_name ?? ''));
    }

    public function isBoarded(): bool
    {
        return $this->boarded_at !== null;
    }

    public function markBoarded(?int $userId = null): void
    {
        $this->forceFill([
            'boarded_at' => now(),
            'boarded_by' => $userId,
        ])->save();
    }

    public function unmarkBoarded(): void
    {
        $this->forceFill([
            'boarded_at' => null,
            'boarded_by' => null,
        ])->save();
    }
}

