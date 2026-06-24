<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evento del ciclo prenotazioni/pagamenti/email. Scritto da App\Support\BookingLog.
 */
class BookingEvent extends Model
{
    protected $fillable = [
        'occurred_at', 'level', 'context', 'booking_number',
        'booking_id', 'status', 'message', 'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
