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

        // Today's stats
        $todayStats = [
            'bookings' => Booking::whereDate('booking_date', today())->count(),
            'guests' => Booking::whereDate('booking_date', today())
                ->where('status', '!=', BookingStatus::CANCELLED)
                ->sum('seats'),
            'revenue' => Booking::whereDate('booking_date', today())
                ->whereIn('status', $revenueStatuses)
                ->sum('total_amount'),
        ];

        // Monthly stats
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthlyStats = [
            'bookings' => Booking::whereBetween('booking_date', [$monthStart, $monthEnd])->count(),
            'revenue' => Booking::whereBetween('booking_date', [$monthStart, $monthEnd])
                ->whereIn('status', $revenueStatuses)
                ->sum('total_amount'),
            'avg_booking_value' => Booking::whereBetween('booking_date', [$monthStart, $monthEnd])
                ->whereIn('status', $revenueStatuses)
                ->avg('total_amount') ?? 0,
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

        // Weekly revenue chart data (stessa definizione di ricavo: prenotazioni
        // incassate/confermate per data partenza).
        $weeklyRevenue = Booking::whereIn('status', $revenueStatuses)
            ->whereBetween('booking_date', [now()->subDays(7)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(booking_date) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->locale('it')->isoFormat('ddd');
            $chartData[] = $weeklyRevenue[$date] ?? 0;
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
            'pendingBookings',
            'todayBookings',
            'chartLabels',
            'chartData',
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
        $bookings = Booking::with(['tour', 'departure', 'seatRecords.catamaran'])
            ->whereBetween('booking_date', [now()->startOfMonth(), now()->endOfMonth()->addMonth()])
            ->where('status', '!=', BookingStatus::CANCELLED)
            ->get()
            ->map(function ($booking) {
                // Catamarani distinti assegnati ai posti della prenotazione (di norma uno).
                $catamarans = $booking->seatRecords
                    ->pluck('catamaran.name')
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'id' => $booking->id,
                    'title' => "{$booking->customer_first_name} {$booking->customer_last_name}",
                    'start' => $booking->booking_date->format('Y-m-d') . 'T' . ($booking->departure?->start_time ?? '09:00'),
                    'end' => $booking->booking_date->format('Y-m-d') . 'T' . ($booking->departure?->end_time ?? '17:00'),
                    'color' => $this->getBookingColor($booking->status),
                    'extendedProps' => [
                        'booking_number' => $booking->booking_number,
                        'tour' => $booking->tour?->name ?? 'N/A',
                        'tour_id' => $booking->tour_id,
                        'guests' => $booking->seats,
                        'catamaran' => $catamarans->isNotEmpty() ? $catamarans->implode(', ') : null,
                        'status' => $booking->status->value ?? $booking->status,
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
