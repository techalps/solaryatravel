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
        'b2b_user_id',
        'attribution_source',
        'commission_rate_snapshot',
        'commission_amount',
        'commission_status',
        'commission_paid',
        'commission_paid_at',
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
        'pending_refund_amount',
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
        'pending_refund_amount' => 'decimal:2',
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
        'commission_rate_snapshot' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_paid' => 'boolean',
        'commission_paid_at' => 'datetime',
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

    /** Agenzia (utente b2b) a cui è attribuita la prenotazione, se presente. */
    public function b2bUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'b2b_user_id');
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function seatRecords(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /** Solo i posti attivi (non disdetti). */
    public function activeSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class)->whereNull('cancelled_at');
    }

    /**
     * Almeno un passeggero attivo è privo di documento d'identità completo.
     * Usa la relazione già caricata se disponibile, per non generare query.
     */
    public function hasMissingDocuments(): bool
    {
        $seats = $this->relationLoaded('seatRecords')
            ? $this->seatRecords->whereNull('cancelled_at')
            : $this->activeSeats;

        foreach ($seats as $seat) {
            if (!$seat->hasDocument()) {
                return true;
            }
        }

        return false;
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    /** Solo gli extra attivi (non disdetti). */
    public function activeAddons(): HasMany
    {
        return $this->hasMany(BookingAddon::class)->whereNull('cancelled_at');
    }

    /**
     * Ricalcola posti e importi della prenotazione tenendo conto solo dei posti
     * e degli extra ATTIVI (esclusi i disdetti). Mantiene sconto e aliquota IVA.
     * Restituisce il nuovo total_amount.
     */
    public function recalculateTotals(): float
    {
        $seats = $this->seatRecords()->whereNull('cancelled_at')->get();
        $addons = $this->addons()->whereNull('cancelled_at')->get();

        // Posti "contanti" = quelli con bracket che occupa un posto, o adulti (bracket null).
        $countingSeats = $seats->filter(function ($s) {
            $br = $s->ageBracket;
            return $br === null || $br->counts_as_seat;
        })->count();

        $basePrice = (float) $seats->sum(fn ($s) => (float) $s->price_paid);
        $addonsTotal = (float) $addons->sum(fn ($a) => (float) $a->total_price);

        // Sconto: se percentuale nota la riapplichiamo, altrimenti manteniamo l'importo
        // fisso (ma non oltre il nuovo imponibile).
        $discount = (float) $this->discount_amount;
        if ($discount > 0) {
            $discount = min($discount, $basePrice + $addonsTotal);
        }

        $subtotal = max(0, $basePrice + $addonsTotal - $discount);
        $taxRate = (float) config('booking.tax_rate', 0) / 100;
        $taxAmount = round($subtotal * $taxRate, 2);
        $total = round($subtotal + $taxAmount, 2);

        $this->update([
            'seats' => $countingSeats,
            'base_price' => round($basePrice, 2),
            'addons_total' => round($addonsTotal, 2),
            'discount_amount' => round($discount, 2),
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
        ]);

        return $total;
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

    /** Prenotazioni attribuite al canale B2B (portal o referral). */
    public function scopeB2b($query)
    {
        return $query->whereNotNull('b2b_user_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING]);
    }

    // Helpers
    /** Prenotazione attribuita a un'agenzia (canale B2B)? */
    public function isB2b(): bool
    {
        return $this->b2b_user_id !== null;
    }

    public function isPending(): bool
    {
        return $this->status === BookingStatus::PENDING;
    }

    /**
     * Avvia (una sola volta) la finestra di pagamento con carta.
     *
     * Il timer parte all'apertura del checkout, non alla creazione della
     * prenotazione: quest'ultima nasce senza scadenza, così chi compila il form
     * con calma non consuma il tempo utile. Dalla seconda apertura in poi la
     * scadenza NON si rinnova, altrimenti ricaricare la pagina terrebbe i posti
     * bloccati all'infinito.
     *
     * Vale solo per le prenotazioni in attesa di pagamento carta: il bonifico ha
     * una sua scadenza in ore, assegnata alla creazione.
     */
    public function startCheckoutWindow(): void
    {
        if ($this->status !== BookingStatus::PENDING || $this->payment_deadline !== null) {
            return;
        }

        $this->forceFill([
            'payment_deadline' => now()->addMinutes(\App\Support\Settings::paymentDeadlineMinutes()),
        ])->save();
    }

    /**
     * La finestra di pagamento è scaduta? Verificato ALLA LETTURA, così la
     * scadenza è effettiva anche se lo scheduler è fermo: il job di pulizia
     * ripassa poi a liberare i posti e a scrivere lo stato annullato.
     *
     * Una prenotazione senza payment_deadline non è scaduta: è un carrello a cui
     * il checkout non è ancora stato aperto.
     */
    public function checkoutWindowExpired(): bool
    {
        return $this->payment_deadline !== null
            && in_array($this->status, [BookingStatus::PENDING, BookingStatus::AWAITING_TRANSFER], true)
            && $this->payment_deadline->isPast();
    }

    /**
     * Prenotazioni in attesa la cui scadenza di pagamento è passata: sono i
     * "carrelli" da svuotare (annullare, liberando i posti).
     */
    public function scopeExpiredCheckout($query)
    {
        return $query
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::AWAITING_TRANSFER])
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now());
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

    /**
     * La partenza è già avvenuta.
     *
     * Si guarda l'orario di fine della partenza quando c'è (un tour del mattino
     * è concluso nel pomeriggio dello stesso giorno); altrimenti si ricade sulla
     * data della prenotazione, considerata passata a giorno concluso.
     */
    public function departureIsPast(): bool
    {
        $departure = $this->departure;

        if ($departure?->departure_date) {
            $end = \Carbon\Carbon::parse($departure->departure_date);
            $end = $departure->end_time
                ? $end->setTimeFrom(\Carbon\Carbon::parse($departure->end_time))
                : $end->endOfDay();

            return $end->isPast();
        }

        return (bool) $this->booking_date?->copy()->endOfDay()->isPast();
    }

    /**
     * Può essere portata a "Completata": confermata e con la partenza passata.
     *
     * Non passa da BookingStatus::canTransitionTo() — quel grafo impone
     * confirmed -> checked_in -> completed — perché a bordo il check-in spesso
     * non viene registrato e le prenotazioni resterebbero "confermate" per
     * sempre. Qui "completata" significa "il tour si è svolto".
     */
    public function canBeCompleted(): bool
    {
        return $this->status === BookingStatus::CONFIRMED && $this->departureIsPast();
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

    /**
     * Resta del denaro da incassare su questa prenotazione.
     *
     * Prima guardava SOLO lo stato deposit_paid con balance_amount > 0, cioè il
     * caso "acconto versato". Ma un residuo può nascere anche da un aumento di
     * prezzo su una prenotazione già confermata e pagata: lì lo stato resta
     * CONFIRMED e la differenza non compariva da nessuna parte, senza alcun
     * modo di registrarne l'incasso.
     *
     * Ora la domanda è quella vera: manca del denaro? Si guarda il residuo
     * effettivo, non lo stato.
     */
    public function hasBalanceDue(): bool
    {
        if (in_array($this->status, [
            BookingStatus::CANCELLED,
            BookingStatus::REFUNDED,
            BookingStatus::PENDING,
        ], true)) {
            return false;
        }

        return $this->outstandingAmount() > 0;
    }

    /** Quanto resta da incassare: totale meno quanto già versato. */
    public function outstandingAmount(): float
    {
        return round((float) $this->total_amount - (float) $this->amount_paid, 2);
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
