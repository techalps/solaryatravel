<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourDeparture;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartureAssignmentController extends Controller
{
    /**
     * Indice: tutte le partenze attive della data scelta, raggruppate per tour.
     * Ogni partenza è già completa dei dati di assegnazione catamarani inline.
     */
    public function index(Request $request): View
    {
        $request->validate(['date' => 'nullable|date']);
        $date = $request->date('date') ?? Carbon::today();
        $dateString = $date->toDateString();

        $departures = TourDeparture::query()
            ->with([
                'tour',
                'tour.catamarans',
                'bookings' => fn ($q) => $q->whereNotIn('status', [
                    BookingStatus::CANCELLED,
                    BookingStatus::REFUNDED,
                    BookingStatus::NO_SHOW,
                ]),
                'bookings.seatRecords.ageBracket',
                'bookings.seatRecords.catamaran',
            ])
            ->whereDate('departure_date', $dateString)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereHas('tour', fn ($q) => $q->where('is_active', true))
            ->orderBy('start_time')
            ->get();

        // Costruisce per ciascuna partenza i dati per il partial
        $blocks = $departures->map(function (TourDeparture $dep) {
            return [
                'departure' => $dep,
            ] + $this->buildAssignmentData($dep);
        });

        // Raggruppa per tour mantenendo l'ordine cronologico delle partenze
        $byTour = $blocks->groupBy(fn ($b) => $b['departure']->tour_id);

        return view('admin.departures.index', [
            'date' => $date,
            'blocks' => $blocks,
            'byTour' => $byTour,
        ]);
    }

    /**
     * Pagina admin: assegnazione/spostamento passeggeri tra i catamarani
     * di una specifica partenza.
     */
    public function show(TourDeparture $departure): View
    {
        $departure->load([
            'tour',
            'bookings' => fn ($q) => $q->whereNotIn('status', [
                BookingStatus::CANCELLED,
                BookingStatus::REFUNDED,
                BookingStatus::NO_SHOW,
            ]),
            'bookings.seatRecords.ageBracket',
            'bookings.seatRecords.catamaran',
        ]);

        $data = $this->buildAssignmentData($departure);

        return view('admin.departures.assignments', array_merge(
            ['departure' => $departure],
            $data,
        ));
    }

    /**
     * Calcola catamarani operativi, raggruppamento posti e statistiche per una partenza.
     * Si aspetta che $departure abbia già caricato 'tour' e 'bookings.seatRecords'.
     *
     * @return array{catamarans: \Illuminate\Support\Collection, byCatamaran: \Illuminate\Support\Collection, stats: array, unassignedCount: int}
     */
    protected function buildAssignmentData(TourDeparture $departure): array
    {
        $catamarans = $departure->tour
            ->operatingCatamarans()
            ->filter(fn ($c) => $c->isAvailableOn($departure->departure_date))
            ->values();

        $seats = $departure->bookings
            ->flatMap->seatRecords
            ->sortBy(function ($s) {
                return ($s->booking?->booking_number ?? '') . '-' . str_pad((string) $s->seat_number, 4, '0', STR_PAD_LEFT);
            })
            ->values();

        $byCatamaran = $seats->groupBy(fn ($s) => $s->catamaran_id ?? 0);

        $stats = [];
        foreach ($catamarans as $cat) {
            $count = $byCatamaran->get($cat->id)?->count() ?? 0;
            $stats[$cat->id] = [
                'count' => $count,
                'capacity' => (int) $cat->capacity,
                'free' => max(0, (int) $cat->capacity - $count),
            ];
        }

        $unassignedCount = $byCatamaran->get(0)?->count() ?? 0;

        return compact('catamarans', 'byCatamaran', 'stats', 'unassignedCount');
    }
}
