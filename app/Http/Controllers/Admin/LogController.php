<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard diagnostica degli eventi del ciclo prenotazioni/pagamenti/email
 * (tabella booking_events). Riservata a system_admin (middleware "system").
 */
class LogController extends Controller
{
    /** Etichette leggibili per i context noti. */
    public const CONTEXT_LABELS = [
        'booking_create'        => 'Creazione prenotazione',
        'booking_admin_create'  => 'Creazione (admin)',
        'booking_confirm'       => 'Conferma prenotazione',
        'booking_transfer_confirm' => 'Conferma bonifico',
        'booking_admin_cancel'  => 'Annullamento (admin)',
        'booking_cancel'        => 'Annullamento',
        'booking_reschedule'    => 'Cambio data',
        'booking_remove_items'  => 'Rimozione partecipanti',
        'booking_refund'        => 'Rimborso',
        'payment_checkout'      => 'Checkout Stripe',
        'payment_return'        => 'Ritorno pagamento',
        'payment_webhook'       => 'Webhook Stripe',
        'payment_succeeded'     => 'Pagamento riuscito',
        'payment_failed'        => 'Pagamento fallito',
        'payment_refund'        => 'Rimborso Stripe',
        'email_send'            => 'Invio email',
        'reminder_48h'          => 'Reminder 48h',
        'reminder_24h'          => 'Reminder 24h',
        'reminder_balance'      => 'Reminder saldo',
        'reminder_transfer'     => 'Reminder bonifico',
        'reminders_cron'        => 'Cron reminder',
    ];

    public function index(Request $request): View
    {
        $period = $request->input('period', 'week');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $level = $request->input('level');              // info|warning|error
        $context = $request->input('context');          // uno dei context
        $search = trim((string) $request->input('q', '')); // booking_number

        // Query base filtrata (riusata da KPI, grafici e tabella).
        $base = BookingEvent::query()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->when($level, fn ($q) => $q->where('level', $level))
            ->when($context, fn ($q) => $q->where('context', $context))
            ->when($search !== '', fn ($q) => $q->where('booking_number', 'like', "%{$search}%"));

        // KPI per livello.
        $byLevel = (clone $base)->selectRaw('level, COUNT(*) as c')->groupBy('level')->pluck('c', 'level');
        $stats = [
            'total'   => (int) $byLevel->sum(),
            'info'    => (int) ($byLevel['info'] ?? 0),
            'warning' => (int) ($byLevel['warning'] ?? 0),
            'error'   => (int) ($byLevel['error'] ?? 0),
        ];

        // Distribuzione per tipologia (context).
        $byContext = (clone $base)
            ->selectRaw('context, COUNT(*) as c')
            ->groupBy('context')
            ->orderByDesc('c')
            ->get()
            ->map(fn ($r) => [
                'context' => $r->context,
                'label' => self::CONTEXT_LABELS[$r->context] ?? $r->context,
                'count' => (int) $r->c,
            ]);

        // Andamento giornaliero per livello (per il grafico a linee impilato).
        $daily = (clone $base)
            ->selectRaw('DATE(occurred_at) as d, level, COUNT(*) as c')
            ->groupBy('d', 'level')
            ->orderBy('d')
            ->get();

        $days = $daily->pluck('d')->unique()->values();
        $series = [];
        foreach (['info', 'warning', 'error'] as $lv) {
            $series[$lv] = $days->map(function ($d) use ($daily, $lv) {
                return (int) ($daily->firstWhere(fn ($r) => $r->d === $d && $r->level === $lv)->c ?? 0);
            });
        }

        // Tabella eventi (paginata), più recenti prima.
        $events = (clone $base)->orderByDesc('occurred_at')->paginate(50)->withQueryString();

        // Tutti i context disponibili per la tendina filtro.
        $contexts = BookingEvent::select('context')->distinct()->pluck('context')
            ->mapWithKeys(fn ($c) => [$c => self::CONTEXT_LABELS[$c] ?? $c])
            ->sort();

        return view('admin.system.logs', compact(
            'period', 'startDate', 'endDate', 'level', 'context', 'search',
            'stats', 'byContext', 'days', 'series', 'events', 'contexts'
        ));
    }

    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'week'  => now()->subDays(7),
            'month' => now()->subDays(30),
            'quarter' => now()->subDays(90),
            'all'   => Carbon::createFromDate(2020, 1, 1),
            default => now()->subDays(7),
        };
    }
}
