<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Centro notifiche dell'area admin.
 *
 * Il badge e i toast si aggiornano via polling (`feed`): su hosting condiviso
 * OVH non ci sono WebSocket — servirebbe un processo sempre attivo, che non è
 * consentito — quindi il "tempo reale" è una richiesta leggera ogni 30s.
 */
class NotificationController extends Controller
{
    public function __construct(private AdminNotificationService $notifiche) {}

    /** Elenco completo, con paginazione. */
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifiche = AdminNotification::query()
            ->visibleTo($user->getKey())
            ->with(['booking:id,uuid,booking_number', 'author:id,name'])
            ->orderByDesc('admin_notifications.created_at')
            ->paginate(30);

        return view('admin.notifications.index', [
            'notifiche' => $notifiche,
            'nonLette' => $this->notifiche->unreadCount($user),
        ]);
    }

    /**
     * Dati per campanella e toast. Interrogato dal polling: deve restare
     * leggero (nessun eager loading superfluo, pochi record).
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $toast = $this->notifiche->pendingToasts($user)->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'icon' => $n->icon(),
            'color' => $n->color(),
            'url' => $n->booking ? route('admin.bookings.show', $n->booking_id) : route('admin.notifications.index'),
        ]);

        return response()->json([
            'unread' => $this->notifiche->unreadCount($user),
            'items' => $this->notifiche->latestFor($user, 8)->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'icon' => $n->icon(),
                'color' => $n->color(),
                'read' => $n->my_read_at !== null,
                'ago' => $n->created_at->diffForHumans(short: true),
                'url' => $n->booking ? route('admin.bookings.show', $n->booking_id) : route('admin.notifications.index'),
            ]),
            'toasts' => $toast,
        ]);
    }

    /** Segna letta e porta alla prenotazione collegata, se c'è. */
    public function read(Request $request, AdminNotification $notification): RedirectResponse
    {
        $this->notifiche->markRead($notification, $request->user());

        return $notification->booking_id
            ? redirect()->route('admin.bookings.show', $notification->booking_id)
            : back();
    }

    /** Segna letta senza spostarsi: usata dai toast e dal menu a tendina. */
    public function readAjax(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->notifiche->markRead($notification, $request->user());

        return response()->json(['unread' => $this->notifiche->unreadCount($request->user())]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $n = $this->notifiche->markAllRead($request->user());

        return back()->with('success', $n > 0
            ? $n.' notifiche segnate come lette.'
            : 'Nessuna notifica da segnare come letta.');
    }

    /** Elimina solo per l'admin corrente: gli altri continuano a vederla. */
    public function destroy(Request $request, AdminNotification $notification): RedirectResponse
    {
        $this->notifiche->delete($notification, $request->user());

        return back()->with('success', 'Notifica eliminata.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $n = $this->notifiche->deleteAll($request->user());

        return back()->with('success', $n > 0
            ? $n.' notifiche eliminate.'
            : 'Nessuna notifica da eliminare.');
    }
}
