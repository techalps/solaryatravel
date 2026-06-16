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
use App\Support\Settings;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $query = Booking::with(['tour', 'departure', 'payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tour')) {
            $query->where('tour_id', $request->tour);
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

        $bookings = $query->orderBy('booking_date', 'desc')->paginate(20)->withQueryString();
        $tours = Tour::orderBy('name')->get();
        $statuses = BookingStatus::cases();
        $stats = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();

        return view('admin.bookings.index', compact('bookings', 'tours', 'statuses', 'stats'));
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

        return view('admin.bookings.create', compact('tours', 'selectedTour', 'departures', 'statuses'));
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
            $blockedIds = \App\Models\TourCatamaranBlock::where('tour_id', $tour->id)
                ->whereDate('start_date', '<=', $isoDate)
                ->whereDate('end_date', '>=', $isoDate)
                ->pluck('catamaran_id')
                ->map(fn ($id) => (int) $id)
                ->all();

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

            return [
                // id reale (numerico) se la partenza esiste, altrimenti id sintetico.
                'id' => $d ? (string) $d->id : ('virt:' . $isoDate . ':' . $time),
                'iso_date' => $isoDate,
                'date' => \Carbon\Carbon::parse($isoDate)->format('d/m/Y'),
                'time' => $time,
                'available' => $d ? $d->seats_available : null,
                'capacity' => $d ? $d->capacity : $tour->total_capacity,
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
            ],
            'departures' => $departures,
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
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_country' => 'nullable|string|max:5',
            'customer_tax_code' => 'nullable|string|max:16',
            'special_requests' => 'nullable|string|max:1000',
            'status' => 'required|in:' . implode(',', $statusValues),
            // Catamarano: opzionale (vuoto = distribuzione automatica).
            'catamaran_id' => 'nullable|integer|exists:catamarans,id',
            // Bloccare il catamarano per l'intera giornata della partenza.
            'block_catamaran_day' => 'nullable|boolean',
        ]);

        $status = BookingStatus::from($validated['status']);

        // Tour "su richiesta": prezzi inseriti a mano dall'admin (niente listino).
        $tour = Tour::findOrFail($validated['tour_id']);
        $isOnRequest = (bool) $tour->booking_on_request;
        if ($isOnRequest && ($validated['total_price'] ?? null) === null) {
            return back()->withInput()->with('error', 'Per un tour su richiesta devi indicare il prezzo totale.');
        }

        // Risolvi l'id partenza: reale (numerico) o virtuale ("virt:Y-m-d:H:i").
        // La virtuale viene materializzata ora con firstOrCreate (stessa logica
        // del flusso pubblico), così la prenotazione referenzia una riga vera.
        try {
            $departureId = $this->resolveDepartureId(
                (int) $validated['tour_id'],
                (string) $validated['tour_departure_id']
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
            // Prezzo TOTALE manuale (usato solo per i tour su richiesta).
            'total_price' => $isOnRequest ? (float) ($validated['total_price'] ?? 0) : null,
            // Catamarano forzato (vuoto = distribuzione automatica del service).
            'forced_catamaran_id' => $validated['catamaran_id'] ?? null,
        ];

        try {
            $booking = $this->bookingService->create($payload, 'admin');

            // Blocco catamarano per l'intera giornata, se richiesto: blocca i
            // catamarani effettivamente assegnati a questa prenotazione (quello
            // forzato dall'admin oppure quelli scelti dalla distribuzione automatica).
            if ($request->boolean('block_catamaran_day')) {
                $date = $booking->booking_date->toDateString();
                $catamaranIds = $booking->seatRecords()
                    ->whereNotNull('catamaran_id')
                    ->pluck('catamaran_id')
                    ->unique();
                foreach ($catamaranIds as $catId) {
                    \App\Models\TourCatamaranBlock::firstOrCreate(
                        [
                            'tour_id' => $booking->tour_id,
                            'catamaran_id' => $catId,
                            'start_date' => $date,
                            'end_date' => $date,
                        ],
                        ['reason' => 'Bloccato da prenotazione admin #' . $booking->booking_number]
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

            // Email in base allo stato scelto:
            //  - PENDING → invia il link di pagamento Stripe al cliente;
            //  - CONFIRMED → pagamento già incassato off-platform: invia i biglietti;
            //  - altri stati (deposito/bonifico/completata/...) → nessuna email automatica.
            if ($status === BookingStatus::PENDING) {
                $emailSent = $this->sendPaymentLinkEmail($booking);
                $message = $emailSent
                    ? 'Prenotazione creata. Email con link di pagamento inviata al cliente.'
                    : 'Prenotazione creata, ma l\'invio dell\'email è fallito (controlla il log).';
            } elseif ($status === BookingStatus::CONFIRMED) {
                $this->sendTicketsEmail($booking);
                $message = 'Prenotazione creata e confermata. Biglietti inviati al cliente.';
            } else {
                $message = 'Prenotazione creata con stato "' . $status->label() . '".';
            }

            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Risolve l'id partenza inviato dal form admin in un id REALE di tour_departures.
     *
     * Accetta:
     *  - un id numerico esistente (partenza già materializzata);
     *  - un id sintetico "virt:Y-m-d:H:i" (partenza virtuale da periodo): viene
     *    verificata contro i periodi del tour e materializzata con firstOrCreate.
     *
     * @throws \RuntimeException se la partenza non è valida per il tour.
     */
    protected function resolveDepartureId(int $tourId, string $rawId): int
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

        $startTime = $time . ':00';
        $endTime = \Carbon\Carbon::parse($startTime)
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

        return $dep->id;
    }

    /**
     * Genera la sessione Stripe e spedisce l'email con il link di pagamento.
     */
    protected function sendPaymentLinkEmail(Booking $booking): bool
    {
        try {
            $session = $this->paymentService->createCheckoutSession($booking);
            $booking->update([
                'checkout_url' => $session['url'],
                'payment_link_sent_at' => now(),
            ]);
            Mail::to($booking->customer_email)->send(new BookingPaymentLink($booking, $session['url']));
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
            'discountCode',
            'seatRecords.catamaran',
            'seatRecords.ageBracket',
        ]);
        $catamarans = Catamaran::active()->ordered()->get();
        return view('admin.bookings.show', compact('booking', 'catamarans'));
    }

    public function edit(Booking $booking): View
    {
        $booking->load(['tour', 'departure', 'seatRecords.catamaran', 'seatRecords.ageBracket']);
        $catamarans = Catamaran::active()->ordered()->get();
        $statuses = BookingStatus::cases();
        return view('admin.bookings.edit', compact('booking', 'catamarans', 'statuses'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:' . implode(',', array_column(BookingStatus::cases(), 'value')),
            'special_requests' => 'nullable|string|max:1000',
            'customer_phone' => 'nullable|string|max:30',
        ]);
        $booking->update($validated);
        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Prenotazione aggiornata.');
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

            return back()->with('success', $msg);
        } catch (\Exception $e) {
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
}
