<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Notifica operativa per gli admin.
 *
 * L'evento è uno, gli stati sono per admin (vedi AdminNotificationState):
 * quello che Mario ha letto resta non letto per Anna.
 */
class AdminNotification extends Model
{
    use HasFactory;

    /** Tipi di evento, con la resa nell'interfaccia. */
    public const TYPES = [
        // Nuove prenotazioni
        'booking_created' => ['label' => 'Nuova prenotazione', 'icon' => 'bi-calendar-plus', 'color' => 'primary', 'toast' => true],
        // Pagamenti e scadenze
        'payment_received' => ['label' => 'Pagamento ricevuto', 'icon' => 'bi-credit-card', 'color' => 'success', 'toast' => true],
        'payment_expiring' => ['label' => 'Prenotazione in scadenza', 'icon' => 'bi-hourglass-split', 'color' => 'warning', 'toast' => false],
        'transfer_to_verify' => ['label' => 'Bonifico da verificare', 'icon' => 'bi-bank', 'color' => 'warning', 'toast' => true],
        'balance_overdue' => ['label' => 'Saldo scaduto', 'icon' => 'bi-exclamation-circle', 'color' => 'warning', 'toast' => false],
        // Annullamenti e rimborsi
        'booking_cancelled' => ['label' => 'Prenotazione annullata', 'icon' => 'bi-x-circle', 'color' => 'danger', 'toast' => true],
        'booking_refunded' => ['label' => 'Rimborso eseguito', 'icon' => 'bi-arrow-counterclockwise', 'color' => 'info', 'toast' => true],
        'b2b_request' => ['label' => 'Richiesta da agenzia', 'icon' => 'bi-briefcase', 'color' => 'warning', 'toast' => true],
        // Documenti
        'missing_documents' => ['label' => 'Documenti mancanti', 'icon' => 'bi-person-vcard', 'color' => 'warning', 'toast' => false],
    ];

    protected $fillable = ['type', 'title', 'body', 'booking_id', 'caused_by', 'data'];

    protected $casts = ['data' => 'array'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caused_by');
    }

    public function states(): HasMany
    {
        return $this->hasMany(AdminNotificationState::class);
    }

    /** Metadati di resa del tipo, con fallback per tipi non previsti. */
    public function meta(): array
    {
        return self::TYPES[$this->type] ?? [
            'label' => ucfirst(str_replace('_', ' ', $this->type)),
            'icon' => 'bi-bell',
            'color' => 'secondary',
            'toast' => false,
        ];
    }

    public function icon(): string
    {
        return $this->meta()['icon'];
    }

    public function color(): string
    {
        return $this->meta()['color'];
    }

    public function showsToast(): bool
    {
        return (bool) $this->meta()['toast'];
    }

    /**
     * Notifiche visibili a un admin: quelle che non ha eliminato.
     *
     * Lo stato può non esistere (notifica mai aperta): il left join lo tiene,
     * mentre un inner join la farebbe sparire dall'elenco.
     */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query
            ->leftJoin('admin_notification_states as st', function ($join) use ($userId) {
                $join->on('st.admin_notification_id', '=', 'admin_notifications.id')
                    ->where('st.user_id', '=', $userId);
            })
            ->whereNull('st.deleted_at')
            ->select('admin_notifications.*', 'st.read_at as my_read_at');
    }

    /** Solo le non lette da quell'admin. */
    public function scopeUnreadFor(Builder $query, int $userId): Builder
    {
        return $query->visibleTo($userId)->whereNull('st.read_at');
    }
}
