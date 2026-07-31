<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Payment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Ricavi = prenotazioni in stato incassato/confermato (qualsiasi canale,
        // incluse le retroattive/manuali senza pagamento Stripe), per data partenza.
        // Coerente con i Report (vedi ReportController).
        $revenueStatuses = BookingStatus::revenueStatusValues();

        // Oggi: partenze di oggi (competenza) e prenotazioni ricevute oggi
        // (raccolta). Prima esisteva solo la prima, ma l'etichetta diceva
        // "Prenotazioni oggi" facendo pensare alla seconda.
        $todayStats = [
            'departures' => Booking::whereDate('booking_date', today())
                ->whereIn('status', $revenueStatuses)
                ->count(),
            'guests' => Booking::whereDate('booking_date', today())
                ->whereIn('status', BookingStatus::boardableStatuses())
                ->sum('seats'),
            // Valore delle partenze di oggi (dovuto, non cassa).
            'revenue' => Booking::whereDate('booking_date', today())
                ->whereIn('status', $revenueStatuses)
                ->sum('total_amount'),
            // Prenotazioni ARRIVATE oggi, a prescindere da quando si parte.
            'booked' => Booking::whereDate('created_at', today())
                ->whereIn('status', $revenueStatuses)
                ->count(),
            'booked_value' => Booking::whereDate('created_at', today())
                ->whereIn('status', $revenueStatuses)
                ->sum('total_amount'),
        ];

        // Mese: le due viste affiancate, stessa finestra usata dai Report.
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthViews = \App\Support\ReportCriteria::bothViews($monthStart, $monthEnd);

        $monthlyStats = [
            // Conteggio sui soli stati che fanno ricavo: prima includeva anche
            // annullate e rimborsate, a differenza degli altri due riquadri.
            'bookings' => $monthViews['competenza']['bookings'],
            'revenue' => $monthViews['competenza']['gross'],
            'collected' => $monthViews['competenza']['collected'],
            'avg_booking_value' => $monthViews['competenza']['avg'],
        ];

        // Pending bookings
        $pendingBookings = Booking::where('status', BookingStatus::PENDING)
            ->with(['tour', 'departure'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Today's bookings
        $todayBookings = Booking::whereDate('booking_date', today())
            ->with(['tour', 'departure'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Grafico 7 giorni: incassi REALI per data di pagamento (payments.paid_at).
        // Prima mostrava il valore delle partenze, quindi era la curva delle
        // uscite passate e non diceva nulla su quanto stava entrando in cassa.
        $weeklyCash = Payment::where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('paid_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Curva di confronto: quanto è stato venduto (prenotato) negli stessi giorni.
        $weeklyBooked = Booking::whereIn('status', $revenueStatuses)
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $chartLabels = [];
        $chartData = [];
        $chartBooked = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->locale('it')->isoFormat('ddd D');
            $chartData[] = (float) ($weeklyCash[$date] ?? 0);
            $chartBooked[] = (float) ($weeklyBooked[$date] ?? 0);
        }

        // Bookings by status
        $bookingsByStatus = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Popular catamarans
        $popularCatamarans = Catamaran::withCount(['bookings' => function ($query) use ($monthStart, $monthEnd) {
            $query->whereBetween('booking_date', [$monthStart, $monthEnd]);
        }])
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get();

        // Recent activity
        $recentActivity = collect();

        // Add recent bookings
        $recentBookings = Booking::with('tour')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'type' => 'booking',
                    'message' => "Nuova prenotazione #{$booking->booking_number}",
                    'details' => "{$booking->customer_first_name} {$booking->customer_last_name} - " . ($booking->tour?->name ?? 'N/A'),
                    'time' => $booking->created_at,
                    'icon' => 'calendar',
                    'color' => 'blue',
                ];
            });

        // Add recent payments
        $recentPayments = Payment::with('booking')
            ->where('status', PaymentStatus::SUCCEEDED)
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment',
                    'message' => "Pagamento ricevuto €" . number_format($payment->amount, 2),
                    'details' => "Prenotazione #{$payment->booking->booking_number}",
                    'time' => $payment->paid_at,
                    'icon' => 'credit-card',
                    'color' => 'green',
                ];
            });

        $recentActivity = $recentBookings->merge($recentPayments)
            ->sortByDesc('time')
            ->take(10)
            ->values();

        return view('admin.dashboard', compact(
            'todayStats',
            'monthlyStats',
            'monthViews',
            'pendingBookings',
            'todayBookings',
            'chartLabels',
            'chartData',
            'chartBooked',
            'bookingsByStatus',
            'popularCatamarans',
            'recentActivity'
        ));
    }

    /**
     * Show the schedule/calendar view.
     */
    public function schedule(): View
    {
        $rangeStart = now()->startOfMonth();
        $rangeEnd = now()->endOfMonth()->addMonth();

        $bookingsList = Booking::with(['tour', 'departure', 'seatRecords.catamaran'])
            ->whereBetween('booking_date', [$rangeStart, $rangeEnd])
            ->where('status', '!=', BookingStatus::CANCELLED)
            ->get();

        // Periodi di blocco (uso esclusivo) per le prenotazioni in vista: così un
        // evento multi-giorno copre TUTTI i giorni del periodo, non solo la partenza.
        $blockByNumber = [];
        if ($bookingsList->isNotEmpty()) {
            $numbers = $bookingsList->pluck('booking_number')->filter()->all();
            $blocks = \App\Models\TourCatamaranBlock::where(function ($q) use ($numbers) {
                foreach ($numbers as $n) {
                    $q->orWhere('reason', 'like', '%#' . $n . '%');
                }
                $q->orWhereRaw('1 = 0');
            })->get();
            foreach ($numbers as $n) {
                $blockByNumber[$n] = $blocks->first(fn ($b) => $b->reason && str_contains($b->reason, '#' . $n));
            }
        }

        $bookings = $bookingsList->map(function ($booking) use ($blockByNumber) {
                // Catamarani distinti assegnati ai posti della prenotazione (di norma uno).
                $catamarans = $booking->seatRecords
                    ->pluck('catamaran.name')
                    ->filter()
                    ->unique()
                    ->values();

                $blk = $blockByNumber[$booking->booking_number] ?? null;

                if ($blk) {
                    // Uso esclusivo: l'evento copre l'intero periodo (partenza → ritorno).
                    // FullCalendar tratta 'end' come esclusivo, quindi aggiungiamo 1 giorno.
                    $startT = $blk->start_time ? \Carbon\Carbon::parse($blk->start_time)->format('H:i:s') : '09:00:00';
                    $endT = $blk->end_time ? \Carbon\Carbon::parse($blk->end_time)->format('H:i:s') : '18:00:00';
                    $start = $blk->start_date->format('Y-m-d') . 'T' . $startT;
                    // se stesso giorno, fine = stessa data + ora fine; se multi-giorno, fine = end_date + ora fine
                    $end = $blk->end_date->format('Y-m-d') . 'T' . $endT;
                } else {
                    $start = $booking->booking_date->format('Y-m-d') . 'T' . ($booking->departure?->start_time ?? '09:00');
                    $end = $booking->booking_date->format('Y-m-d') . 'T' . ($booking->departure?->end_time ?? '17:00');
                }

                return [
                    'id' => $booking->id,
                    'title' => "{$booking->customer_first_name} {$booking->customer_last_name}",
                    'start' => $start,
                    'end' => $end,
                    'color' => $this->getBookingColor($booking->status),
                    'extendedProps' => [
                        'booking_number' => $booking->booking_number,
                        'tour' => $booking->tour?->name ?? 'N/A',
                        'tour_id' => $booking->tour_id,
                        'guests' => $booking->seats,
                        'catamaran' => $catamarans->isNotEmpty() ? $catamarans->implode(', ') : null,
                        'status' => $booking->status->value ?? $booking->status,
                        'exclusive' => (bool) $blk,
                    ],
                ];
            });

        $catamarans = Catamaran::where('is_active', true)->get();

        return view('admin.schedule', compact('bookings', 'catamarans'));
    }

    /**
     * Get color based on booking status.
     */
    private function getBookingColor(BookingStatus|string $status): string
    {
        $statusValue = $status instanceof BookingStatus ? $status->value : $status;
        
        return match ($statusValue) {
            'pending' => '#f59e0b',
            'confirmed' => '#10b981',
            'completed' => '#3b82f6',
            'cancelled' => '#ef4444',
            'no_show' => '#6b7280',
            default => '#8b5cf6',
        };
    }
}
