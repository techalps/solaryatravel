<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourDeparture;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Listing pubblico dei tour, con search opzionale per data/persone.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'tour' => 'nullable|integer|exists:tours,id',
            'date' => 'nullable|date',
            'adults' => 'nullable|integer|min:0|max:50',
            'children' => 'nullable|integer|min:0|max:50',
        ]);

        $tourId = $validated['tour'] ?? null;
        $date = $validated['date'] ?? null;
        $adults = (int) ($validated['adults'] ?? 2);
        $children = (int) ($validated['children'] ?? 0);
        $guests = $adults + $children;
        $isSearch = $request->filled('tour') || $request->filled('date');

        $query = Tour::active()
            ->ordered()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')]);

        if ($tourId) {
            $query->where('id', $tourId);
        }

        $tours = $query->get();

        // Filtra per stagione se data specificata
        if ($date) {
            $tours = $tours->filter(function (Tour $t) use ($date) {
                if ($t->season_start && $date < $t->season_start->toDateString()) {
                    return false;
                }
                if ($t->season_end && $date > $t->season_end->toDateString()) {
                    return false;
                }
                return true;
            })->values();
        }

        // Pre-carica periodi per il "from" price (prezzo adulto = base_price del periodo)
        $tours->load('periods');

        $search = [
            'isSearch' => $isSearch,
            'date' => $date,
            'guests' => $guests,
            'tour_id' => $tourId,
            'results' => $tours->count(),
        ];

        $searchTours = Tour::active()->ordered()->get(['id', 'name']);
        $tourSearch = [
            'tour' => $tourId,
            'date' => $date,
            'adults' => $adults,
            'children' => $children,
        ];
        $minBookingDate = \Carbon\Carbon::now()
            ->addHours(config('booking.advance_hours', 24))
            ->toDateString();

        return view('tours.index', compact('tours', 'search', 'searchTours', 'tourSearch', 'minBookingDate'));
    }

    public function show(string $slug, Request $request): View
    {
        $tour = Tour::active()->where('slug', $slug)
            ->with(['images', 'ageBrackets', 'periods.ageBrackets', 'catamarans'])
            ->firstOrFail();

        // Prossime partenze (180 giorni) generate dai periodi.
        // Vengono raggruppate per data per alimentare il calendario di prenotazione.
        $departures = app(\App\Services\DepartureGeneratorService::class)
            ->upcoming($tour, 180);

        $departuresByDate = $departures
            ->groupBy('date')
            ->map(fn ($items) => $items->pluck('time')->unique()->values()->all())
            ->all();

        // Tour simili
        $similar = Tour::active()
            ->where('id', '!=', $tour->id)
            ->ordered()
            ->with(['images' => fn ($q) => $q->where('is_primary', true), 'periods'])
            ->take(3)
            ->get();

        return view('tours.show', compact('tour', 'departures', 'departuresByDate', 'similar'));
    }

    /**
     * Endpoint AJAX: posti disponibili per una partenza.
     */
    public function checkDeparture(TourDeparture $departure, Request $request)
    {
        $request->validate(['seats' => 'required|integer|min:1|max:200']);
        $result = $this->bookingService->checkAvailability($departure, (int) $request->seats);
        return response()->json($result + [
            'departure_id' => $departure->id,
            'seats_available' => $departure->seats_available,
        ]);
    }
}
