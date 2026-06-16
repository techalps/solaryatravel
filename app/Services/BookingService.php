<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\DiscountCode;
use App\Models\Tour;
use App\Models\TourAgeBracket;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    /**
     * Crea una prenotazione su un tour, distribuendo automaticamente i posti
     * tra i catamarani disponibili (ottimizzazione: gruppo unito quando possibile).
     *
     * Input (nuovo modello adulti + bambini con DOB):
     *  - 'adults_count' int (>=1)
     *  - 'children' array<int, array{dob: 'Y-m-d', bracket_id?: int}>
     *
     * @param  array  $data  [
     *   'tour_id', 'tour_departure_id',
     *   'adults_count', 'children',
     *   'customer_first_name', 'customer_last_name', 'customer_email', ...,
     *   'addons' => [], 'discount_code' => null
     * ]
     */
    public function create(array $data, string $source = 'website'): Booking
    {
        return DB::transaction(function () use ($data, $source) {
            /** @var Tour $tour */
            $tour = Tour::findOrFail($data['tour_id']);

            $adminOverride = ! empty($data['admin_override']);

            // I tour "su richiesta" non sono prenotabili online (nessun prezzo / checkout).
            // L'admin però può registrarli manualmente indicando i prezzi a mano.
            if ($tour->booking_on_request && ! $adminOverride) {
                throw new \Exception('Questa crociera è su richiesta: contattaci via email o WhatsApp.');
            }

            /** @var TourDeparture $departure */
            $departure = TourDeparture::where('tour_id', $tour->id)
                ->lockForUpdate()
                ->findOrFail($data['tour_departure_id']);

            // Inserimento manuale da admin: consente partenze passate / non più
            // "scheduled" (registrazione retroattiva). I controlli di capacità
            // restano attivi (distributeSeats blocca l'overbooking più sotto).
            if (! $adminOverride && $departure->status !== 'scheduled') {
                throw new \Exception('Questa partenza non è disponibile.');
            }

            // Modalità legacy (admin form): accetta bracket_counts e converte in formato nuovo
            if (!isset($data['adults_count']) && !empty($data['bracket_counts'] ?? [])) {
                return $this->createFromBracketCounts($tour, $departure, $data, $source);
            }

            $adultsCount = (int) ($data['adults_count'] ?? 0);
            $children = $data['children'] ?? [];

            if ($adultsCount < 1) {
                throw new \Exception('Serve almeno un adulto per prenotare.');
            }

            // Tour "su richiesta": nessun listino/fascia. L'admin inserisce un unico
            // prezzo TOTALE (es. catamarano riservato); adulti/bambini contano solo i
            // posti. Il totale è attribuito al primo posto (intestatario), gli altri a 0.
            $manualPricing = $tour->booking_on_request && $adminOverride;

            if ($manualPricing) {
                // I bambini servono solo come conteggio posti (DOB facoltativa, niente prezzo).
                $resolvedChildren = [];
                foreach ($children as $child) {
                    $resolvedChildren[] = ['dob' => $child['dob'] ?? null, 'price' => 0];
                }

                $pricing = $this->pricingService->calculateManual(
                    $tour,
                    $adultsCount,
                    count($children),
                    (float) ($data['total_price'] ?? 0),
                    $data['addons'] ?? [],
                    $data['discount_code'] ?? null
                );
                $brackets = collect(); // nessun bracket per i tour su richiesta
            } else {
                // Risolvi bracket per ogni bambino (in base al DOB e alla data di partenza)
                $brackets = $this->pricingService
                    ->resolveBrackets($tour, $departure->departure_date)
                    ->keyBy('id');
                $resolvedChildren = [];
                foreach ($children as $child) {
                    $dob = $child['dob'] ?? null;
                    if (!$dob) {
                        throw new \Exception('Manca la data di nascita di un bambino.');
                    }
                    $bracket = $this->pricingService->resolveBracketForDob(
                        $brackets->values(),
                        $dob,
                        $departure->departure_date
                    );
                    if (!$bracket) {
                        throw new \Exception("Per la data di nascita {$dob} non è disponibile alcuna riduzione: aggiungilo come adulto.");
                    }
                    $resolvedChildren[] = ['dob' => $dob, 'bracket_id' => $bracket->id];
                }

                // Pricing
                $pricing = $this->pricingService->calculateForParticipants(
                    $tour,
                    $departure,
                    $adultsCount,
                    $resolvedChildren,
                    $data['addons'] ?? [],
                    $data['discount_code'] ?? null
                );
            }

            $countingSeats = $pricing['counting_seats'];
            if ($countingSeats <= 0) {
                throw new \Exception('Numero posti non valido.');
            }

            // Distribuzione posti: automatica, oppure forzata su un catamarano
            // specifico se l'admin l'ha scelto.
            $forcedCatamaranId = !empty($data['forced_catamaran_id'])
                ? (int) $data['forced_catamaran_id']
                : null;
            $assignment = $this->distributeSeats($tour, $departure, $countingSeats, $forcedCatamaranId);
            if ($assignment === null) {
                throw new \Exception($forcedCatamaranId !== null
                    ? 'Il catamarano selezionato non ha posti sufficienti (o non è disponibile) per questa partenza.'
                    : 'Posti insufficienti per questa partenza. Contattaci via email o WhatsApp per le alternative.');
            }

            // Modalità di pagamento: acconto e/o bonifico.
            $total = (float) $pricing['total_amount'];
            $paymentType = $data['payment_type'] ?? 'full';
            $useDeposit = ! empty($data['use_deposit']) || $paymentType === 'deposit';

            $depositAmount = null;
            $balanceAmount = null;
            $balanceDueAt = null;
            if ($useDeposit) {
                $depositAmount = round($total * \App\Support\Settings::depositPercentage() / 100, 2);
                $balanceAmount = round($total - $depositAmount, 2);
                $balanceDueAt = \Illuminate\Support\Carbon::parse($departure->departure_date)
                    ->setTimeFromTimeString((string) ($departure->start_time ?? '00:00'))
                    ->subHours(\App\Support\Settings::balanceDueHours());
            }

            // Stato iniziale: in admin lo stato è scelto esplicitamente (es. una
            // prenotazione retroattiva nasce già Confermata/Completata). Altrimenti
            // il bonifico parte in attesa incasso e gli altri in pending pagamento.
            $explicitStatus = $data['status'] ?? null;
            if ($explicitStatus instanceof BookingStatus) {
                $initialStatus = $explicitStatus;
            } elseif (is_string($explicitStatus) && $explicitStatus !== '') {
                $initialStatus = BookingStatus::from($explicitStatus);
            } else {
                $initialStatus = $paymentType === 'bank_transfer'
                    ? BookingStatus::AWAITING_TRANSFER
                    : BookingStatus::PENDING;
            }

            // Scadenza pagamento: rilevante solo finché la prenotazione resta in
            // attesa di pagamento. Se nasce già confermata (admin), nessuna scadenza.
            $paymentDeadline = match (true) {
                $initialStatus === BookingStatus::PENDING && $paymentType === 'bank_transfer'
                    => now()->addHours(\App\Support\Settings::bankTransferExpiryHours()),
                $initialStatus === BookingStatus::AWAITING_TRANSFER
                    => now()->addHours(\App\Support\Settings::bankTransferExpiryHours()),
                $initialStatus === BookingStatus::PENDING
                    => now()->addMinutes((int) config('booking.payment_expiry_minutes', 30)),
                default => null,
            };

            $booking = Booking::create([
                'user_id' => $data['user_id'] ?? auth()->id(),
                'tour_id' => $tour->id,
                'tour_departure_id' => $departure->id,
                'booking_date' => $departure->departure_date,
                'seats' => $countingSeats,
                'base_price' => $pricing['base_price'],
                'addons_total' => $pricing['addons_total'],
                'discount_amount' => $pricing['discount_amount'],
                'discount_code_id' => $pricing['discount_code_id'],
                'tax_amount' => $pricing['tax_amount'],
                'total_amount' => $pricing['total_amount'],
                'payment_type' => $paymentType,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
                'balance_due_at' => $balanceDueAt,
                'amount_paid' => 0,
                'currency' => 'EUR',
                'status' => $initialStatus,
                'customer_first_name' => $data['customer_first_name'],
                'customer_last_name' => $data['customer_last_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_country' => $data['customer_country'] ?? 'IT',
                'special_requests' => $data['special_requests'] ?? null,
                'payment_deadline' => $paymentDeadline,
                'source' => $source,
                'locale' => app()->getLocale(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'metadata' => [
                    'pricing' => $pricing,
                    'distribution' => $assignment,
                ],
            ]);

            // Pre-popola il primo adulto (prenotante) come guest
            $guests = $data['guests'] ?? [];
            if (empty($guests)) {
                $guests = [[
                    'first_name' => $data['customer_first_name'] ?? null,
                    'last_name' => $data['customer_last_name'] ?? null,
                    'date_of_birth' => null,
                ]];
            }

            // Crea booking_seats per ogni partecipante (adulti + bambini risolti).
            // Su richiesta: il prezzo totale finisce sul primo posto, gli altri a 0.
            $manualTotalOnFirstSeat = $manualPricing
                ? (float) ($pricing['manual_total_on_first_seat'] ?? 0)
                : null;
            $this->createParticipantSeats(
                $booking,
                $adultsCount,
                $manualPricing ? 0.0 : (float) $pricing['adult_unit_price'],
                $resolvedChildren,
                $brackets,
                $assignment,
                $guests,
                $manualTotalOnFirstSeat
            );

            // Addons
            if (!empty($data['addons'])) {
                foreach ($data['addons'] as $addonId) {
                    $addon = \App\Models\Addon::find($addonId);
                    if ($addon) {
                        $totalPrice = (float) $addon->calculatePrice($countingSeats, max(0.5, ($tour->duration_hours ?? 0) / 8));
                        $booking->addons()->create([
                            'addon_id' => $addonId,
                            'quantity' => $addon->price_type === 'per_person' ? $countingSeats : 1,
                            'unit_price' => $addon->price,
                            'total_price' => $totalPrice,
                        ]);
                    }
                }
            }

            // Aggiorna utilizzo discount code
            if ($pricing['discount_code_id']) {
                DiscountCode::find($pricing['discount_code_id'])?->increment('times_used');
            }

            return $booking->fresh(['seatRecords.catamaran', 'tour', 'departure']);
        });
    }

    /**
     * Legacy: crea una prenotazione dal vecchio formato bracket_counts
     * (usato dal form admin che permette di scegliere quantità per bracket).
     */
    protected function createFromBracketCounts(Tour $tour, TourDeparture $departure, array $data, string $source): Booking
    {
        $bracketCounts = $data['bracket_counts'] ?? [];
        if (empty(array_filter($bracketCounts))) {
            throw new \Exception('Devi selezionare almeno un partecipante.');
        }

        $pricing = $this->pricingService->calculate(
            $tour,
            $departure,
            $bracketCounts,
            $data['addons'] ?? [],
            $data['discount_code'] ?? null
        );

        $countingSeats = $pricing['counting_seats'];
        if ($countingSeats <= 0) {
            throw new \Exception('Numero posti non valido.');
        }

        $assignment = $this->distributeSeats($tour, $departure, $countingSeats);
        if ($assignment === null) {
            throw new \Exception('Posti insufficienti per questa partenza. Contattaci via email o WhatsApp per le alternative.');
        }

        $booking = Booking::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'tour_id' => $tour->id,
            'tour_departure_id' => $departure->id,
            'booking_date' => $departure->departure_date,
            'seats' => $countingSeats,
            'base_price' => $pricing['base_price'],
            'addons_total' => $pricing['addons_total'],
            'discount_amount' => $pricing['discount_amount'],
            'discount_code_id' => $pricing['discount_code_id'],
            'tax_amount' => $pricing['tax_amount'],
            'total_amount' => $pricing['total_amount'],
            'currency' => 'EUR',
            'status' => BookingStatus::PENDING,
            'customer_first_name' => $data['customer_first_name'],
            'customer_last_name' => $data['customer_last_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_country' => $data['customer_country'] ?? 'IT',
            'special_requests' => $data['special_requests'] ?? null,
            'payment_deadline' => now()->addMinutes(config('booking.payment_expiry_minutes', 30)),
            'source' => $source,
            'locale' => app()->getLocale(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => ['pricing' => $pricing, 'distribution' => $assignment],
        ]);

        $this->createSeats($booking, $bracketCounts, $assignment, $data['guests'] ?? []);

        if (!empty($data['addons'])) {
            foreach ($data['addons'] as $addonId) {
                $addon = \App\Models\Addon::find($addonId);
                if ($addon) {
                    $totalPrice = (float) $addon->calculatePrice($countingSeats, max(0.5, ($tour->duration_hours ?? 0) / 8));
                    $booking->addons()->create([
                        'addon_id' => $addonId,
                        'quantity' => $addon->price_type === 'per_person' ? $countingSeats : 1,
                        'unit_price' => $addon->price,
                        'total_price' => $totalPrice,
                    ]);
                }
            }
        }

        if ($pricing['discount_code_id']) {
            DiscountCode::find($pricing['discount_code_id'])?->increment('times_used');
        }

        return $booking->fresh(['seatRecords.catamaran', 'tour', 'departure']);
    }

    /**
     * Annulla una prenotazione (libera i posti).
     */
    public function cancel(Booking $booking, ?string $reason = null): bool
    {
        if (!$booking->canBeCancelled()) {
            throw new \Exception('Questa prenotazione non può essere annullata.');
        }

        return DB::transaction(function () use ($booking, $reason) {
            $booking->update([
                'status' => BookingStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // I seat records restano per audit ma la booking essendo cancelled
            // non conta più nelle disponibilità (vedi scope active e seatsBookedOnDeparture).

            if ($booking->discount_code_id) {
                DiscountCode::find($booking->discount_code_id)?->decrement('times_used');
            }

            return true;
        });
    }

    /**
     * Verifica disponibilità di una partenza per N posti.
     *
     * @return array{available:bool, message?:string, distribution?:array}
     */
    public function checkAvailability(TourDeparture $departure, int $seats): array
    {
        $departure->loadMissing('tour');
        $tour = $departure->tour;

        // Vincoli temporali
        $date = Carbon::parse($departure->departure_date);
        if ($date->lt(now()->startOfDay())) {
            return ['available' => false, 'message' => 'La data selezionata è nel passato.'];
        }

        $minAdvanceHours = (int) config('booking.advance_hours', 0);
        if ($minAdvanceHours > 0 && $date->diffInHours(now()) < $minAdvanceHours) {
            return ['available' => false, 'message' => "Serve prenotare con almeno {$minAdvanceHours} ore di anticipo."];
        }

        if ($departure->status !== 'scheduled') {
            return ['available' => false, 'message' => 'Partenza non disponibile.'];
        }

        if ($seats < ($tour->min_capacity ?? 1)) {
            return ['available' => false, 'message' => "Numero minimo partecipanti: {$tour->min_capacity}."];
        }
        if ($tour->max_capacity && $seats > $tour->max_capacity) {
            return ['available' => false, 'message' => "Numero massimo partecipanti: {$tour->max_capacity}."];
        }

        $assignment = $this->distributeSeats($tour, $departure, $seats);
        if ($assignment === null) {
            return ['available' => false, 'message' => 'Posti insufficienti per questa partenza. Contattaci via email o WhatsApp per le alternative.'];
        }

        return ['available' => true, 'distribution' => $assignment];
    }

    /**
     * Capacità residua totale per una partenza: somma dei posti liberi su tutti
     * i catamarani operativi e disponibili nella data (esclusi quelli bloccati),
     * tenendo conto dell'eventuale capacity_override della partenza.
     *
     * Stessa base di calcolo di distributeSeats(), così il limite mostrato in UI
     * coincide con ciò che il backend accetterà davvero.
     */
    public function remainingCapacity(TourDeparture $departure): int
    {
        $departure->loadMissing('tour');
        $tour = $departure->tour;
        if (!$tour) {
            return 0;
        }

        $departureDate = is_string($departure->departure_date)
            ? $departure->departure_date
            : $departure->departure_date->format('Y-m-d');

        $blockedIds = TourCatamaranBlock::where('tour_id', $tour->id)
            ->whereDate('start_date', '<=', $departureDate)
            ->whereDate('end_date', '>=', $departureDate)
            ->pluck('catamaran_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $totalFree = 0;
        foreach ($tour->operatingCatamarans() as $cat) {
            if (in_array((int) $cat->id, $blockedIds, true)) {
                continue;
            }
            if (!$cat->isAvailableOn($departure->departure_date)) {
                continue;
            }
            $booked = $cat->seatsBookedOnDeparture($departure->id);
            $totalFree += max(0, $cat->capacity - $booked);
        }

        if (!is_null($departure->capacity_override)) {
            $allowedRemaining = max(0, $departure->capacity_override - $departure->seats_booked);
            $totalFree = min($totalFree, $allowedRemaining);
        }

        return (int) $totalFree;
    }

    /**
     * Posti liberi sul catamarano singolo più capiente disponibile per la
     * partenza. Serve a capire se un gruppo "entra unito" da qualche parte.
     */
    public function largestSingleCatamaranFree(TourDeparture $departure): int
    {
        $departure->loadMissing('tour');
        $tour = $departure->tour;
        if (!$tour) {
            return 0;
        }

        $departureDate = is_string($departure->departure_date)
            ? $departure->departure_date
            : $departure->departure_date->format('Y-m-d');

        $blockedIds = TourCatamaranBlock::where('tour_id', $tour->id)
            ->whereDate('start_date', '<=', $departureDate)
            ->whereDate('end_date', '>=', $departureDate)
            ->pluck('catamaran_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $maxFree = 0;
        foreach ($tour->operatingCatamarans() as $cat) {
            if (in_array((int) $cat->id, $blockedIds, true)) {
                continue;
            }
            if (!$cat->isAvailableOn($departure->departure_date)) {
                continue;
            }
            $booked = $cat->seatsBookedOnDeparture($departure->id);
            $maxFree = max($maxFree, max(0, $cat->capacity - $booked));
        }

        return (int) $maxFree;
    }

    /**
     * Auto-distribuzione posti tra catamarani con ottimizzazione "gruppo unito".
     *
     * Strategia:
     *  1. Recupera catamarani operativi del tour disponibili nella data.
     *  2. Calcola posti liberi su ciascuno (capacity - già prenotati).
     *  3. Se UN catamarano basta da solo → assegna tutto lì (gruppo unito).
     *  4. Altrimenti riempi i catamarani in ordine decrescente di posti liberi
     *     (minimizza il numero di catamarani coinvolti).
     *
     * @return array<int, array{catamaran_id:int, seats:int}>|null
     */
    public function distributeSeats(Tour $tour, TourDeparture $departure, int $seats, ?int $forcedCatamaranId = null): ?array
    {
        $catamarans = $tour->operatingCatamarans();

        // Catamarani bloccati per questo tour nella data della partenza
        $departureDate = is_string($departure->departure_date)
            ? $departure->departure_date
            : $departure->departure_date->format('Y-m-d');
        $blockedIds = TourCatamaranBlock::where('tour_id', $tour->id)
            ->whereDate('start_date', '<=', $departureDate)
            ->whereDate('end_date', '>=', $departureDate)
            ->pluck('catamaran_id')
            ->all();

        $candidates = [];
        foreach ($catamarans as $cat) {
            // Catamarano forzato dall'admin: considera SOLO quello.
            if ($forcedCatamaranId !== null && (int) $cat->id !== $forcedCatamaranId) {
                continue;
            }
            // Salta catamarani bloccati per questo tour nella data
            if (in_array((int) $cat->id, array_map('intval', $blockedIds), true)) {
                continue;
            }
            // Salta catamarani non disponibili nella data (manutenzione, blocchi)
            if (!$cat->isAvailableOn($departure->departure_date)) {
                continue;
            }
            $booked = $cat->seatsBookedOnDeparture($departure->id);
            $free = max(0, $cat->capacity - $booked);
            if ($free <= 0) {
                continue;
            }
            $candidates[] = [
                'catamaran_id' => $cat->id,
                'free' => $free,
                'priority' => $cat->pivot->priority ?? $cat->sort_order ?? 0,
            ];
        }

        // Catamarano forzato ma non idoneo (inesistente per il tour, bloccato,
        // non disponibile o pieno): nessuna distribuzione possibile.
        if ($forcedCatamaranId !== null && empty($candidates)) {
            return null;
        }

        // Eventuale capacity_override sulla partenza
        if (!is_null($departure->capacity_override)) {
            $totalFree = array_sum(array_column($candidates, 'free'));
            $alreadyBooked = $departure->seats_booked;
            $allowedRemaining = max(0, $departure->capacity_override - $alreadyBooked);
            // Se l'override è più restrittivo, scaliamo proporzionalmente
            if ($allowedRemaining < $totalFree) {
                // Riduci la disponibilità totale a $allowedRemaining preservando l'ordine
                $remaining = $allowedRemaining;
                foreach ($candidates as &$c) {
                    if ($remaining <= 0) {
                        $c['free'] = 0;
                    } else {
                        $c['free'] = min($c['free'], $remaining);
                        $remaining -= $c['free'];
                    }
                }
                unset($c);
            }
        }

        $totalFree = array_sum(array_column($candidates, 'free'));
        if ($totalFree < $seats) {
            return null;
        }

        // 1) Tentativo "gruppo unito": catamarano singolo che ospita tutti
        // Preferisci il catamarano con MENO posti liberi che riesca a contenere
        // tutto il gruppo (best-fit), così lasciamo intatti i grandi.
        $singleFit = collect($candidates)
            ->filter(fn ($c) => $c['free'] >= $seats)
            ->sortBy('free')
            ->first();
        if ($singleFit) {
            return [['catamaran_id' => $singleFit['catamaran_id'], 'seats' => $seats]];
        }

        // 2) Split: riempi i catamarani con più posti liberi prima
        $sorted = collect($candidates)->sortByDesc('free')->values();
        $remaining = $seats;
        $assignment = [];
        foreach ($sorted as $c) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, $c['free']);
            if ($take > 0) {
                $assignment[] = ['catamaran_id' => $c['catamaran_id'], 'seats' => $take];
                $remaining -= $take;
            }
        }

        return $remaining === 0 ? $assignment : null;
    }

    /**
     * Crea i booking_seats nel nuovo modello adulti + bambini.
     * Gli adulti hanno tour_age_bracket_id NULL e price_paid = prezzo adulto del periodo.
     * I bambini riferiscono al loro bracket (riduzione) e salvano la DOB.
     *
     * @param  array<int, array{dob: string, bracket_id: int}>  $children
     * @param  \Illuminate\Support\Collection  $brackets  keyBy('id')
     * @param  array<int, array{catamaran_id:int, seats:int}>  $distribution
     */
    protected function createParticipantSeats(
        Booking $booking,
        int $adultsCount,
        float $adultUnitPrice,
        array $children,
        \Illuminate\Support\Collection $brackets,
        array $distribution,
        array $guests = [],
        ?float $manualTotalOnFirstSeat = null
    ): void {
        // Espandi la distribuzione in una queue di catamaran_id (uno per posto contante)
        $catamaranQueue = [];
        foreach ($distribution as $slot) {
            for ($i = 0; $i < $slot['seats']; $i++) {
                $catamaranQueue[] = $slot['catamaran_id'];
            }
        }

        $seatNumber = 1;
        $countingIdx = 0;
        $guestIdx = 0; // indice nell'array $guests (parallelo all'ordine: adulti, poi bambini come passati)

        // Adulti
        for ($a = 0; $a < $adultsCount; $a++) {
            $guest = $guests[$guestIdx] ?? [];
            // Su richiesta: tutto il totale sul primo posto, gli altri a 0.
            $seatPrice = $manualTotalOnFirstSeat !== null
                ? ($a === 0 ? $manualTotalOnFirstSeat : 0.0)
                : $adultUnitPrice;
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $catamaranQueue[$countingIdx] ?? null,
                'tour_age_bracket_id' => null,
                'price_paid' => $seatPrice,
                'guest_first_name' => $guest['first_name'] ?? null,
                'guest_last_name' => $guest['last_name'] ?? null,
                'guest_date_of_birth' => $guest['date_of_birth'] ?? null,
                'tax_code' => $a === 0 ? ($guest['tax_code'] ?? null) : null, // CF solo sull'intestatario
                'is_primary' => $a === 0,
            ]);
            $countingIdx++;
            $guestIdx++;
        }

        // Bambini: prima quelli che occupano un posto (counts_as_seat=true), poi gli infanti.
        // Manteniamo per ciascun bambino il riferimento all'indice originale così da recuperare
        // nome/cognome dall'array $guests (passato nell'ordine: adulti, poi bambini in ordine).
        $countingChildren = [];
        $nonCountingChildren = [];
        foreach ($children as $childIdx => $child) {
            // Tour con listino: bracket risolto. Tour "su richiesta": nessun bracket,
            // prezzo manuale dal campo 'price' e il bambino occupa sempre un posto.
            $bracket = isset($child['bracket_id']) ? $brackets->get($child['bracket_id']) : null;
            $manual = $bracket === null && array_key_exists('price', $child);
            if (!$bracket && !$manual) {
                continue;
            }
            $guest = $guests[$adultsCount + $childIdx] ?? [];
            $entry = ['child' => $child, 'bracket' => $bracket, 'guest' => $guest];
            if (!$bracket || $bracket->counts_as_seat) {
                $countingChildren[] = $entry;
            } else {
                $nonCountingChildren[] = $entry;
            }
        }

        foreach ($countingChildren as $entry) {
            $bracket = $entry['bracket'];
            $price = $bracket
                ? (float) $bracket->price * (float) $booking->departure->price_modifier
                : (float) ($entry['child']['price'] ?? 0); // prezzo manuale (su richiesta)
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $catamaranQueue[$countingIdx] ?? null,
                'tour_age_bracket_id' => $bracket?->id,
                'price_paid' => $price,
                'guest_first_name' => $entry['guest']['first_name'] ?? ($entry['child']['first_name'] ?? null),
                'guest_last_name' => $entry['guest']['last_name'] ?? ($entry['child']['last_name'] ?? null),
                'guest_date_of_birth' => $entry['child']['dob'] ?? null,
                'is_primary' => false,
            ]);
            $countingIdx++;
        }

        // Infanti in braccio: stesso catamarano del primo posto
        $defaultCatamaran = $catamaranQueue[0] ?? null;
        foreach ($nonCountingChildren as $entry) {
            $bracket = $entry['bracket'];
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $defaultCatamaran,
                'tour_age_bracket_id' => $bracket->id,
                'price_paid' => (float) $bracket->price * (float) $booking->departure->price_modifier,
                'guest_first_name' => $entry['guest']['first_name'] ?? ($entry['child']['first_name'] ?? null),
                'guest_last_name' => $entry['guest']['last_name'] ?? ($entry['child']['last_name'] ?? null),
                'guest_date_of_birth' => $entry['child']['dob'],
                'is_primary' => false,
            ]);
        }
    }

    /**
     * Legacy: crea i booking_seats da un dizionario bracket_id => quantità.
     * Mantenuto per eventuali importazioni o vecchie integrazioni.
     *
     * @param  array<int,int>  $bracketCounts
     * @param  array<int, array{catamaran_id:int, seats:int}>  $distribution
     * @param  array  $guests
     */
    protected function createSeats(Booking $booking, array $bracketCounts, array $distribution, array $guests = []): void
    {
        // Espandi la distribuzione in una lista di catamaran_id (uno per posto contante)
        $catamaranQueue = [];
        foreach ($distribution as $slot) {
            for ($i = 0; $i < $slot['seats']; $i++) {
                $catamaranQueue[] = $slot['catamaran_id'];
            }
        }

        // Espandi i bracket in una lista di bracket_id (uno per partecipante totale)
        // Mantieni l'ordine: bracket "counts_as_seat=true" prima per matchare la queue
        $brackets = $this->pricingService
            ->resolveBrackets($booking->tour, $booking->departure->departure_date)
            ->whereIn('id', array_keys($bracketCounts))
            ->keyBy('id');

        $countingList = [];
        $nonCountingList = [];
        foreach ($bracketCounts as $bracketId => $count) {
            $bracket = $brackets->get($bracketId);
            if (!$bracket || $count <= 0) {
                continue;
            }
            for ($i = 0; $i < $count; $i++) {
                $entry = ['bracket' => $bracket];
                if ($bracket->counts_as_seat) {
                    $countingList[] = $entry;
                } else {
                    $nonCountingList[] = $entry;
                }
            }
        }

        $seatNumber = 1;

        // Posti contanti → assegnati a catamarano dalla queue
        foreach ($countingList as $idx => $entry) {
            $bracket = $entry['bracket'];
            $guest = $guests[$idx] ?? [];
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $catamaranQueue[$idx] ?? null,
                'tour_age_bracket_id' => $bracket->id,
                'price_paid' => (float) $bracket->price * (float) $booking->departure->price_modifier,
                'guest_first_name' => $guest['first_name'] ?? null,
                'guest_last_name' => $guest['last_name'] ?? null,
                'guest_date_of_birth' => $guest['date_of_birth'] ?? null,
                'is_primary' => $idx === 0,
            ]);
        }

        // Posti non contanti (es. neonati) → seguono il catamarano del primo posto contante
        $defaultCatamaran = $catamaranQueue[0] ?? null;
        foreach ($nonCountingList as $entry) {
            $bracket = $entry['bracket'];
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $defaultCatamaran,
                'tour_age_bracket_id' => $bracket->id,
                'price_paid' => (float) $bracket->price * (float) $booking->departure->price_modifier,
                'is_primary' => false,
            ]);
        }
    }

    /**
     * Sposta un singolo posto da un catamarano all'altro.
     * Solleva eccezione se il catamarano destinazione è pieno.
     */
    public function moveSeat(BookingSeat $seat, int $newCatamaranId): BookingSeat
    {
        return DB::transaction(function () use ($seat, $newCatamaranId) {
            $booking = $seat->booking;
            $departure = $booking->departure;
            $catamaran = Catamaran::findOrFail($newCatamaranId);

            $booked = $catamaran->seatsBookedOnDeparture($departure->id);
            // Esclude il seat corrente se già su questo catamarano
            if ($seat->catamaran_id === $catamaran->id) {
                return $seat;
            }
            if ($booked >= $catamaran->capacity) {
                throw new \Exception("Catamarano {$catamaran->name} pieno per questa partenza.");
            }

            if (!$catamaran->isAvailableOn($departure->departure_date)) {
                throw new \Exception("Catamarano {$catamaran->name} non disponibile nella data.");
            }

            $seat->catamaran_id = $catamaran->id;
            $seat->save();
            return $seat->fresh('catamaran');
        });
    }
}
