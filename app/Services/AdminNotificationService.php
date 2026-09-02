<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\AdminNotificationState;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creazione e lettura delle notifiche per l'area admin.
 *
 * Punto unico: gli eventi arrivano dall'observer sulle prenotazioni e dai
 * comandi schedulati, e la resa (badge, elenco, toast) legge da qui.
 */
class AdminNotificationService
{
    /**
     * Registra un evento.
     *
     * @param  string  $type   Una delle chiavi di AdminNotification::TYPES.
     * @param  User|int|null  $causedBy Chi ha provocato l'evento: non riceverà
     *                                  il toast (una prenotazione creata da me
     *                                  non deve avvisarmi che è arrivata).
     */
    public function notify(
        string $type,
        string $title,
        ?string $body = null,
        ?Booking $booking = null,
        User|int|null $causedBy = null,
        array $data = [],
    ): AdminNotification {
        return AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'booking_id' => $booking?->getKey(),
            'caused_by' => $causedBy instanceof User ? $causedBy->getKey() : $causedBy,
            'data' => $data ?: null,
        ]);
    }

    /** Numero di notifiche non lette per il badge sulla campanella. */
    public function unreadCount(User $user): int
    {
        return AdminNotification::query()->unreadFor($user->getKey())->count();
    }

    /**
     * Notifiche visibili, più recenti prima.
     *
     * @return \Illuminate\Support\Collection<int, AdminNotification>
     */
    public function latestFor(User $user, int $limit = 15)
    {
        return AdminNotification::query()
            ->visibleTo($user->getKey())
            ->with(['booking:id,uuid,booking_number', 'author:id,name'])
            ->orderByDesc('admin_notifications.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Notifiche da mostrare come toast: non lette, di tipo "toast", recenti e
     * non causate dall'utente stesso.
     *
     * La finestra temporale evita che riaprendo l'admin dopo giorni compaia
     * una pila di toast per eventi vecchi: quelli restano nell'elenco.
     */
    public function pendingToasts(User $user, int $withinMinutes = 10)
    {
        $tipiToast = collect(AdminNotification::TYPES)
            ->filter(fn ($m) => $m['toast'])
            ->keys()
            ->all();

        return AdminNotification::query()
            ->unreadFor($user->getKey())
            ->whereIn('type', $tipiToast)
            ->where('admin_notifications.created_at', '>=', now()->subMinutes($withinMinutes))
            ->where(function ($q) use ($user) {
                $q->whereNull('caused_by')->orWhere('caused_by', '!=', $user->getKey());
            })
            ->with('booking:id,uuid,booking_number')
            ->orderByDesc('admin_notifications.created_at')
            ->limit(5)
            ->get();
    }

    public function markRead(AdminNotification $notification, User $user): void
    {
        $this->upsertState($notification->getKey(), $user->getKey(), ['read_at' => now()]);
    }

    /** Segna lette tutte le notifiche visibili all'utente. */
    public function markAllRead(User $user): int
    {
        $ids = AdminNotification::query()
            ->unreadFor($user->getKey())
            ->pluck('admin_notifications.id');

        foreach ($ids as $id) {
            $this->upsertState($id, $user->getKey(), ['read_at' => now()]);
        }

        return $ids->count();
    }

    /**
     * Elimina la notifica PER QUESTO admin: resta visibile agli altri.
     * Segna anche letta, così non resta a gonfiare il contatore.
     */
    public function delete(AdminNotification $notification, User $user): void
    {
        $this->upsertState($notification->getKey(), $user->getKey(), [
            'deleted_at' => now(),
            'read_at' => now(),
        ]);
    }

    /** Elimina per questo admin tutte le notifiche che vede. */
    public function deleteAll(User $user): int
    {
        $ids = AdminNotification::query()
            ->visibleTo($user->getKey())
            ->pluck('admin_notifications.id');

        foreach ($ids as $id) {
            $this->upsertState($id, $user->getKey(), ['deleted_at' => now(), 'read_at' => now()]);
        }

        return $ids->count();
    }

    /**
     * Crea o aggiorna lo stato (notifica, admin).
     *
     * updateOrCreate e non insert: due richieste ravvicinate — la campanella
     * che segna letta e il click sulla notifica — proverebbero a inserire la
     * stessa coppia, e l'indice unique farebbe fallire la seconda.
     */
    private function upsertState(int $notificationId, int $userId, array $attributes): void
    {
        AdminNotificationState::updateOrCreate(
            ['admin_notification_id' => $notificationId, 'user_id' => $userId],
            $attributes,
        );
    }

    /**
     * Admin che possono ricevere notifiche gestionali.
     *
     * Lo skipper è escluso: nell'area admin vede solo l'imbarco, e avvisarlo
     * di pagamenti o rimborsi non ha senso.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function recipients()
    {
        return User::query()
            ->whereIn('role', ['admin', 'super_admin', 'system_admin'])
            ->get();
    }
}
