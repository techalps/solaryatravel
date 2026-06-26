<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Support\BookingLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Provvigioni agenzie B2B lato admin: rendicontazione mensile e marcatura
 * "pagata" della singola prenotazione. Solo poteri gestionali (super_admin/
 * system_admin), come per la creazione delle agenzie.
 */
class CommissionController extends Controller
{
    /** La rendicontazione provvigioni è riservata ai poteri gestionali completi. */
    private function guard(): void
    {
        abort_unless(auth()->user()->hasSuperAdminPowers(), 403);
    }

    /**
     * Vista mensile commissioni, aggregata per agenzia. Filtro per mese (Y-m).
     */
    public function index(Request $request): View
    {
        $this->guard();

        $month = $request->input('month', now()->format('Y-m'));
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
            $month = $start->format('Y-m');
        }
        $end = $start->copy()->endOfMonth();

        // Aggregato per agenzia nel mese (sulla data di prenotazione).
        $rows = Booking::query()
            ->b2b()
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('b2b_user_id')
            ->selectRaw('COUNT(*) as bookings_count')
            ->selectRaw('SUM(total_amount) as total_generated')
            ->selectRaw("SUM(CASE WHEN commission_status != 'reversed' THEN commission_amount ELSE 0 END) as commission_earned")
            ->selectRaw('SUM(CASE WHEN commission_paid = 1 THEN commission_amount ELSE 0 END) as commission_paid')
            ->groupBy('b2b_user_id')
            ->get();

        $agencies = User::whereIn('id', $rows->pluck('b2b_user_id'))->get()->keyBy('id');

        $report = $rows->map(function ($r) use ($agencies) {
            $earned = (float) $r->commission_earned;
            $paid = (float) $r->commission_paid;
            return (object) [
                'agency' => $agencies[$r->b2b_user_id] ?? null,
                'bookings_count' => (int) $r->bookings_count,
                'total_generated' => (float) $r->total_generated,
                'commission_earned' => $earned,
                'commission_paid' => $paid,
                'commission_due' => max(0, $earned - $paid),
            ];
        })->sortByDesc('commission_due')->values();

        return view('admin.commissions.index', [
            'month' => $month,
            'start' => $start,
            'report' => $report,
            'totals' => (object) [
                'generated' => $report->sum('total_generated'),
                'earned' => $report->sum('commission_earned'),
                'paid' => $report->sum('commission_paid'),
                'due' => $report->sum('commission_due'),
            ],
        ]);
    }

    /**
     * Dettaglio prenotazioni B2B di una singola agenzia in un mese (per liquidare).
     */
    public function agency(Request $request, User $agency): View
    {
        $this->guard();
        abort_unless($agency->isB2b(), 404);

        $month = $request->input('month', now()->format('Y-m'));
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
            $month = $start->format('Y-m');
        }
        $end = $start->copy()->endOfMonth();

        $bookings = Booking::where('b2b_user_id', $agency->id)
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->with(['tour'])
            ->latest('booking_date')
            ->get();

        return view('admin.commissions.agency', [
            'agency' => $agency,
            'month' => $month,
            'start' => $start,
            'bookings' => $bookings,
        ]);
    }

    /** Marca la commissione di una prenotazione come pagata all'agenzia. */
    public function markPaid(Request $request, Booking $booking): RedirectResponse
    {
        $this->guard();
        abort_unless($booking->isB2b(), 404);

        if ($booking->commission_status === 'reversed') {
            return back()->with('warning', 'La commissione è stornata: non c\'è nulla da pagare.');
        }
        if ($booking->commission_paid) {
            return back()->with('info', 'Questa commissione risulta già pagata.');
        }

        $booking->update([
            'commission_paid' => true,
            'commission_paid_at' => now(),
        ]);

        BookingLog::info('b2b_commission_paid', 'Commissione segnata come pagata all\'agenzia', $booking, [
            'agency_id' => $booking->b2b_user_id,
            'amount' => $booking->commission_amount,
        ]);

        return back()->with('success', 'Commissione segnata come pagata.');
    }

    /** Annulla la marcatura "pagata" (in caso di errore). */
    public function unmarkPaid(Request $request, Booking $booking): RedirectResponse
    {
        $this->guard();
        abort_unless($booking->isB2b(), 404);

        $booking->update(['commission_paid' => false, 'commission_paid_at' => null]);
        BookingLog::info('b2b_commission_unpaid', 'Annullata marcatura pagamento commissione', $booking, [
            'agency_id' => $booking->b2b_user_id,
        ]);

        return back()->with('success', 'Marcatura pagamento annullata.');
    }
}
