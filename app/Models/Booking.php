<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Observers\BookingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BookingObserver::class])]
class Booking extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'uuid',
        'booking_number',
        'user_id',
        'tour_id',
        'tour_departure_id',
        'booking_date',
        'seats',
        'base_price',
        'addons_total',
        'discount_amount',
        'discount_code_id',
        'tax_amount',
        'total_amount',
        'payment_type',
        'deposit_amount',
        'balance_amount',
        'amount_paid',
        'balance_due_at',
        'penalty_amount',
        'currency',
        'status',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'customer_country',
        'special_requests',
        'qr_code',
        'payment_deadline',
        'confirmed_at',
        'checked_in_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'source',
        'external_reference',
        'metadata',
        'payment_link_sent_at',
        'tickets_sent_at',
        'reminder_48h_sent_at',
        'reminder_24h_sent_at',
        'balance_reminder_sent_at',
        'bank_transfer_reminder_sent_at',
        'checkout_url',
        'locale',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'booking_date' => 'date',
        'base_price' => 'decimal:2',
        'addons_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'balance_due_at' => 'datetime',
        'metadata' => 'array',
        'payment_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payment_link_sent_at' => 'datetime',
        'tickets_sent_at' => 'datetime',
        'reminder_48h_sent_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
        'balance_reminder_sent_at' => 'datetime',
        'bank_transfer_reminder_sent_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function seatRecords(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function successfulPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', 'succeeded')->latest();
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', BookingStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', BookingStatus::CONFIRMED);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'refunded', 'no_show']);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('booking_date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING]);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === BookingStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::CONFIRMED;
    }

    public function isCheckedIn(): bool
    {
        return $this->status === BookingStatus::CHECKED_IN;
    }

    public function canBeCheckedIn(): bool
    {
        // Il posto è garantito anche col solo acconto versato.
        return in_array($this->status, [BookingStatus::CONFIRMED, BookingStatus::DEPOSIT_PAID])
            && $this->booking_date->isToday();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            BookingStatus::PENDING,
            BookingStatus::DEPOSIT_PAID,
            BookingStatus::AWAITING_TRANSFER,
            BookingStatus::CONFIRMED,
        ]);
    }

    /** Acconto versato con saldo ancora da pagare. */
    public function hasBalanceDue(): bool
    {
        return $this->status === BookingStatus::DEPOSIT_PAID
            && (float) $this->balance_amount > 0;
    }

    public function getCustomerFullNameAttribute(): string
    {
        return trim($this->customer_first_name . ' ' . $this->customer_last_name);
    }

    /**
     * Catamarani assegnati ai posti di questa prenotazione (distinct).
     */
    public function getAssignedCatamaransAttribute()
    {
        return $this->seatRecords()
            ->whereNotNull('catamaran_id')
            ->with('catamaran')
            ->get()
            ->pluck('catamaran')
            ->unique('id')
            ->values();
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = static::generateBookingNumber();
            }
            if (empty($booking->qr_code)) {
                $booking->qr_code = static::generateQRCode();
            }
        });
    }

    public static function generateBookingNumber(): string
    {
        $year = now()->format('Y');
        $lastBooking = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence = $lastBooking
            ? (int) substr($lastBooking->booking_number, -5) + 1
            : 1;
        return sprintf('SLY-%s-%05d', $year, $sequence);
    }

    public static function generateQRCode(): string
    {
        do {
            $code = strtoupper(\Illuminate\Support\Str::random(12));
        } while (static::where('qr_code', $code)->exists());
        return $code;
    }
}
