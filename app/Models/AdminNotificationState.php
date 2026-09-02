<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stato di UNA notifica per UN admin: letta quando, eliminata quando.
 *
 * La riga nasce alla prima azione dell'admin su quella notifica: assenza di
 * riga significa "non letta e non eliminata", che è lo stato iniziale.
 *
 * `deleted_at` qui NON è SoftDeletes del modello: è l'eliminazione fatta
 * dall'admin sulla notifica, un dato di dominio.
 */
class AdminNotificationState extends Model
{
    protected $fillable = ['admin_notification_id', 'user_id', 'read_at', 'deleted_at'];

    protected $casts = [
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(AdminNotification::class, 'admin_notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
