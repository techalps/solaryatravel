<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartureAssignmentController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

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
     * Sposta in blocco i passeggeri selezionati su un altro catamarano.
     *
     * Caso operativo: "sposta tutti (o alcuni) i passeggeri di questa barca su
     * un'altra" — guasto, cambio scafo, riorganizzazione dei gruppi.
     *
     * La capienza è verificata sul TOTALE dei selezionati: se non ci stanno
     * tutti non si sposta nessuno, così il gruppo non resta diviso a metà.
     */
    public function moveSeatsBulk(Request $request, TourDeparture $departure): RedirectResponse
    {
        $validated = $request->validate([
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'integer|exists:booking_seats,id',
            'target_catamaran_id' => 'required|integer|exists:catamarans,id',
        ], [
            'seat_ids.required' => 'Seleziona almeno un passeggero da spostare.',
            'target_catamaran_id.required' => 'Scegli il catamarano di destinazione.',
        ]);

        // Carica SOLO i posti che appartengono davvero a questa partenza: evita
        // che un id manomesso sposti passeggeri di un'altra partenza.
        $seats = BookingSeat::with('booking.departure')
            ->whereIn('id', $validated['seat_ids'])
            ->whereNull('cancelled_at')
            ->whereHas('booking', fn ($q) => $q->where('tour_departure_id', $departure->id))
            ->get();

        if ($seats->isEmpty()) {
            return back()->with('error', 'Nessun passeggero valido fra quelli selezionati.');
        }

        $ignored = count($validated['seat_ids']) - $seats->count();

        try {
            $moved = $this->bookingService->moveSeatsBulk($seats, (int) $validated['target_catamaran_id']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($moved === 0) {
            return back()->with('info', 'I passeggeri selezionati erano già su quel catamarano.');
        }

        $target = Catamaran::find($validated['target_catamaran_id']);
        $message = sprintf(
            '%d %s spostati su %s.',
            $moved,
            $moved === 1 ? 'passeggero' : 'passeggeri',
            $target?->name ?? 'catamarano'
        );

        if ($ignored > 0) {
            $message .= sprintf(' %d selezione ignorata (non appartiene a questa partenza).', $ignored);
        }

        return back()->with('success', $message);
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
            ->reject(fn ($s) => $s->cancelled_at !== null) // posti disdetti non si assegnano
            ->sortBy(function ($s) {
                return ($s->booking?->booking_number ?? '') . '-' . str_pad((string) $s->seat_number, 4, '0', STR_PAD_LEFT);
            })
            ->values();

        // Posti delle prenotazioni a USO ESCLUSIVO il cui blocco copre questo giorno
        // ma la cui partenza è in un altro giorno del periodo: li mostriamo qui (in
        // sola lettura) così l'assegnazione è visibile per OGNI giorno del periodo.
        $spanningSeats = $this->spanningReservedSeats($departure);

        $allSeats = $seats->concat($spanningSeats);
        $byCatamaran = $allSeats->groupBy(fn ($s) => $s->catamaran_id ?? 0);

        // Catamarani riservati a USO ESCLUSIVO per questa partenza (blocco che copre
        // il giorno + numero prenotazione nel reason). Vale anche per i catamarani
        // SENZA posti assegnati (es. il 2° scafo di una riserva multi-catamarano):
        // devono comunque risultare riservati e non disponibili.
        $exclusiveByCatamaran = $this->exclusiveReservationsByCatamaran($departure);

        $stats = [];
        foreach ($catamarans as $cat) {
            $count = $byCatamaran->get($cat->id)?->count() ?? 0;
            $isExclusive = isset($exclusiveByCatamaran[$cat->id]);
            $stats[$cat->id] = [
                'count' => $count,
                'capacity' => (int) $cat->capacity,
                // Un catamarano in uso esclusivo non è disponibile per nuove assegnazioni.
                'free' => $isExclusive ? 0 : max(0, (int) $cat->capacity - $count),
                'exclusive' => $isExclusive,
            ];
        }

        $unassignedCount = $byCatamaran->get(0)?->count() ?? 0;

        return compact('catamarans', 'byCatamaran', 'stats', 'unassignedCount', 'exclusiveByCatamaran');
    }

    /**
     * Mappa catamaran_id => info riserva esclusiva per la data della partenza.
     * Una riserva esclusiva è un TourCatamaranBlock che copre il giorno e il cui
     * reason contiene il numero prenotazione ("... #SLY-..."). Restituisce numero
     * prenotazione e intestatario, così la vista mostra una riga sintetica anche
     * per i catamarani senza posti fisicamente assegnati.
     *
     * @return array<int, array{booking_number: string, holder: string, booking: \App\Models\Booking|null}>
     */
    protected function exclusiveReservationsByCatamaran(TourDeparture $departure): array
    {
        $day = $departure->departure_date instanceof \DateTimeInterface
            ? $departure->departure_date->format('Y-m-d')
            : (string) $departure->departure_date;

        $blocks = \App\Models\TourCatamaranBlock::query()
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->get();

        if ($blocks->isEmpty()) {
            return [];
        }

        // catamaran_id => booking_number (dal reason).
        $numberByCatamaran = [];
        $numbers = [];
        foreach ($blocks as $b) {
            if (preg_match('/#(\S+)/', (string) $b->reason, $m)) {
                $numberByCatamaran[(int) $b->catamaran_id] = $m[1];
                $numbers[] = $m[1];
            }
        }

        if (empty($numbers)) {
            return [];
        }

        // Intestatari delle prenotazioni coinvolte (escluse annullate/rimborsate/no-show).
        $bookings = \App\Models\Booking::query()
            ->whereIn('booking_number', array_unique($numbers))
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::REFUNDED, BookingStatus::NO_SHOW])
            ->get()
            ->keyBy('booking_number');

        $result = [];
        foreach ($numberByCatamaran as $catId => $number) {
            $booking = $bookings->get($number);
            if (! $booking) {
                continue; // prenotazione annullata: il blocco non è più una riserva attiva
            }
            $result[$catId] = [
                'booking_number' => $number,
                'holder' => $booking->customer_full_name,
                'booking' => $booking,
            ];
        }

        return $result;
    }

    /**
     * Posti delle prenotazioni a uso esclusivo il cui BLOCCO copre la data della
     * partenza ma la cui partenza effettiva è un altro giorno del periodo.
     * Restituisce i BookingSeat marcati (is_spanning, span_date) per la sola
     * visualizzazione: il catamarano è quello del blocco.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\BookingSeat>
     */
    protected function spanningReservedSeats(TourDeparture $departure): \Illuminate\Support\Collection
    {
        $day = $departure->departure_date instanceof \DateTimeInterface
            ? $departure->departure_date->format('Y-m-d')
            : (string) $departure->departure_date;

        // Blocchi (di qualsiasi tour: il catamarano è fisicamente occupato) che
        // coprono questo giorno e si estendono su PIÙ giorni (start != end).
        $blocks = \App\Models\TourCatamaranBlock::query()
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->whereColumn('start_date', '!=', 'end_date')
            ->get();

        if ($blocks->isEmpty()) {
            return collect();
        }

        // Ricava i numeri prenotazione dal campo reason ("... #SLY-...").
        $numbers = $blocks->map(function ($b) {
            return preg_match('/#(\S+)/', (string) $b->reason, $m) ? $m[1] : null;
        })->filter()->unique()->values();

        if ($numbers->isEmpty()) {
            return collect();
        }

        $catByNumber = []; // booking_number => [catamaran_id,...]
        foreach ($blocks as $b) {
            if (preg_match('/#(\S+)/', (string) $b->reason, $m)) {
                $catByNumber[$m[1]][] = (int) $b->catamaran_id;
            }
        }

        return \App\Models\Booking::query()
            ->whereIn('booking_number', $numbers)
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::REFUNDED, BookingStatus::NO_SHOW])
            // Escludi quelle la cui partenza È già questo giorno (già incluse sopra).
            ->where(fn ($q) => $q->whereHas('departure', fn ($d) => $d->whereDate('departure_date', '!=', $day)))
            ->with(['seatRecords.ageBracket', 'departure'])
            ->get()
            ->flatMap(function ($booking) use ($catByNumber, $day) {
                $allowedCats = $catByNumber[$booking->booking_number] ?? [];
                return $booking->seatRecords
                    ->reject(fn ($s) => $s->cancelled_at !== null)
                    ->filter(fn ($s) => in_array((int) $s->catamaran_id, $allowedCats, true))
                    ->map(function ($s) use ($booking) {
                        // Marcatori per la vista (sola lettura, niente spostamento).
                        $s->is_spanning = true;
                        $s->span_origin_date = $booking->booking_date;
                        return $s;
                    });
            })
            ->values();
    }
}
