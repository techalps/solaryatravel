<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Http\Controllers\Controller;
use App\Mail\BookingCancelled;
use App\Mail\BookingPaymentLink;
use App\Mail\BookingRefunded;
use App\Mail\BookingTickets;
use App\Mail\AdminBookingCancelled;
use App\Mail\AdminBookingRefunded;
use App\Support\BookingLog;
use App\Support\Settings;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $query = Booking::with(['tour', 'departure', 'payments', 'b2bUser']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tour')) {
            $query->where('tour_id', $request->tour);
        }
        // Filtro per agenzia (canale B2B). 'none' = solo vendite dirette (senza agenzia).
        if ($request->filled('agency')) {
            if ($request->agency === 'none') {
                $query->whereNull('b2b_user_id');
            } else {
                $query->where('b2b_user_id', $request->agency);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%{$s}%")
                    ->orWhere('customer_first_name', 'like', "%{$s}%")
                    ->orWhere('customer_last_name', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%");
            });
        }

        // Ordinamento: colonne consentite (chiave UI => colonna/espressione DB).
        // Default: per numero prenotazione (decrescente).
        $sortable = [
            'number'   => 'booking_number',
            'customer' => 'customer_last_name',
            'tour'     => 'tour_id',
            'date'     => 'booking_date',
            'seats'    => 'seats',
            'total'    => 'total_amount',
            'status'   => 'status',
            'created'  => 'created_at',
        ];
        $sort = $request->input('sort', 'number');
        if (! array_key_exists($sort, $sortable)) {
            $sort = 'number';
        }
        $dir = strtolower($request->input('dir', $sort === 'number' ? 'desc' : 'asc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortable[$sort], $dir);
        // Ordinamento secondario stabile.
        if ($sort !== 'number') {
            $query->orderBy('booking_number', 'desc');
        }

        $bookings = $query->paginate(20)->withQueryString();

        // Blocchi a uso esclusivo delle prenotazioni in pagina (per mostrare
        // andata/ritorno con gli orari reali). Mappa: booking_number => blocco.
        $numbers = $bookings->pluck('booking_number')->filter()->all();
        $blockByBooking = collect();
        if (! empty($numbers)) {
            $blocks = \App\Models\TourCatamaranBlock::where(function ($q) use ($numbers) {
                foreach ($numbers as $n) {
                    $q->orWhere('reason', 'like', '%#' . $n . '%');
                }
            })->get();
            foreach ($numbers as $n) {
                $blockByBooking[$n] = $blocks->first(fn ($b) => $b->reason && str_contains($b->reason, '#' . $n));
            }
        }

        $tours = Tour::orderBy('name')->get();
        $statuses = BookingStatus::cases();
        $stats = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
        // Agenzie B2B per il filtro (solo quelle che hanno almeno una prenotazione).
        $agencies = \App\Models\User::where('role', 'b2b')
            ->whereIn('id', Booking::whereNotNull('b2b_user_id')->distinct()->pluck('b2b_user_id'))
            ->orderBy('agency_name')
            ->get(['id', 'agency_name', 'name']);

        return view('admin.bookings.index', compact('bookings', 'tours', 'statuses', 'stats', 'sort', 'dir', 'blockByBooking', 'agencies'));
    }

    /**
     * Form admin per creare una nuova prenotazione manualmente.
     */
    public function create(Request $request): View
    {
        $tours = Tour::with('ageBrackets')->orderBy('name')->get();

        $selectedTour = null;
        $departures = collect();

        if ($request->filled('tour_id')) {
            $selectedTour = $tours->firstWhere('id', (int) $request->tour_id);
            if ($selectedTour) {
                // Admin: tutte le partenze (anche passate) per consentire
                // l'inserimento retroattivo. Più recenti in cima.
                $departures = $selectedTour->departures()
                    ->orderByDesc('departure_date')
                    ->orderBy('start_time')
                    ->get();
            }
        }

        $statuses = BookingStatus::cases();
        $depositEnabled = \App\Support\Settings::depositEnabled();
        $depositPercentage = \App\Support\Settings::depositPercentage();
        $bankTransferEnabled = \App\Support\Settings::bankTransferEnabled();
        $balanceDueHours = \App\Support\Settings::balanceDueHours();
        // Agenzie B2B a cui l'admin può attribuire la prenotazione.
        $b2bAgencies = \App\Models\User::where('role', 'b2b')
            ->orderBy('agency_name')
            ->get(['id', 'agency_name', 'name', 'commission_rate']);

        return view('admin.bookings.create', compact(
            'tours', 'selectedTour', 'departures', 'statuses',
            'depositEnabled', 'depositPercentage', 'bankTransferEnabled', 'balanceDueHours', 'b2bAgencies'
        ));
    }

    /**
     * JSON: partenze future di un tour (per popolamento dinamico del form).
     */
    public function departuresJson(
        Tour $tour,
        \App\Services\PricingService $pricing,
        \App\Services\DepartureGeneratorService $generator
    ) {
        // Stesse date prenotabili del frontend: vengono generate dai PERIODI del tour
        // (giorni operativi + orari), non solo dalle righe già materializzate.
        // In più, per l'admin includiamo il passato (retroattivo) e uniamo le
        // tour_departures già esistenti (anche fuori dai periodi, es. partenze ad hoc).
        $from = now()->subMonths(12)->startOfDay();
        $to = now()->addDays(60)->endOfDay();

        // 1) Partenze virtuali dai periodi (incl. passato).
        $virtual = $generator->generate($tour, $from->copy(), $to->copy(), includePast: true);

        // Indicizza per (data, orario) per evitare duplicati con quelle materializzate.
        $byKey = [];
        $durationMin = (int) round(($tour->duration_hours ?? 1) * 60);

        foreach ($virtual as $v) {
            $start = strlen($v['time']) === 5 ? $v['time'] . ':00' : $v['time'];
            $key = $v['date'] . ' ' . substr($start, 0, 5);
            $byKey[$key] = [
                'departure_date' => $v['date'],
                'start_time' => $start,
            ];
        }

        // 2) Unisci le partenze già presenti a DB (sovrascrivono la virtuale di pari chiave).
        foreach ($tour->departures()->whereDate('departure_date', '>=', $from->toDateString())->get() as $d) {
            $key = \Carbon\Carbon::parse($d->departure_date)->format('Y-m-d')
                . ' ' . \Carbon\Carbon::parse($d->start_time)->format('H:i');
            $byKey[$key] = ['model' => $d];
        }

        // 3) Costruisci il payload SENZA creare righe: le virtuali restano tali
        //    (id sintetico "virt:Y-m-d:H:i") e verranno materializzate solo al
        //    salvataggio (vedi store()). Le già esistenti usano il loro id reale.
        $departures = collect($byKey)->map(function ($entry, $key) use ($tour, $pricing) {
            $d = $entry['model'] ?? null;

            // Data/orario della partenza (dal modello reale o dalla virtuale).
            $isoDate = $d
                ? \Carbon\Carbon::parse($d->departure_date)->format('Y-m-d')
                : $entry['departure_date'];
            $time = $d
                ? \Carbon\Carbon::parse($d->start_time)->format('H:i')
                : substr($entry['start_time'], 0, 5);

            $period = $pricing->resolvePeriod($tour, $isoDate);
            $modifier = $d ? (float) $d->price_modifier : 1.0;

            $brackets = $pricing->resolveBrackets($tour, $isoDate)
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'label' => $b->label,
                    'price' => round((float) $b->price * $modifier, 2),
                    'counts_as_seat' => (bool) $b->counts_as_seat,
                    'range_label' => $b->range_label,
                    'min_age' => (int) ($b->min_age ?? 0),
                    'max_age' => $b->max_age !== null ? (int) $b->max_age : null,
                ])->values();

            // Catamarani selezionabili per questa data: operativi, non bloccati,
            // disponibili, con i posti liberi (se la partenza esiste già).
            // Blocco GLOBALE per catamarano + per fascia oraria della partenza:
            // due slot disgiunti nello stesso giorno non si bloccano a vicenda.
            $winEnd = $d && $d->end_time
                ? \Carbon\Carbon::parse($d->end_time)->format('H:i')
                : \Carbon\Carbon::parse($time)->addMinutes((int) round(((float) ($tour->duration_hours ?? 1)) * 60))->format('H:i');
            $blockedIds = \App\Models\TourCatamaranBlock::blockedCatamaranIdsOn($isoDate, $time, $winEnd);

            $catamarans = $tour->operatingCatamarans()
                ->filter(fn ($cat) => !in_array((int) $cat->id, $blockedIds, true) && $cat->isAvailableOn($isoDate))
                ->map(function ($cat) use ($d) {
                    $booked = $d ? $cat->seatsBookedOnDeparture($d->id) : 0;
                    $free = max(0, (int) $cat->capacity - $booked);
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'capacity' => (int) $cat->capacity,
                        'free' => $free,
                    ];
                })
                ->values();

            // Posti/capienza coerenti coi catamarani EFFETTIVAMENTE disponibili
            // (esclusi i bloccati globalmente). Se tutti bloccati → 0.
            $availSeats = (int) $catamarans->sum('free');
            $availCapacity = (int) $catamarans->sum('capacity');

            return [
                // id reale (numerico) se la partenza esiste, altrimenti id sintetico.
                'id' => $d ? (string) $d->id : ('virt:' . $isoDate . ':' . $time),
                'iso_date' => $isoDate,
                'date' => \Carbon\Carbon::parse($isoDate)->format('d/m/Y'),
                'time' => $time,
                'available' => $availSeats,
                'capacity' => $availCapacity,
                'price_modifier' => $modifier,
                'adult_price' => $period
                    ? round((float) $period->base_price * $modifier, 2)
                    : null,
                'is_past' => \Carbon\Carbon::parse($isoDate)->lt(now()->startOfDay()),
                'status' => $d ? $d->status : 'scheduled',
                'brackets' => $brackets,
                'catamarans' => $catamarans,
            ];
        })
        ->sortByDesc('iso_date')
        ->values();

        return response()->json([
            'tour' => [
                'id' => $tour->id,
                'name' => $tour->name,
                // Tour su richiesta: il form mostra i campi prezzo manuali.
                'on_request' => (bool) $tour->booking_on_request,
                'total_capacity' => (int) $tour->total_capacity,
                // Catamarani operativi del tour (per la modalità uso esclusivo,
                // dove la data è libera e non esiste una partenza a calendario).
                'catamarans' => $tour->operatingCatamarans()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'capacity' => (int) $c->capacity,
                    ])->values(),
            ],
            'departures' => $departures,
        ]);
    }

    /**
     * JSON: disponibilità dei catamarani del tour per il blocco "uso esclusivo"
     * su un periodo (date libere). Per ciascun catamarano indica se è bloccabile
     * e, in caso contrario, quali prenotazioni attive lo impediscono.
     */
    public function catamaranAvailability(Tour $tour, Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end', $start);
        // Fascia oraria del blocco: due slot disgiunti nello stesso giorno non collidono.
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        if (!$start) {
            return response()->json(['catamarans' => []]);
        }
        if (!$end || $end < $start) {
            $end = $start;
        }

        $catamarans = $tour->operatingCatamarans()->map(function ($cat) use ($tour, $start, $end, $startTime, $endTime) {
            $conflicts = $this->bookingService->conflictingBookingsForBlock(
                $tour->id,
                [(int) $cat->id],
                $start,
                $end,
                $startTime,
                $endTime
            );

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'capacity' => (int) $cat->capacity,
                'available' => $conflicts->isEmpty(),
                'conflicts' => $conflicts->map(fn ($b) => [
                    'booking_number' => $b->booking_number,
                    'date' => $b->booking_date->format('d/m/Y'),
                    'customer' => trim($b->customer_first_name . ' ' . $b->customer_last_name),
                ])->values(),
            ];
        })->values();

        return response()->json([
            'tour' => ['id' => $tour->id, 'name' => $tour->name],
            'catamarans' => $catamarans,
        ]);
    }

    /**
     * Salva la prenotazione creata da admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $statusValues = array_column(BookingStatus::cases(), 'value');

        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            // Può essere un id reale (numerico) di tour_departures oppure un id
            // sintetico "virt:Y-m-d:H:i" per una partenza virtuale da materializzare.
            'tour_departure_id' => 'required|string',
            // Partecipanti (come nel frontend): adulti con nome/cognome, bambini con DOB.
            'adults' => 'required|array|min:1',
            'adults.*.first_name' => 'required|string|max:100',
            'adults.*.last_name' => 'required|string|max:100',
            'children' => 'nullable|array',
            'children.*.dob' => 'required_with:children|date|before:today',
            'children.*.first_name' => 'required_with:children|string|max:100',
            'children.*.last_name' => 'required_with:children|string|max:100',
            // Prezzo TOTALE manuale (solo tour "su richiesta", es. catamarano riservato).
            'total_price' => 'nullable|numeric|min:0',
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:addons,id',
            'discount_code' => 'nullable|string|max:50',
            // Attribuzione a un'agenzia B2B: se valorizzato, la prenotazione risulta
            // dell'agenzia (come fatta dal portale) e matura la commissione.
            'b2b_user_id' => 'nullable|exists:users,id',
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_country' => 'nullable|string|max:5',
            'customer_tax_code' => 'nullable|string|max:16',
            'special_requests' => 'nullable|string|max:1000',
            'status' => 'nullable|in:' . implode(',', $statusValues),
            // Metodo di pagamento scelto dall'admin:
            //  - manual: già incassato (contanti/POS/altro) → registra un pagamento;
            //  - stripe: genera un link di pagamento da inviare al cliente;
            //  - bank_transfer: in attesa bonifico.
            'payment_method' => 'nullable|in:manual,stripe,bank_transfer',
            // Rata: intero o acconto (2 rate), solo se l'acconto è attivo.
            'payment_installment' => 'nullable|in:full,deposit',
            // Scadenza saldo (solo acconto): proposta in automatico, modificabile.
            'balance_due_date' => 'nullable|date',
            // Stato forzato manualmente (retroattive: completata/check-in/ecc.).
            // Catamarano singolo (modalità normale): opzionale, vuoto = automatico.
            'catamaran_id' => 'nullable|integer|exists:catamarans,id',
            // Uso esclusivo: blocco catamarano(i) per un periodo con orari.
            'block_catamaran_day' => 'nullable|boolean',
            'block_start_date' => 'nullable|date',
            'block_end_date' => 'nullable|date|after_or_equal:block_start_date',
            'block_start_time' => 'nullable|date_format:H:i',
            'block_end_time' => 'nullable|date_format:H:i',
            // Catamarani da riservare (uso esclusivo): uno o più.
            'catamaran_ids' => 'nullable|array',
            'catamaran_ids.*' => 'integer|exists:catamarans,id',
        ]);

        // Metodo di pagamento e rata (acconto solo se abilitato nelle impostazioni).
        $paymentMethod = $validated['payment_method'] ?? null;
        $useDeposit = \App\Support\Settings::depositEnabled()
            && ($validated['payment_installment'] ?? 'full') === 'deposit';

        // Lo stato deriva dal metodo di pagamento; se l'admin ha forzato uno stato
        // (retroattive), quello ha la precedenza.
        if (!empty($validated['status'])) {
            $status = BookingStatus::from($validated['status']);
        } else {
            $status = match ($paymentMethod) {
                'stripe' => BookingStatus::PENDING,            // attende pagamento online
                'bank_transfer' => BookingStatus::AWAITING_TRANSFER,
                'manual' => $useDeposit ? BookingStatus::DEPOSIT_PAID : BookingStatus::CONFIRMED,
                default => BookingStatus::CONFIRMED,           // default: già incassato
            };
        }

        // Tour "su richiesta": prezzi inseriti a mano dall'admin (niente listino).
        $tour = Tour::findOrFail($validated['tour_id']);
        $isOnRequest = (bool) $tour->booking_on_request;
        if ($isOnRequest && ($validated['total_price'] ?? null) === null) {
            return back()->withInput()->with('error', 'Per un tour su richiesta devi indicare il prezzo totale.');
        }

        // Uso esclusivo (blocco catamarano): la data di partenza è LIBERA, quindi la
        // partenza virtuale non deve essere validata contro i periodi del tour.
        $exclusive = $request->boolean('block_catamaran_day');

        // Catamarani da riservare in uso esclusivo (uno o più).
        $exclusiveCatamaranIds = $exclusive
            ? array_values(array_unique(array_map('intval', $validated['catamaran_ids'] ?? [])))
            : [];

        if ($exclusive && empty($exclusiveCatamaranIds)) {
            return back()->withInput()->with('error', 'Seleziona almeno un catamarano da riservare.');
        }

        // Controllo conflitti: non si possono bloccare catamarani con prenotazioni
        // attive nel periodo. Segnala all'admin quali prenotazioni lo impediscono.
        if ($exclusive) {
            $blockStart = $validated['block_start_date'] ?? null;
            $blockEnd = $validated['block_end_date'] ?? $blockStart;
            $blockStartTime = $validated['block_start_time'] ?? null;
            $blockEndTime = $validated['block_end_time'] ?? null;
            if ($blockStart) {
                $conflicts = $this->bookingService->conflictingBookingsForBlock(
                    (int) $validated['tour_id'],
                    $exclusiveCatamaranIds,
                    $blockStart,
                    $blockEnd ?? $blockStart,
                    $blockStartTime,
                    $blockEndTime
                );
                if ($conflicts->isNotEmpty()) {
                    $lines = $conflicts->map(function ($b) {
                        $cat = $b->seatRecords->pluck('catamaran.name')->filter()->unique()->implode(', ');
                        return '#' . $b->booking_number . ' (' . $b->booking_date->format('d/m/Y')
                            . ' · ' . trim($b->customer_first_name . ' ' . $b->customer_last_name) . ')'
                            . ($cat ? ' — ' . $cat : '');
                    })->implode('; ');
                    return back()->withInput()->with('error',
                        'Impossibile riservare i catamarani selezionati: ci sono prenotazioni attive nel periodo. '
                        . 'Annulla o sposta queste prenotazioni su un altro catamarano prima di procedere: ' . $lines);
                }
            }
        }

        // Risolvi l'id partenza: reale (numerico) o virtuale ("virt:Y-m-d:H:i").
        // La virtuale viene materializzata ora con firstOrCreate (stessa logica
        // del flusso pubblico), così la prenotazione referenzia una riga vera.
        try {
            $departureId = $this->resolveDepartureId(
                (int) $validated['tour_id'],
                (string) $validated['tour_departure_id'],
                $exclusive,
                // In uso esclusivo la partenza dura quanto la fascia indicata (ritorno).
                $exclusive ? ($validated['block_end_time'] ?? null) : null
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        $validated['tour_departure_id'] = $departureId;

        // Risolvi ogni bambino sul bracket della data (in base al DOB) e prepara
        // la lista guests nell'ordine atteso dal service: adulti, poi bambini.
        $adults = array_values($validated['adults']);
        $children = array_values($validated['children'] ?? []);

        $resolvedChildren = [];
        $guests = [];
        foreach ($adults as $i => $a) {
            $guests[] = [
                'first_name' => trim($a['first_name']),
                'last_name' => trim($a['last_name']),
                'tax_code' => $i === 0 && !empty($validated['customer_tax_code'])
                    ? strtoupper(trim($validated['customer_tax_code']))
                    : null,
            ];
        }
        foreach ($children as $c) {
            // Su richiesta i bambini contano solo come posti (nessun prezzo per riga).
            $resolvedChildren[] = ['dob' => $c['dob']];
            $guests[] = [
                'first_name' => trim($c['first_name']),
                'last_name' => trim($c['last_name']),
            ];
        }

        $payload = [
            'tour_id' => $validated['tour_id'],
            'tour_departure_id' => $validated['tour_departure_id'],
            'adults_count' => count($adults),
            'children' => $resolvedChildren,
            'addons' => $validated['addons'] ?? [],
            'discount_code' => $validated['discount_code'] ?? null,
            'customer_first_name' => $validated['customer_first_name'],
            'customer_last_name' => $validated['customer_last_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_country' => $validated['customer_country'] ?? 'IT',
            'special_requests' => $validated['special_requests'] ?? null,
            'guests' => $guests,
            'status' => $status,           // stato scelto dall'admin
            'admin_override' => true,      // consente partenze passate (retroattive)
            // Collega la prenotazione al CLIENTE (se l'email è di un utente registrato),
            // mai all'admin che la sta creando. Null se l'email non ha un account.
            'user_id' => \App\Models\User::where('email', $validated['customer_email'])->value('id'),
            // Tipo di pagamento per il calcolo acconto/saldo nel service.
            'payment_type' => $useDeposit ? 'deposit' : ($paymentMethod === 'bank_transfer' ? 'bank_transfer' : 'full'),
            'use_deposit' => $useDeposit,
            // Prezzo TOTALE manuale (usato solo per i tour su richiesta).
            'total_price' => $isOnRequest ? (float) ($validated['total_price'] ?? 0) : null,
            // Uso esclusivo: i catamarani scelti vanno usati (anche oltre capienza).
            'exclusive_use' => $exclusive,
            'forced_catamaran_ids' => $exclusive
                ? $exclusiveCatamaranIds
                // Modalità normale: eventuale singolo catamarano scelto (o automatico).
                : (!empty($validated['catamaran_id']) ? [(int) $validated['catamaran_id']] : null),
        ];

        try {
            $booking = $this->bookingService->create($payload, 'admin');

            // Attribuzione a un'agenzia B2B (opzionale): l'admin sta registrando una
            // prenotazione per conto di un'agenzia. Risulta come dal portale
            // (attribution_source=b2b_portal) e matura la commissione dell'agenzia.
            if (!empty($validated['b2b_user_id'])) {
                $agency = \App\Models\User::where('role', 'b2b')->find($validated['b2b_user_id']);
                if ($agency) {
                    app(\App\Services\CommissionService::class)
                        ->attributeToAgency($booking, $agency, 'b2b_portal');
                }
            }

            // Scadenza saldo: se l'admin l'ha indicata a mano (acconto), sovrascrive
            // quella calcolata in automatico dal service.
            if ($useDeposit && !empty($validated['balance_due_date'])) {
                $booking->update(['balance_due_at' => \Carbon\Carbon::parse($validated['balance_due_date'])]);
            }

            // Blocco catamarano (uso esclusivo) per un periodo con orari, se richiesto:
            // blocca i catamarani esplicitamente scelti dall'admin.
            if ($exclusive && !empty($exclusiveCatamaranIds)) {
                $depDate = $booking->booking_date->toDateString();
                $startDate = $validated['block_start_date'] ?? $depDate;
                $endDate = $validated['block_end_date'] ?? $startDate;
                if ($endDate < $startDate) {
                    $endDate = $startDate;
                }
                $startTime = $validated['block_start_time'] ?? null;
                $endTime = $validated['block_end_time'] ?? null;

                foreach ($exclusiveCatamaranIds as $catId) {
                    \App\Models\TourCatamaranBlock::firstOrCreate(
                        [
                            'tour_id' => $booking->tour_id,
                            'catamaran_id' => $catId,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ],
                        [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'reason' => 'Riservato da prenotazione admin #' . $booking->booking_number,
                        ]
                    );
                }
            }

            // Timestamp coerenti con lo stato scelto (utile per report e dettaglio).
            // Per le retroattive usa la data della partenza se è già passata.
            $eventTime = $booking->booking_date && $booking->booking_date->isPast()
                ? $booking->booking_date
                : now();
            $stamps = match ($status) {
                BookingStatus::CONFIRMED, BookingStatus::DEPOSIT_PAID, BookingStatus::AWAITING_TRANSFER
                    => ['confirmed_at' => $booking->confirmed_at ?? $eventTime],
                BookingStatus::CHECKED_IN
                    => ['confirmed_at' => $booking->confirmed_at ?? $eventTime, 'checked_in_at' => $eventTime],
                BookingStatus::COMPLETED
                    => ['confirmed_at' => $booking->confirmed_at ?? $eventTime, 'completed_at' => $eventTime],
                BookingStatus::CANCELLED, BookingStatus::REFUNDED
                    => ['cancelled_at' => now()],
                default => [],
            };
            if ($stamps) {
                $booking->update($stamps);
            }

            // Gestione pagamento in base al metodo scelto dall'admin.
            if ($paymentMethod === 'stripe') {
                // Genera il link di pagamento e SALVALO (senza inviare email):
                // l'admin lo vede nel dettaglio e decide se inviarlo al cliente.
                $linkOk = $this->generatePaymentLink($booking);
                $message = $linkOk
                    ? 'Prenotazione creata. Link di pagamento pronto: invialo al cliente dal dettaglio.'
                    : 'Prenotazione creata, ma la generazione del link di pagamento è fallita (controlla il log).';
            } elseif ($paymentMethod === 'manual') {
                // Pagamento già incassato (contanti/POS/altro): registra l'incasso
                // così amount_paid è valorizzato (necessario per penali/rimborsi).
                $amount = $useDeposit && (float) $booking->deposit_amount > 0
                    ? (float) $booking->deposit_amount
                    : (float) $booking->total_amount;
                $this->registerManualPayment($booking, $amount);
                if ($status === BookingStatus::CONFIRMED) {
                    $this->sendTicketsEmail($booking);
                    $message = 'Prenotazione creata e confermata (incasso registrato). Biglietti inviati al cliente.';
                } else {
                    $message = 'Prenotazione creata. Incasso registrato (€ ' . number_format($amount, 2, ',', '.') . ').';
                }
            } elseif ($paymentMethod === 'bank_transfer') {
                $message = 'Prenotazione creata in attesa di bonifico.';
            } elseif ($status === BookingStatus::PENDING) {
                // Stato forzato PENDING senza metodo: comportamento storico (email link).
                $this->sendPaymentLinkEmail($booking);
                $message = 'Prenotazione creata. Email con link di pagamento inviata al cliente.';
            } elseif ($status === BookingStatus::CONFIRMED) {
                $this->sendTicketsEmail($booking);
                $message = 'Prenotazione creata e confermata. Biglietti inviati al cliente.';
            } else {
                $message = 'Prenotazione creata con stato "' . $status->label() . '".';
            }

            BookingLog::info('booking_admin_create', 'Prenotazione creata da admin', $booking, [
                'payment_method' => $paymentMethod ?? null,
                'total_amount' => (float) $booking->total_amount,
            ]);

            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('success', $message);
        } catch (\Exception $e) {
            BookingLog::failure('booking_admin_create', 'Creazione prenotazione admin fallita', null, $e);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Risolve l'id partenza inviato dal form admin in un id REALE di tour_departures.
     *
     * Accetta:
     *  - un id numerico esistente (partenza già materializzata);
     *  - un id sintetico "virt:Y-m-d:H:i" (partenza virtuale): normalmente viene
     *    verificata contro i periodi del tour e materializzata con firstOrCreate.
     *
     * @param  bool  $allowFreeDate  uso esclusivo: salta la verifica sui periodi
     *                               (la data di partenza è scelta liberamente).
     * @throws \RuntimeException se la partenza non è valida per il tour.
     */
    protected function resolveDepartureId(int $tourId, string $rawId, bool $allowFreeDate = false, ?string $endTimeOverride = null): int
    {
        // Id reale già esistente.
        if (ctype_digit($rawId)) {
            $dep = \App\Models\TourDeparture::where('tour_id', $tourId)->find((int) $rawId);
            if (!$dep) {
                throw new \RuntimeException('Partenza non valida per questo tour.');
            }
            return $dep->id;
        }

        // Id virtuale "virt:Y-m-d:H:i".
        if (!preg_match('/^virt:(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})$/', $rawId, $m)) {
            throw new \RuntimeException('Partenza non valida.');
        }
        [$date, $time] = [$m[1], $m[2]];

        $tour = Tour::with('periods')->findOrFail($tourId);

        // In modalità uso esclusivo la data è libera: nessuna verifica sui periodi.
        if (!$allowFreeDate) {
            // Verifica che data+orario siano coperti da un periodo del tour
            // (stessa regola di BookingController::start e BookingForm::resolveDeparture).
            $period = $tour->periods
                ->first(function ($p) use ($date, $time) {
                    $within = \Carbon\Carbon::parse($date)->betweenIncluded(
                        \Carbon\Carbon::parse($p->start_date),
                        \Carbon\Carbon::parse($p->end_date)
                    );
                    if (!$within) {
                        return false;
                    }
                    $weekdays = is_array($p->weekdays) && !empty($p->weekdays) ? $p->weekdays : [1, 2, 3, 4, 5, 6, 7];
                    $times = is_array($p->times) && !empty($p->times) ? $p->times : ['10:00'];
                    $iso = \Carbon\Carbon::parse($date)->isoWeekday();
                    return in_array($iso, array_map('intval', $weekdays), true)
                        && in_array($time, array_map(fn ($t) => substr($t, 0, 5), $times), true);
                });

            if (!$period) {
                throw new \RuntimeException('La data o l\'orario selezionato non è disponibile per questo tour.');
            }
        }

        $startTime = $time . ':00';
        // In uso esclusivo fa fede l'orario di RITORNO indicato dall'admin: la
        // partenza dura solo la fascia riservata, non l'intera durata del tour.
        // Così dalle 12:30 il catamarano torna prenotabile (da admin) lo stesso giorno.
        $endTime = $endTimeOverride
            ? (strlen($endTimeOverride) === 5 ? $endTimeOverride . ':00' : $endTimeOverride)
            : \Carbon\Carbon::parse($startTime)
                ->addMinutes((int) round(($tour->duration_hours ?? 1) * 60))
                ->format('H:i:s');

        $dep = \App\Models\TourDeparture::firstOrCreate(
            [
                'tour_id' => $tour->id,
                'departure_date' => $date,
                'start_time' => $startTime,
            ],
            [
                'end_time' => $endTime,
                'status' => 'scheduled',
                'price_modifier' => 1.0,
            ]
        );

        // Se la riga esisteva già con un end_time diverso (es. durata piena del tour),
        // allinealo alla fascia esclusiva indicata dall'admin.
        if ($endTimeOverride && $dep->wasRecentlyCreated === false
            && \Carbon\Carbon::parse($dep->end_time)->format('H:i:s') !== $endTime) {
            $dep->update(['end_time' => $endTime]);
        }

        return $dep->id;
    }

    /**
     * Genera la sessione Stripe e spedisce l'email con il link di pagamento.
     */
    protected function sendPaymentLinkEmail(Booking $booking): bool
    {
        try {
            // Riusa il link già generato se presente, altrimenti creane uno.
            $url = $booking->checkout_url;
            if (! $url) {
                $session = $this->paymentService->createCheckoutSession($booking);
                $url = $session['url'];
                $booking->update(['checkout_url' => $url]);
            }
            Mail::to($booking->customer_email)->send(new BookingPaymentLink($booking, $url));
            $booking->update(['payment_link_sent_at' => now()]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Invio email link pagamento fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Genera (e salva) il link di pagamento Stripe SENZA inviare email.
     * Usato quando l'admin vuole il link da inviare lui al cliente.
     */
    protected function generatePaymentLink(Booking $booking): bool
    {
        try {
            $session = $this->paymentService->createCheckoutSession($booking);
            $booking->update(['checkout_url' => $session['url']]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Generazione link pagamento fallita', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Registra un incasso "manuale" (contanti/POS/altro) sulla prenotazione,
     * così amount_paid è valorizzato e penali/rimborsi funzionano correttamente.
     */
    protected function registerManualPayment(Booking $booking, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }
        Payment::create([
            'booking_id' => $booking->id,
            'gateway' => 'manual',
            'amount' => $amount,
            'currency' => $booking->currency ?: 'EUR',
            'status' => PaymentStatus::SUCCEEDED,
            'payment_method_type' => 'manual',
            'paid_at' => now(),
        ]);
        $booking->update(['amount_paid' => (float) $booking->amount_paid + $amount]);
    }

    /**
     * Spedisce l'email con i biglietti / QR (idempotente).
     */
    protected function sendTicketsEmail(Booking $booking): bool
    {
        if ($booking->tickets_sent_at) {
            return true;
        }
        try {
            Mail::to($booking->customer_email)->send(new BookingTickets($booking));
            $booking->update(['tickets_sent_at' => now()]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Invio email biglietti fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'tour',
            'departure',
            'addons.addon',
            'payments',
            'checkIns',
            'b2bUser',
            'discountCode',
            'seatRecords.catamaran',
            'seatRecords.ageBracket',
        ]);
        $catamarans = Catamaran::active()->ordered()->get();

        // Catamarani riservati (uso esclusivo) da QUESTA prenotazione: i blocchi
        // sono marcati col numero prenotazione nel campo reason.
        $reservedBlocks = \App\Models\TourCatamaranBlock::with('catamaran')
            ->where('reason', 'like', '%#' . $booking->booking_number . '%')
            ->orderBy('start_date')
            ->get();

        // Catamarani su cui spostare la riserva: completamente liberi nella partenza
        // (0 posti occupati) e diversi da quelli già riservati da questa prenotazione.
        $reservedCatIds = $reservedBlocks->pluck('catamaran_id')->map(fn ($id) => (int) $id)->all();
        $freeCatamaransForReservation = collect();
        if ($booking->departure && $reservedBlocks->isNotEmpty()) {
            $avail = $this->bookingService->catamaranAvailabilityList($booking->departure);
            $freeNames = collect($avail)->filter(fn ($c) => $c['free'] === $c['capacity'])->pluck('name');
            $freeCatamaransForReservation = $catamarans
                ->whereIn('name', $freeNames)
                ->whereNotIn('id', $reservedCatIds)
                ->values();
        }

        return view('admin.bookings.show', compact('booking', 'catamarans', 'reservedBlocks', 'freeCatamaransForReservation'));
    }

    public function edit(Booking $booking): View
    {
        $booking->load(['tour', 'departure', 'seatRecords.catamaran', 'seatRecords.ageBracket', 'addons.addon']);
        $catamarans = Catamaran::active()->ordered()->get();
        $statuses = BookingStatus::cases();
        // Percentuale di rimborso prevista dalla policy (per il modale penali).
        $refundPercentage = $this->paymentService->refundPercentageFor($booking);

        // Prenotazione a uso esclusivo (multi-giorno): periodo/orari attuali dal blocco.
        $reservedBlock = \App\Models\TourCatamaranBlock::where('reason', 'like', '%#' . $booking->booking_number . '%')
            ->orderBy('start_date')->first();

        return view('admin.bookings.edit', compact('booking', 'catamarans', 'statuses', 'refundPercentage', 'reservedBlock'));
    }

    /**
     * Disdetta di singoli partecipanti / extra da una prenotazione, con eventuale
     * rimborso parziale calcolato sulla somma dei rimossi. Gli elementi restano a
     * DB (storico) marcati cancelled_at; posti/totali vengono ricalcolati.
     */
    public function removeItems(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'seat_ids' => 'nullable|array',
            'seat_ids.*' => 'integer',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'integer',
            'reason' => 'nullable|string|max:500',
            'refund_mode' => 'nullable|in:penalty,full,custom,none',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $seatIds = array_map('intval', $validated['seat_ids'] ?? []);
        $addonIds = array_map('intval', $validated['addon_ids'] ?? []);

        if (empty($seatIds) && empty($addonIds)) {
            return back()->with('error', 'Nessun elemento selezionato da rimuovere.');
        }

        // Posti da disdire: solo di questa prenotazione, attivi, NON intestatario.
        $seats = $booking->seatRecords()
            ->whereNull('cancelled_at')
            ->whereIn('id', $seatIds)
            ->where('is_primary', false)
            ->get();

        // Blocco: non si può scendere sotto 1 partecipante attivo.
        $activeSeatCount = $booking->seatRecords()->whereNull('cancelled_at')->count();
        if (count($seats) >= $activeSeatCount) {
            return back()->with('error', 'Deve restare almeno un partecipante. Per azzerare la prenotazione usa l\'annullamento.');
        }

        $addons = $booking->addons()
            ->whereNull('cancelled_at')
            ->whereIn('id', $addonIds)
            ->get();

        if ($seats->isEmpty() && $addons->isEmpty()) {
            return back()->with('error', 'Gli elementi selezionati non sono validi (forse l\'intestatario o già rimossi).');
        }

        // Importo dei rimossi (riferimento per il rimborso).
        $removedAmount = (float) $seats->sum(fn ($s) => (float) $s->price_paid)
            + (float) $addons->sum(fn ($a) => (float) $a->total_price);

        $reason = $request->filled('reason') ? $request->input('reason') : 'Modifica prenotazione (rimozione partecipanti/extra)';
        $mode = $validated['refund_mode'] ?? 'penalty';
        $calc = $this->paymentService->refundForRemovedAmount($booking, $removedAmount);

        $refundAmount = match ($mode) {
            'full'   => $removedAmount,
            'custom' => min((float) ($validated['refund_amount'] ?? 0), $removedAmount),
            'none'   => 0.0,
            default  => (float) $calc['amount'], // penalty da policy
        };

        DB::transaction(function () use ($seats, $addons, $reason, $booking) {
            foreach ($seats as $s) {
                $s->update(['cancelled_at' => now(), 'cancellation_reason' => $reason]);
            }
            foreach ($addons as $a) {
                $a->update(['cancelled_at' => now(), 'cancellation_reason' => $reason]);
            }
            $booking->recalculateTotals();
        });

        // Rimborso parziale sull'importo deciso (riusa la stessa logica del cancel).
        $refundMsg = '';
        if ($refundAmount > 0) {
            $refund = $this->paymentService->applyCancellationRefund($booking->fresh(), $refundAmount, $reason);
            $refundMsg = ' Rimborso: € ' . number_format((float) ($refund['amount'] ?? 0), 2, ',', '.')
                . (($refund['manual'] ?? false) ? ' (da effettuare manualmente)' : ' (su Stripe)') . '.';
        }

        $removedCount = count($seats) + count($addons);

        BookingLog::info('booking_remove_items', 'Rimossi partecipanti/extra', $booking->fresh(), [
            'seats_removed' => count($seats),
            'addons_removed' => count($addons),
            'refund_mode' => $mode,
            'refund_amount' => round((float) $refundAmount, 2),
        ]);

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', "Rimossi {$removedCount} element" . ($removedCount === 1 ? 'o' : 'i') . "." . $refundMsg);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:' . implode(',', array_column(BookingStatus::cases(), 'value')),
            'special_requests' => 'nullable|string|max:1000',
            'customer_phone' => 'nullable|string|max:30',
            // Prezzo totale manuale (solo tour "su richiesta" / catamarano riservato).
            'total_price' => 'nullable|numeric|min:0',
            // Scadenza saldo (prenotazioni con acconto): modificabile.
            'balance_due_date' => 'nullable|date',
        ]);

        // Aggiorna il prezzo totale manuale per le prenotazioni su richiesta:
        // il totale è "secco" e viene attribuito al primo posto (gli altri a 0),
        // coerentemente con come è stato creato.
        $booking->loadMissing('tour');
        if ($booking->tour?->booking_on_request && $request->filled('total_price')) {
            $newTotal = round((float) $validated['total_price'], 2);
            DB::transaction(function () use ($booking, $newTotal) {
                $seats = $booking->seatRecords()->whereNull('cancelled_at')->orderByDesc('is_primary')->orderBy('id')->get();
                foreach ($seats as $i => $seat) {
                    $seat->update(['price_paid' => $i === 0 ? $newTotal : 0.0]);
                }
                $booking->update([
                    'base_price' => $newTotal,
                    'total_amount' => $newTotal,
                ]);
            });
        }

        $booking->update([
            'status' => $validated['status'] ?? $booking->status,
            'special_requests' => $validated['special_requests'] ?? $booking->special_requests,
            'customer_phone' => $validated['customer_phone'] ?? $booking->customer_phone,
        ]);

        // Scadenza saldo modificabile (solo se c'è un saldo da incassare).
        if ($request->filled('balance_due_date') && (float) $booking->balance_amount > 0) {
            $booking->update(['balance_due_at' => \Carbon\Carbon::parse($validated['balance_due_date'])]);
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Prenotazione aggiornata.');
    }

    /**
     * Anteprima JSON del cambio data: calcola il nuovo totale e la differenza
     * SENZA salvare nulla (transazione annullata).
     */
    public function reschedulePreview(Request $request, Booking $booking)
    {
        $request->validate(['tour_departure_id' => 'required|string']);

        try {
            $departureId = $this->resolveDepartureId(
                (int) $booking->tour_id,
                (string) $request->input('tour_departure_id')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $newDeparture = \App\Models\TourDeparture::findOrFail($departureId);

        // Simula in una transazione annullata per non toccare i dati.
        DB::beginTransaction();
        try {
            $result = $this->bookingService->reschedule($booking, $newDeparture);
        } finally {
            DB::rollBack();
        }
        $booking->refresh(); // scarta eventuali modifiche in memoria

        $paid = (float) $this->paymentService->amountPaid($booking);
        return response()->json([
            'old_total' => $result['old_total'],
            'new_total' => $result['new_total'],
            'difference' => $result['difference'],
            'amount_paid' => round($paid, 2),
            'payment_method' => $this->paymentService->primaryPaymentMethod($booking),
            'new_date' => $newDeparture->departure_date->format('d/m/Y'),
            'new_time' => \Carbon\Carbon::parse($newDeparture->start_time)->format('H:i'),
        ]);
    }

    /**
     * Esegue il cambio data e gestisce il conguaglio secondo il metodo di pagamento
     * originale (Stripe / bonifico / manuale).
     */
    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'tour_departure_id' => 'nullable|string',
            // Come gestire una differenza A FAVORE del cliente (nuova data più economica).
            'credit_mode' => 'nullable|in:refund,none,custom',
            'credit_amount' => 'nullable|numeric|min:0',
            // Come incassare un conguaglio in AUMENTO se bonifico/manuale.
            'surcharge_handling' => 'nullable|in:paid,pending',
            // Uso esclusivo: nuovo periodo (partenza + ritorno) con orari.
            'new_start_date' => 'nullable|date',
            'new_end_date' => 'nullable|date|after_or_equal:new_start_date',
            'new_start_time' => 'nullable|date_format:H:i',
            'new_end_time' => 'nullable|date_format:H:i',
        ]);

        // I blocchi (uso esclusivo) collegati a questa prenotazione.
        $blocks = \App\Models\TourCatamaranBlock::where('reason', 'like', '%#' . $booking->booking_number . '%')->get();
        $isExclusive = $blocks->isNotEmpty();

        if ($isExclusive) {
            // Cambio data uso esclusivo: nuovo periodo con orari liberi.
            $startDate = $validated['new_start_date'] ?? null;
            if (!$startDate) {
                return back()->with('error', 'Indica la nuova data di partenza.');
            }
            $endDate = $validated['new_end_date'] ?? $startDate;
            if ($endDate < $startDate) {
                $endDate = $startDate;
            }
            $startTime = $validated['new_start_time'] ?? \Carbon\Carbon::parse($blocks->first()->start_time ?: '09:00')->format('H:i');
            $endTime = $validated['new_end_time'] ?? \Carbon\Carbon::parse($blocks->first()->end_time ?: '18:00')->format('H:i');
            $catamaranIds = $blocks->pluck('catamaran_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

            // Controllo conflitti sul NUOVO periodo, escludendo questa prenotazione.
            $conflicts = $this->bookingService->conflictingBookingsForBlock(
                (int) $booking->tour_id, $catamaranIds, $startDate, $endDate, $startTime, $endTime, $booking->id
            );
            if ($conflicts->isNotEmpty()) {
                $lines = $conflicts->map(fn ($b) => '#' . $b->booking_number . ' (' . $b->booking_date->format('d/m/Y') . ')')->implode('; ');
                return back()->with('error', 'Impossibile spostare: i catamarani sono occupati nel nuovo periodo da: ' . $lines);
            }

            // Materializza/riusa la partenza sulla nuova data di partenza (data libera).
            try {
                $departureId = $this->resolveDepartureId((int) $booking->tour_id, 'virt:' . $startDate . ':' . $startTime, true, $endTime);
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
            $newDeparture = \App\Models\TourDeparture::findOrFail($departureId);

            $this->bookingService->reschedule($booking, $newDeparture);

            // Sposta i blocchi sul nuovo periodo (con i nuovi orari).
            foreach ($blocks as $blk) {
                $blk->update([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }

            $booking->refresh();
            return redirect()->route('admin.bookings.show', $booking)->with('success',
                'Prenotazione (uso esclusivo) spostata dal ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y')
                . ' al ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y') . '.');
        }

        // --- Cambio data prenotazione NORMALE (singolo giorno) ---
        if (empty($validated['tour_departure_id'])) {
            return back()->with('error', 'Seleziona la nuova data.');
        }

        try {
            $departureId = $this->resolveDepartureId(
                (int) $booking->tour_id,
                (string) $validated['tour_departure_id']
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $newDeparture = \App\Models\TourDeparture::findOrFail($departureId);
        $method = $this->paymentService->primaryPaymentMethod($booking);

        $result = $this->bookingService->reschedule($booking, $newDeparture);
        $booking->refresh();
        $diff = (float) $result['difference'];

        $msg = 'Prenotazione spostata al ' . $newDeparture->departure_date->format('d/m/Y')
            . ' alle ' . \Carbon\Carbon::parse($newDeparture->start_time)->format('H:i') . '.';

        if (abs($diff) < 0.01) {
            return redirect()->route('admin.bookings.show', $booking)->with('success', $msg . ' Nessun conguaglio.');
        }

        if ($diff > 0) {
            // Conguaglio DA INCASSARE.
            if ($method === 'stripe') {
                $ok = $this->generatePaymentLink($booking);
                $booking->update(['status' => BookingStatus::PENDING]);
                $msg .= $ok
                    ? ' Conguaglio di € ' . number_format($diff, 2, ',', '.') . ': link di pagamento pronto nel dettaglio.'
                    : ' Conguaglio dovuto ma generazione link fallita (controlla il log).';
            } else {
                // Bonifico / manuale: scelta nel modale.
                $handling = $validated['surcharge_handling'] ?? 'paid';
                if ($handling === 'paid') {
                    $this->registerManualPayment($booking, $diff);
                    $msg .= ' Conguaglio di € ' . number_format($diff, 2, ',', '.') . ' registrato come incassato.';
                } else {
                    $booking->update(['status' => BookingStatus::AWAITING_TRANSFER]);
                    $msg .= ' Conguaglio di € ' . number_format($diff, 2, ',', '.') . ' in attesa di incasso.';
                }
            }
        } else {
            // Differenza A CREDITO: scelta dell'admin.
            $creditMode = $validated['credit_mode'] ?? 'none';
            if ($creditMode !== 'none') {
                $credit = $creditMode === 'custom'
                    ? min((float) ($validated['credit_amount'] ?? 0), abs($diff))
                    : abs($diff);
                if ($credit > 0) {
                    $refund = $this->paymentService->applyCancellationRefund($booking, $credit, 'Conguaglio cambio data');
                    $msg .= ' Rimborso differenza € ' . number_format((float) ($refund['amount'] ?? 0), 2, ',', '.')
                        . (($refund['manual'] ?? false) ? ' (manuale)' : ' (su Stripe)') . '.';
                }
            } else {
                $msg .= ' Differenza a favore del cliente non rimborsata.';
            }
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', $msg);
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();
        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione eliminata.');
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::PENDING) {
            return back()->with('error', 'Solo le prenotazioni in attesa possono essere confermate.');
        }
        $booking->update(['status' => BookingStatus::CONFIRMED, 'confirmed_at' => now()]);
        BookingLog::info('booking_confirm', 'Prenotazione confermata da admin', $booking);
        return back()->with('success', 'Prenotazione confermata.');
    }

    /**
     * Conferma l'incasso di un bonifico: registra il pagamento, aggiorna lo stato
     * (CONFIRMED, oppure DEPOSIT_PAID se era un acconto con saldo residuo) e
     * triggera l'invio biglietti tramite l'Observer sul passaggio a CONFIRMED.
     */
    public function confirmTransfer(Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::AWAITING_TRANSFER) {
            return back()->with('error', 'Questa prenotazione non è in attesa di bonifico.');
        }

        // Importo incassato: acconto se previsto, altrimenti intero.
        $isDeposit = $booking->payment_type === 'deposit' && (float) $booking->deposit_amount > 0;
        $amount = $isDeposit ? (float) $booking->deposit_amount : (float) $booking->total_amount;

        // Registra il pagamento bonifico.
        Payment::create([
            'booking_id' => $booking->id,
            'gateway' => 'bank_transfer',
            'amount' => $amount,
            'currency' => $booking->currency ?: 'EUR',
            'status' => PaymentStatus::SUCCEEDED,
            'payment_method_type' => 'bank_transfer',
            'paid_at' => now(),
        ]);

        $newStatus = ($isDeposit && (float) $booking->balance_amount > 0)
            ? BookingStatus::DEPOSIT_PAID
            : BookingStatus::CONFIRMED;

        $booking->update([
            'amount_paid' => (float) $booking->amount_paid + $amount,
            'status' => $newStatus,
            'confirmed_at' => $booking->confirmed_at ?? now(),
        ]);

        BookingLog::info('booking_transfer_confirm', 'Incasso bonifico confermato', $booking->fresh(), [
            'amount' => $amount,
            'is_deposit' => $isDeposit,
            'new_status' => $newStatus->value,
        ]);

        // L'Observer su CONFIRMED invia biglietti + notifica admin.
        return back()->with('success', 'Bonifico confermato. La prenotazione è ora ' . $newStatus->label() . '.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            // penalty = applica penale da policy · full = rimborso totale · custom = importo libero · none = nessun rimborso
            'refund_mode' => 'nullable|in:penalty,full,custom,none',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $reason = $request->filled('reason') ? $request->input('reason') : 'Annullata da amministratore';
            $mode = $validated['refund_mode'] ?? 'penalty';

            // Calcola sempre la situazione (importo versato + penale da policy).
            $calc = $this->paymentService->calculateRefundAmount($booking);
            $paid = (float) ($calc['paid'] ?? 0);

            // Determina l'importo da rimborsare in base alla scelta admin.
            $refundAmount = match ($mode) {
                'full'   => $paid,
                'custom' => min((float) ($validated['refund_amount'] ?? 0), $paid),
                'none'   => 0.0,
                default  => (float) ($calc['amount'] ?? 0), // penalty
            };

            $this->bookingService->cancel($booking, $reason);

            $refund = null;
            if ($paid > 0) {
                $refund = $this->paymentService->applyCancellationRefund($booking, $refundAmount, $reason);
                if (($refund['amount'] ?? 0) > 0 && ! ($refund['manual'] ?? false)) {
                    $booking->update(['status' => BookingStatus::REFUNDED]);
                }
            }

            // Per l'email cliente, riflette l'importo effettivamente deciso dall'admin.
            $emailCalc = array_merge($calc, [
                'amount' => $refundAmount,
                'penalty' => round(max(0, $paid - $refundAmount), 2),
                'percentage' => $paid > 0 ? (int) round($refundAmount / $paid * 100) : 0,
            ]);

            try {
                Mail::to($booking->customer_email)->send(new BookingCancelled($booking->fresh(), $reason, $emailCalc));
            } catch (\Throwable $e) {
                Log::error('Invio mail annullamento fallito', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }

            try {
                \App\Support\AdminMailer::send(new AdminBookingCancelled($booking->fresh(), $reason, $emailCalc, $refund));
            } catch (\Throwable $e) {
                Log::error('Notifica admin annullamento fallita', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }

            $msg = 'Prenotazione annullata. Email inviata al cliente.';
            if ($refund && ($refund['amount'] ?? 0) > 0) {
                $msg .= ($refund['manual'] ?? false)
                    ? ' Rimborso bonifico di €' . number_format($refund['amount'], 2, ',', '.') . ' da effettuare manualmente.'
                    : ' Rimborso di €' . number_format($refund['amount'], 2, ',', '.') . ' eseguito su Stripe.';
            }

            BookingLog::info('booking_admin_cancel', 'Prenotazione annullata da admin', $booking->fresh(), [
                'reason' => $reason,
                'refund_mode' => $mode,
                'refund_amount' => round((float) $refundAmount, 2),
                'refund_manual' => (bool) ($refund['manual'] ?? false),
            ]);

            // Se c'era una richiesta di annullamento dell'agenzia, marcala accolta.
            $this->markB2bRequestResolved($booking, 'approved');

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            BookingLog::failure('booking_admin_cancel', 'Annullamento admin fallito', $booking, $e);
            return back()->with('error', $e->getMessage());
        }
    }

    public function refund(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0|max:' . ((float) $booking->total_amount),
            'note' => 'nullable|string|max:500',
        ]);

        $amount = isset($validated['amount']) && $validated['amount'] !== null && $validated['amount'] !== ''
            ? (float) $validated['amount']
            : (float) $booking->total_amount;
        $note = $validated['note'] ?? null;

        // 1) Aggiorna la prenotazione
        $booking->update(['status' => BookingStatus::REFUNDED]);

        // 2) Rifletti il rimborso sui Payment del booking così la pagina Pagamenti
        //    mostra lo stato corretto (refunded / partially_refunded).
        $payments = $booking->payments()
            ->whereIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->orderBy('paid_at')
            ->get();

        $remaining = $amount;
        foreach ($payments as $payment) {
            if ($remaining <= 0) {
                break;
            }
            $alreadyRefunded = (float) $payment->refunded_amount;
            $refundable = max(0, (float) $payment->amount - $alreadyRefunded);
            if ($refundable <= 0) {
                continue;
            }
            $apply = min($remaining, $refundable);
            $totalRefunded = $alreadyRefunded + $apply;
            $isFull = $totalRefunded >= (float) $payment->amount - 0.005;

            $payment->update([
                'status' => $isFull ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED,
                'refunded_amount' => round($totalRefunded, 2),
                'refund_reason' => $note ?: 'Rimborso da admin (booking)',
                'refunded_at' => now(),
            ]);

            $remaining -= $apply;
        }

        // 3) Mail al cliente
        try {
            Mail::to($booking->customer_email)->send(new BookingRefunded($booking->fresh(), $amount, $note));
        } catch (\Throwable $e) {
            Log::error('Invio mail rimborso fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Rimborso registrato sulla prenotazione, ma l\'invio email è fallito (controlla il log).');
        }

        BookingLog::info('booking_refund', 'Rimborso registrato da admin', $booking->fresh(), [
            'amount' => round($amount, 2),
            'note' => $note,
        ]);

        try {
            \App\Support\AdminMailer::send(new AdminBookingRefunded($booking->fresh(), $amount, $note));
        } catch (\Throwable $e) {
            Log::error('Notifica admin rimborso fallita', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Rimborso registrato (€' . number_format($amount, 2, ',', '.') . '). Email inviata al cliente.');
    }

    public function resendConfirmation(Booking $booking): RedirectResponse
    {
        try {
            Mail::to($booking->customer_email)->send(new BookingTickets($booking));
            $booking->update(['tickets_sent_at' => now()]);
            return back()->with('success', 'Biglietti reinviati a ' . $booking->customer_email . '.');
        } catch (\Throwable $e) {
            Log::error('Reinvio biglietti fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Invio fallito: ' . $e->getMessage());
        }
    }

    /**
     * Invia al cliente l'email col link di pagamento Stripe (genera il link se manca).
     */
    public function sendPaymentLink(Booking $booking): RedirectResponse
    {
        $ok = $this->sendPaymentLinkEmail($booking);
        return $ok
            ? back()->with('success', 'Link di pagamento inviato a ' . $booking->customer_email . '.')
            : back()->with('error', 'Invio del link fallito (controlla il log).');
    }

    /**
     * Invia al cliente la richiesta di pagamento del SALDO (prenotazioni con
     * acconto). L'email contiene il link alla pagina di saldo, che gestisce sia
     * il pagamento con carta (Stripe) sia le istruzioni per il bonifico.
     */
    public function sendBalanceRequest(Booking $booking): RedirectResponse
    {
        if (! $booking->hasBalanceDue()) {
            return back()->with('error', 'Per questa prenotazione non risulta un saldo da pagare.');
        }
        try {
            Mail::to($booking->customer_email)->send(new \App\Mail\BookingBalanceReminder($booking));
            $booking->update(['balance_reminder_sent_at' => now()]);
            return back()->with('success', 'Richiesta di saldo inviata a ' . $booking->customer_email . '.');
        } catch (\Throwable $e) {
            Log::error('Invio richiesta saldo fallito', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            return back()->with('error', 'Invio della richiesta di saldo fallito (controlla il log).');
        }
    }

    public function export(Request $request)
    {
        return back()->with('info', 'Export non ancora implementato.');
    }

    /**
     * Sposta un singolo posto su un altro catamarano (richiesta AJAX o form).
     */
    public function moveSeat(Request $request, Booking $booking, BookingSeat $seat): RedirectResponse
    {
        if ($seat->booking_id !== $booking->id) {
            abort(404);
        }
        $request->validate(['catamaran_id' => 'required|exists:catamarans,id']);
        try {
            $this->bookingService->moveSeat($seat, (int) $request->catamaran_id);
            return back()->with('success', 'Posto spostato.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Sposta un'intera prenotazione a uso esclusivo su un altro catamarano
     * (posti + riserva). Il catamarano di destinazione dev'essere libero.
     */
    public function moveReservation(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate(['catamaran_id' => 'required|exists:catamarans,id']);
        try {
            $this->bookingService->moveExclusiveReservation($booking, (int) $request->catamaran_id);
            return back()->with('success', 'Riserva spostata sul nuovo catamarano.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Risolve una richiesta dell'agenzia (annullamento/modifica) senza eseguirla:
     * la marca approvata o rifiutata. Usato per "Rifiuta" e per "Modifica gestita"
     * (l'admin contatta l'agenzia e applica le modifiche a mano).
     */
    public function resolveB2bRequest(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'note' => 'nullable|string|max:500',
        ]);

        if (($booking->metadata['b2b_request']['status'] ?? null) !== 'pending') {
            return back()->with('warning', 'Nessuna richiesta in attesa per questa prenotazione.');
        }

        $this->markB2bRequestResolved($booking, $data['decision'], $data['note'] ?? null);

        return back()->with('success', 'Richiesta dell\'agenzia '.($data['decision'] === 'approved' ? 'approvata' : 'rifiutata').'.');
    }

    /**
     * Aggiorna lo stato della richiesta b2b in metadata (pending → approved/rejected),
     * tracciando chi e quando. No-op se non c'è una richiesta pending.
     */
    private function markB2bRequestResolved(Booking $booking, string $decision, ?string $note = null): void
    {
        $req = $booking->metadata['b2b_request'] ?? null;
        if (! $req || ($req['status'] ?? null) !== 'pending') {
            return;
        }

        $req['status'] = $decision;
        $req['resolved_by_user_id'] = auth()->id();
        $req['resolved_at'] = now()->toIso8601String();
        if ($note !== null) {
            $req['resolution_note'] = $note;
        }

        $booking->update(['metadata' => array_merge($booking->metadata ?? [], ['b2b_request' => $req])]);

        BookingLog::info('b2b_request_resolved', 'Richiesta agenzia '.$decision, $booking, [
            'agency_id' => $booking->b2b_user_id,
            'decision' => $decision,
        ]);
    }
}
