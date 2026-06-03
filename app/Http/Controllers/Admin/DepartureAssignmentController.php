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
        $request->validate([
            'date' => 'nullable|date',
            'tour' => 'nullable|integer',
        ]);

        // Vista a intervallo: dalla data scelta (default oggi) per 14 giorni,
        // così si vedono tutte le partenze da assegnare, non solo quelle di un giorno.
        $from = $request->date('date') ?? Carbon::today();
        $to = $from->copy()->addDays(14)->endOfDay();
        $tourId = $request->integer('tour') ?: null;

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
            ->whereBetween('departure_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($tourId, fn ($q) => $q->where('tour_id', $tourId))
            ->whereHas('tour', fn ($q) => $q->where('is_active', true))
            ->orderBy('departure_date')
            ->orderBy('start_time')
            ->get();

        // Costruisce per ciascuna partenza i dati per il partial
        $blocks = $departures->map(function (TourDeparture $dep) {
            return [
                'departure' => $dep,
            ] + $this->buildAssignmentData($dep);
        });

        // Raggruppa prima per mese (Y-m) e poi per giorno (Y-m-d), così l'intestazione
        // del mese compare una sola volta. Ordine cronologico preservato.
        $byMonth = $blocks
            ->groupBy(fn ($b) => Carbon::parse($b['departure']->departure_date)->format('Y-m'))
            ->map(fn ($monthBlocks) => $monthBlocks->groupBy(
                fn ($b) => Carbon::parse($b['departure']->departure_date)->toDateString()
            ));

        $tours = Tour::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.departures.index', [
            'from' => $from,
            'byMonth' => $byMonth,
            'tours' => $tours,
            'selectedTour' => $tourId,
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
