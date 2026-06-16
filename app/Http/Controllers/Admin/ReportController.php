<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->input('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // Ricavi = prenotazioni in stato incassato/confermato (qualsiasi canale,
        // incluse le retroattive/manuali senza pagamento Stripe), per data partenza.
        $revenueStatuses = BookingStatus::revenueStatusValues();

        $revenue = Booking::whereIn('status', $revenueStatuses)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->sum('total_amount');

        $previousRevenue = Booking::whereIn('status', $revenueStatuses)
            ->whereBetween('booking_date', [$startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate])
            ->sum('total_amount');

        $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        $confirmedBookings = Booking::where('status', BookingStatus::CONFIRMED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalPassengers = Booking::whereBetween('created_at', [$startDate, $endDate])->sum('seats');
        $avgBookingValue = Booking::whereBetween('created_at', [$startDate, $endDate])->avg('total_amount') ?? 0;

        $topTours = Tour::withCount(['bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate]);
        }])
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get();

        $revenueByDay = Booking::whereIn('status', $revenueStatuses)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('DATE(booking_date) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $bookingsByStatus = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('admin.reports.index', compact(
            'period', 'startDate', 'endDate',
            'revenue', 'previousRevenue',
            'totalBookings', 'confirmedBookings', 'totalPassengers', 'avgBookingValue',
            'topTours', 'revenueByDay', 'bookingsByStatus'
        ));
    }

    public function revenue(Request $request): View
    {
        $period = $request->input('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // Ricavi = prenotazioni in stato incassato/confermato (qualsiasi canale,
        // incluse le retroattive/manuali), aggregati per data partenza.
        $revenueStatuses = BookingStatus::revenueStatusValues();

        // "transactions" qui = numero di prenotazioni che fanno ricavo nel giorno.
        $dailyRevenue = Booking::whereIn('status', $revenueStatuses)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('DATE(booking_date) as date, SUM(total_amount) as total, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $revenueByTour = Booking::with('tour')
            ->whereIn('status', $revenueStatuses)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('tour_id, SUM(total_amount) as total')
            ->groupBy('tour_id')
            ->orderByDesc('total')
            ->get();

        // La ripartizione per canale di incasso resta basata sui pagamenti reali
        // (Stripe/bonifico/ecc.). Le prenotazioni manuali senza pagamento confluiscono
        // in una voce "manuale/registrato" così il dato resta leggibile.
        $revenueByGateway = Payment::where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('gateway, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('gateway')
            ->get();

        $manualRevenue = Booking::whereIn('status', $revenueStatuses)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', PaymentStatus::SUCCEEDED))
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('SUM(total_amount) as total, COUNT(*) as count')
            ->first();
        if ($manualRevenue && (float) $manualRevenue->total > 0) {
            $revenueByGateway->push((object) [
                'gateway' => 'manuale',
                'total' => (float) $manualRevenue->total,
                'count' => (int) $manualRevenue->count,
            ]);
            $revenueByGateway = $revenueByGateway->sortByDesc('total')->values();
        }

        $monthlyRevenue = Booking::whereIn('status', $revenueStatuses)
            ->whereYear('booking_date', now()->year)
            ->selectRaw('MONTH(booking_date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $stats = [
            'total' => Booking::whereIn('status', $revenueStatuses)
                ->whereBetween('booking_date', [$startDate, $endDate])->sum('total_amount'),
            'transactions' => Booking::whereIn('status', $revenueStatuses)
                ->whereBetween('booking_date', [$startDate, $endDate])->count(),
            'avg_transaction' => Booking::whereIn('status', $revenueStatuses)
                ->whereBetween('booking_date', [$startDate, $endDate])->avg('total_amount') ?? 0,
            // I rimborsi restano un dato di cassa reale (pagamenti rimborsati).
            'refunds' => Payment::where('status', PaymentStatus::REFUNDED)
                ->whereBetween('created_at', [$startDate, $endDate])->sum('amount'),
        ];

        return view('admin.reports.revenue', compact(
            'period', 'startDate', 'endDate',
            'dailyRevenue', 'revenueByTour', 'revenueByGateway', 'monthlyRevenue', 'stats'
        ));
    }

    public function bookings(Request $request): View
    {
        $period = $request->input('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $dailyBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(seats) as passengers')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $bookingsByTour = Booking::with('tour')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('tour_id, COUNT(*) as total, SUM(seats) as passengers')
            ->groupBy('tour_id')
            ->orderByDesc('total')
            ->get();

        $bookingsByStatus = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status->value => $item->count]);

        $bookingsByTimeSlot = Booking::with('departure')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotNull('tour_departure_id')
            ->selectRaw('tour_departure_id, COUNT(*) as count')
            ->groupBy('tour_departure_id')
            ->orderByDesc('count')
            ->limit(12)
            ->get()
            ->map(function ($item) {
                $time = $item->departure?->start_time;
                $item->time_slot = $time ? Carbon::parse($time)->format('H:i') : 'N/A';
                return $item;
            });

        $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        $cancelledBookings = Booking::where('status', BookingStatus::CANCELLED)
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $cancellationRate = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 1) : 0;

        $stats = [
            'total' => $totalBookings,
            'confirmed' => Booking::where('status', BookingStatus::CONFIRMED)
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed' => Booking::where('status', BookingStatus::COMPLETED)
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'cancelled' => $cancelledBookings,
            'passengers' => Booking::whereBetween('created_at', [$startDate, $endDate])->sum('seats'),
            'avg_passengers' => Booking::whereBetween('created_at', [$startDate, $endDate])->avg('seats') ?? 0,
            'cancellation_rate' => $cancellationRate,
        ];

        return view('admin.reports.bookings', compact(
            'period', 'startDate', 'endDate',
            'dailyBookings', 'bookingsByTour', 'bookingsByStatus', 'bookingsByTimeSlot', 'stats'
        ));
    }

    public function occupancy(Request $request): View
    {
        $period = $request->input('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $tours = Tour::where('is_active', true)->get();

        $occupancyData = [];
        foreach ($tours as $tour) {
            $bookings = Booking::where('tour_id', $tour->id)
                ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->get();

            $totalPassengers = $bookings->sum('seats');
            $totalSlots = $bookings->count();
            $distinctDepartures = $bookings->pluck('tour_departure_id')->filter()->unique()->count();
            $capacityPerSlot = (int) ($tour->max_capacity ?? 0);
            $maxCapacity = $capacityPerSlot * max($distinctDepartures, 1);

            $occupancyData[] = [
                'tour' => $tour,
                'bookings' => $totalSlots,
                'passengers' => $totalPassengers,
                'max_capacity' => $maxCapacity,
                'occupancy_rate' => $maxCapacity > 0 ? round(($totalPassengers / $maxCapacity) * 100, 1) : 0,
                'avg_passengers' => $totalSlots > 0 ? round($totalPassengers / $totalSlots, 1) : 0,
            ];
        }

        usort($occupancyData, fn($a, $b) => $b['occupancy_rate'] <=> $a['occupancy_rate']);

        $dailyOccupancy = Booking::whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('DATE(booking_date) as date, SUM(seats) as passengers, COUNT(*) as bookings')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $timeSlotPopularity = Booking::with('departure')
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotNull('tour_departure_id')
            ->selectRaw('tour_departure_id, COUNT(*) as count, SUM(seats) as passengers')
            ->groupBy('tour_departure_id')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(function ($item) {
                $time = $item->departure?->start_time;
                $item->time_slot = $time ? Carbon::parse($time)->format('H:i') : 'N/A';
                return $item;
            });

        $dayOfWeekStats = Booking::whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('DAYOFWEEK(booking_date) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        $stats = [
            'total_capacity_used' => array_sum(array_column($occupancyData, 'passengers')),
            'total_max_capacity' => array_sum(array_column($occupancyData, 'max_capacity')),
            'avg_occupancy' => count($occupancyData) > 0
                ? round(array_sum(array_column($occupancyData, 'occupancy_rate')) / count($occupancyData), 1)
                : 0,
            'busiest_day' => !empty($dayOfWeekStats) ? $this->getDayName(array_search(max($dayOfWeekStats), $dayOfWeekStats)) : '-',
        ];

        return view('admin.reports.occupancy', compact(
            'period', 'startDate', 'endDate',
            'occupancyData', 'dailyOccupancy', 'timeSlotPopularity', 'dayOfWeekStats', 'stats'
        ));
    }

    public function export(Request $request): Response
    {
        $type = $request->input('type', 'bookings');
        $period = $request->input('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $data = match($type) {
            'bookings' => $this->getBookingsExportData($startDate, $endDate),
            'revenue' => $this->getRevenueExportData($startDate, $endDate),
            'passengers' => $this->getPassengersExportData($startDate, $endDate),
            default => $this->getBookingsExportData($startDate, $endDate),
        };

        return $this->exportToCsv($data, $type);
    }

    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            'all' => Carbon::createFromDate(2020, 1, 1),
            default => now()->startOfMonth(),
        };
    }

    private function getDayName(int $day): string
    {
        return [
            1 => 'Domenica', 2 => 'Lunedì', 3 => 'Martedì', 4 => 'Mercoledì',
            5 => 'Giovedì', 6 => 'Venerdì', 7 => 'Sabato',
        ][$day] ?? '-';
    }

    private function getBookingsExportData(Carbon $startDate, Carbon $endDate): array
    {
        $bookings = Booking::with(['tour', 'user', 'departure'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [['Numero', 'Data Prenotazione', 'Data Escursione', 'Orario', 'Tour', 'Cliente', 'Email', 'Posti', 'Totale', 'Stato']];

        foreach ($bookings as $booking) {
            $time = $booking->departure?->start_time;
            $data[] = [
                $booking->booking_number,
                $booking->created_at->format('d/m/Y H:i'),
                $booking->booking_date?->format('d/m/Y') ?? '-',
                $time ? Carbon::parse($time)->format('H:i') : '-',
                $booking->tour->name ?? '-',
                $booking->user->name ?? trim($booking->customer_first_name . ' ' . $booking->customer_last_name),
                $booking->user->email ?? $booking->customer_email,
                $booking->seats,
                number_format($booking->total_amount, 2, ',', '.'),
                $booking->status->value,
            ];
        }

        return $data;
    }

    private function getRevenueExportData(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::with('booking')
            ->where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [['Data', 'Prenotazione', 'Gateway', 'Importo', 'Payment Intent']];

        foreach ($payments as $payment) {
            $data[] = [
                $payment->created_at->format('d/m/Y H:i'),
                $payment->booking->booking_number ?? '-',
                $payment->gateway,
                number_format($payment->amount, 2, ',', '.'),
                $payment->gateway_payment_intent ?? '-',
            ];
        }

        return $data;
    }

    private function getPassengersExportData(Carbon $startDate, Carbon $endDate): array
    {
        $bookings = Booking::with(['tour', 'user', 'departure'])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->orderBy('booking_date', 'desc')
            ->get();

        $data = [['Data', 'Orario', 'Tour', 'Prenotazione', 'Cliente', 'Posti Prenotati']];

        foreach ($bookings as $booking) {
            $time = $booking->departure?->start_time;
            $data[] = [
                $booking->booking_date?->format('d/m/Y') ?? '-',
                $time ? Carbon::parse($time)->format('H:i') : '-',
                $booking->tour->name ?? '-',
                $booking->booking_number,
                $booking->user->name ?? trim($booking->customer_first_name . ' ' . $booking->customer_last_name),
                $booking->seats,
            ];
        }

        return $data;
    }

    private function exportToCsv(array $data, string $type): Response
    {
        $filename = "{$type}_export_" . now()->format('Y-m-d_His') . ".csv";

        $content = '';
        foreach ($data as $row) {
            $content .= implode(';', array_map(fn($cell) => '"' . str_replace('"', '""', $cell) . '"', $row)) . "\n";
        }

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($content));
    }
}
