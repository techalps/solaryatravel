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
                    $data['discount_code'] ?? null,
                    // Posti omaggio concessi dall'admin: occupano il posto in
                    // barca ma valgono 0€.
                    max(0, (int) ($data['complimentary_seats'] ?? 0)),
                    ! empty($data['complimentary_includes_addons'])
                );
            }

            $countingSeats = $pricing['counting_seats'];
            if ($countingSeats <= 0) {
                throw new \Exception('Numero posti non valido.');
            }

            // Distribuzione posti: automatica, oppure forzata su uno o più catamarani
            // se l'admin li ha scelti. In uso esclusivo è consentito superare la
            // capienza dei catamarani scelti (allowOverflow).
            $exclusive = !empty($data['exclusive_use']);
            $forcedCatamaranIds = !empty($data['forced_catamaran_ids'])
                ? array_map('intval', (array) $data['forced_catamaran_ids'])
                // Retrocompatibilità: vecchio singolo id.
                : (!empty($data['forced_catamaran_id']) ? [(int) $data['forced_catamaran_id']] : null);

            $assignment = $this->distributeSeats($tour, $departure, $countingSeats, $forcedCatamaranIds, $exclusive);
            if ($assignment === null) {
                \App\Support\BookingLog::warning('booking_create', 'Posti insufficienti: distribuzione fallita', null, [
                    'source' => $source,
                    'tour_id' => $tour->id,
                    'departure_id' => $departure->id,
                    'seats_requested' => $countingSeats,
                    'forced_catamaran_ids' => $forcedCatamaranIds,
                    'exclusive' => $exclusive,
                ]);
                throw new \Exception($forcedCatamaranIds !== null
                    ? 'I catamarani selezionati non hanno posti sufficienti (o non sono disponibili) per questa partenza.'
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
                // Scadenza saldo in GIORNI prima della partenza (impostabile in
                // admin, default 3): coincide con l'avviso mostrato al cliente
                // in fase di prenotazione. Fine giornata, così "entro 3 giorni"
                // vale per tutto il terzo giorno che precede la partenza.
                $balanceDueAt = \Illuminate\Support\Carbon::parse($departure->departure_date)
                    ->startOfDay()
                    ->subDays(\App\Support\Settings::balanceDueDays())
                    ->endOfDay();
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
                // Se il chiamante passa esplicitamente user_id (anche null, es. admin
                // che prenota per un cliente), lo rispettiamo; altrimenti self-booking.
                'user_id' => array_key_exists('user_id', $data) ? $data['user_id'] : auth()->id(),
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
                    // Traccia dell'omaggio: quanti posti, quanto valeva, chi
                    // l'ha concesso e perché. Serve per report e verifiche.
                    'complimentary' => ($pricing['complimentary_seats'] ?? 0) > 0 ? [
                        'seats' => $pricing['complimentary_seats'],
                        'amount' => $pricing['complimentary_amount'] ?? 0,
                        'includes_addons' => (bool) ($pricing['complimentary_includes_addons'] ?? false),
                        'reason' => $data['complimentary_reason'] ?? null,
                        'granted_by' => auth()->id(),
                        'granted_at' => now()->toDateTimeString(),
                    ] : null,
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

            // Posti omaggio: azzera i più costosi DOPO la creazione (i posti
            // nascono in tre passaggi: adulti, bambini, infanti).
            $complimentarySeats = max(0, (int) ($data['complimentary_seats'] ?? 0));
            if ($complimentarySeats > 0) {
                $this->markComplimentarySeats($booking, $complimentarySeats);
            }

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

            \App\Support\BookingLog::info('booking_create', 'Prenotazione creata', $booking, [
                'source' => $source,
                'tour_id' => $tour->id,
                'departure_id' => $departure->id,
                'seats' => $booking->seats,
                'total_amount' => (float) $booking->total_amount,
                'payment_type' => $booking->payment_type,
            ]);

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

        // Posti omaggio concessi dall'admin: occupano il posto ma valgono 0€.
        $complimentarySeats = max(0, (int) ($data['complimentary_seats'] ?? 0));
        $complimentaryIncludesAddons = ! empty($data['complimentary_includes_addons']);

        $pricing = $this->pricingService->calculate(
            $tour,
            $departure,
            $bracketCounts,
            $data['addons'] ?? [],
            $data['discount_code'] ?? null,
            $complimentarySeats,
            $complimentaryIncludesAddons
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
            'metadata' => [
                'pricing' => $pricing,
                'distribution' => $assignment,
                // Traccia dell'omaggio: chi l'ha concesso, quanti posti e perché.
                'complimentary' => $complimentarySeats > 0 ? [
                    'seats' => $complimentarySeats,
                    'amount' => $pricing['complimentary_amount'] ?? 0,
                    'includes_addons' => $complimentaryIncludesAddons,
                    'reason' => $data['complimentary_reason'] ?? null,
                    'granted_by' => auth()->id(),
                    'granted_at' => now()->toDateTimeString(),
                ] : null,
            ],
        ]);

        $this->createSeats($booking, $bracketCounts, $assignment, $data['guests'] ?? [], $complimentarySeats);

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
     * Sposta una prenotazione su una nuova partenza, ricalcolando i prezzi dei
     * posti ATTIVI in base al periodo della nuova data. Non tocca i pagamenti:
     * restituisce i totali prima/dopo e la differenza (positiva = da incassare).
     *
     * Per i tour "su richiesta" (prezzo manuale) NON riprezza: il totale resta
     * quello inserito a mano e la differenza è 0 (salvo modifica esplicita del prezzo).
     *
     * @return array{old_total:float, new_total:float, difference:float}
     */
    public function reschedule(Booking $booking, TourDeparture $newDeparture): array
    {
        return DB::transaction(function () use ($booking, $newDeparture) {
            $tour = $booking->tour;
            $oldTotal = (float) $booking->total_amount;

            // Tour su richiesta: prezzo manuale, niente riprezzatura per periodo.
            if ($tour?->booking_on_request) {
                $booking->update([
                    'tour_departure_id' => $newDeparture->id,
                    'booking_date' => $newDeparture->departure_date,
                ]);
                return ['old_total' => round($oldTotal, 2), 'new_total' => round($oldTotal, 2), 'difference' => 0.0];
            }

            $period = $this->pricingService->resolvePeriod($tour, $newDeparture->departure_date);
            $modifier = (float) $newDeparture->price_modifier;
            $adultUnit = $period ? (float) $period->base_price * $modifier : 0.0;

            // Fasce d'età valide per la nuova data.
            $brackets = $this->pricingService
                ->resolveBrackets($tour, $newDeparture->departure_date)
                ->keyBy('id');

            // Riprezza ogni posto ATTIVO sul nuovo periodo.
            foreach ($booking->seatRecords()->whereNull('cancelled_at')->get() as $seat) {
                if ($seat->tour_age_bracket_id === null) {
                    // Adulto: prezzo pieno del periodo.
                    $seat->update(['price_paid' => round($adultUnit, 2)]);
                    continue;
                }
                // Bambino: prova a mantenere la stessa fascia sulla nuova data; se non
                // esiste più, risolvila in base alla DOB.
                $bracket = $brackets->get($seat->tour_age_bracket_id);
                if (!$bracket && $seat->guest_date_of_birth) {
                    $bracket = $this->pricingService->resolveBracketForDob(
                        $brackets->values(),
                        $seat->guest_date_of_birth->toDateString(),
                        $newDeparture->departure_date
                    );
                }
                if ($bracket) {
                    $seat->update([
                        'tour_age_bracket_id' => $bracket->id,
                        'price_paid' => round((float) $bracket->price * $modifier, 2),
                    ]);
                }
            }

            // Sposta la prenotazione e ricalcola i totali (sconto/IVA mantenuti).
            $booking->update([
                'tour_departure_id' => $newDeparture->id,
                'booking_date' => $newDeparture->departure_date,
            ]);
            $newTotal = $booking->fresh()->recalculateTotals();

            \App\Support\BookingLog::info('booking_reschedule', 'Prenotazione riprogrammata', $booking, [
                'new_departure_id' => $newDeparture->id,
                'new_date' => $newDeparture->departure_date?->toDateString(),
                'old_total' => round($oldTotal, 2),
                'new_total' => round($newTotal, 2),
                'difference' => round($newTotal - $oldTotal, 2),
            ]);

            return [
                'old_total' => round($oldTotal, 2),
                'new_total' => round($newTotal, 2),
                'difference' => round($newTotal - $oldTotal, 2),
            ];
        });
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

            // Rilascia le riserve di uso esclusivo di QUESTA prenotazione.
            // Senza questo il blocco sopravviveva all'annullamento e il
            // catamarano restava invendibile per sempre su quella data
            // ("blocco orfano"): in produzione ne sono stati trovati 4.
            $releasedBlocks = $this->releaseExclusiveBlocks($booking);

            if ($booking->discount_code_id) {
                DiscountCode::find($booking->discount_code_id)?->decrement('times_used');
            }

            \App\Support\BookingLog::info('booking_cancel', 'Prenotazione annullata (posti liberati)', $booking, [
                'reason' => $reason,
                'seats' => $booking->seats,
                'released_blocks' => $releasedBlocks,
            ]);

            return true;
        });
    }

    /**
     * Azzera il prezzo di N posti della prenotazione: i più costosi per primi.
     *
     * Applicato DOPO la creazione dei posti invece che dentro i cicli: i posti
     * nascono in tre passaggi diversi (adulti, bambini, infanti) e replicare la
     * scelta in ognuno sarebbe fragile. Così l'ordine è garantito e coincide con
     * quello di PricingService::applyComplimentarySeats().
     *
     * @return float importo omaggiato
     */
    protected function markComplimentarySeats(Booking $booking, int $complimentarySeats): float
    {
        if ($complimentarySeats <= 0) {
            return 0.0;
        }

        $seats = $booking->seatRecords()
            ->orderByDesc('price_paid')
            ->orderBy('seat_number')
            ->limit($complimentarySeats)
            ->get();

        $amount = 0.0;

        foreach ($seats as $seat) {
            $amount += (float) $seat->price_paid;
            $seat->update(['price_paid' => 0]);
        }

        return round($amount, 2);
    }

    /**
     * Rilascia i blocchi di uso esclusivo creati da una prenotazione.
     *
     * I blocchi sono legati alla prenotazione dal numero scritto nel campo
     * reason ("… #SLY-2026-00044"). Annullamento e rimborso devono rilasciarli:
     * altrimenti il catamarano resta bloccato su quella data pur non essendoci
     * più una prenotazione attiva, e diventa invendibile ovunque (calendario
     * pubblico, admin, uso esclusivo).
     *
     * @return int quanti blocchi sono stati rilasciati
     */
    public function releaseExclusiveBlocks(Booking $booking): int
    {
        if (! $booking->booking_number) {
            return 0;
        }

        return (int) TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->delete();
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

        // Blocco GLOBALE per catamarano: una barca riservata è occupata per qualsiasi tour.
        [$winStart, $winEnd] = $this->departureTimeWindow($departure);
        $blockedIds = TourCatamaranBlock::blockedCatamaranIdsOn($departureDate, $winStart, $winEnd);

        $totalFree = 0;
        foreach ($tour->operatingCatamarans() as $cat) {
            if (in_array((int) $cat->id, $blockedIds, true)) {
                continue;
            }
            if (!$cat->isAvailableOn($departure->departure_date)) {
                continue;
            }
            // Conteggio globale per catamarano su questa fascia oraria: la barca
            // è fisica, se è già piena per un altro tour in orario sovrapposto
            // non ha posti da vendere. Slot disgiunti nello stesso giorno
            // (Daily la mattina, Sunset la sera) restano compatibili.
            $booked = $cat->seatsBookedOnDate($departureDate, $winStart, $winEnd);
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

        // Blocco GLOBALE per catamarano: una barca riservata è occupata per qualsiasi tour.
        [$winStart, $winEnd] = $this->departureTimeWindow($departure);
        $blockedIds = TourCatamaranBlock::blockedCatamaranIdsOn($departureDate, $winStart, $winEnd);

        $maxFree = 0;
        foreach ($tour->operatingCatamarans() as $cat) {
            if (in_array((int) $cat->id, $blockedIds, true)) {
                continue;
            }
            if (!$cat->isAvailableOn($departure->departure_date)) {
                continue;
            }
            // Vedi remainingCapacity(): conteggio globale per fascia oraria.
            $booked = $cat->seatsBookedOnDate($departureDate, $winStart, $winEnd);
            $maxFree = max($maxFree, max(0, $cat->capacity - $booked));
        }

        return (int) $maxFree;
    }

    /**
     * Disponibilità posti per singolo catamarano operativo/disponibile nella
     * partenza (esclusi quelli bloccati). Serve a mostrare in UI quanti posti ci
     * sono su ogni barca, così l'utente sa in anticipo se il gruppo verrà diviso.
     *
     * @return array<int, array{name: string, capacity: int, free: int}>
     */
    public function catamaranAvailabilityList(TourDeparture $departure): array
    {
        $departure->loadMissing('tour');
        $tour = $departure->tour;
        if (!$tour) {
            return [];
        }

        $departureDate = is_string($departure->departure_date)
            ? $departure->departure_date
            : $departure->departure_date->format('Y-m-d');

        [$winStart, $winEnd] = $this->departureTimeWindow($departure);
        $blockedIds = TourCatamaranBlock::blockedCatamaranIdsOn($departureDate, $winStart, $winEnd);

        $list = [];
        foreach ($tour->operatingCatamarans() as $cat) {
            if (in_array((int) $cat->id, $blockedIds, true)) {
                continue;
            }
            if (!$cat->isAvailableOn($departure->departure_date)) {
                continue;
            }
            $booked = $cat->seatsBookedOnDeparture($departure->id);
            $free = max(0, $cat->capacity - $booked);
            $list[] = [
                'id' => (int) $cat->id,
                'name' => $cat->name,
                'capacity' => (int) $cat->capacity,
                'free' => (int) $free,
            ];
        }

        // Ordina dal più capiente al meno: il primo è dove "entra unito" un gruppo.
        usort($list, fn ($a, $b) => $b['free'] <=> $a['free']);

        return $list;
    }

    /**
     * Prenotazioni ATTIVE che impedirebbero di bloccare (uso esclusivo) i catamarani
     * indicati nel periodo [start..end]. Una prenotazione è in conflitto se:
     *  - è dello stesso tour ed è attiva (non annullata/rimborsata/no-show);
     *  - la sua data partenza cade nel periodo di blocco;
     *  - occupa almeno uno dei catamarani che si vogliono bloccare.
     *
     * @param  array<int,int>  $catamaranIds
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    public function conflictingBookingsForBlock(
        int $tourId,
        array $catamaranIds,
        string $startDate,
        string $endDate,
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $excludeBookingId = null
    ): \Illuminate\Support\Collection {
        $catamaranIds = array_values(array_filter(array_map('intval', $catamaranIds)));
        if (empty($catamaranIds)) {
            return collect();
        }

        // Conflitti GLOBALI: un catamarano con una prenotazione attiva (su qualsiasi
        // tour) nel periodo non è bloccabile. $tourId non filtra più i conflitti.
        // $excludeBookingId: esclude la prenotazione stessa (es. durante un cambio data).

        // Fonte 1: prenotazioni con DATA nel periodo e posti sui catamarani indicati.
        $byDate = Booking::query()
            ->active()
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->whereDate('booking_date', '>=', $startDate)
            ->whereDate('booking_date', '<=', $endDate)
            ->whereHas('seatRecords', fn ($q) => $q->whereIn('catamaran_id', $catamaranIds))
            ->with(['seatRecords' => fn ($q) => $q->whereIn('catamaran_id', $catamaranIds), 'tour', 'departure'])
            ->get();

        // Fonte 2: prenotazioni a USO ESCLUSIVO il cui BLOCCO si sovrappone al periodo
        // (anche se la loro data di partenza è fuori dall'intervallo). Le ricaviamo
        // dal numero prenotazione nel campo reason dei blocchi che si sovrappongono.
        $overlapBlocks = TourCatamaranBlock::whereIn('catamaran_id', $catamaranIds)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();
        $blockNumbers = $overlapBlocks->map(fn ($b) =>
            preg_match('/#(\S+)/', (string) $b->reason, $m) ? $m[1] : null
        )->filter()->unique()->values();

        $byBlock = collect();
        if ($blockNumbers->isNotEmpty()) {
            $byBlock = Booking::query()
                ->active()
                ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
                ->whereIn('booking_number', $blockNumbers)
                ->with(['seatRecords' => fn ($q) => $q->whereIn('catamaran_id', $catamaranIds), 'tour', 'departure'])
                ->get();
        }

        $bookings = $byDate->concat($byBlock)->unique('id')->values();

        // Se è indicata una fascia oraria, tieni solo le prenotazioni la cui finestra
        // di OCCUPAZIONE (data+ora inizio → data+ora fine) si sovrappone a quella
        // richiesta. Finestre disgiunte (es. mattina/pomeriggio dello stesso giorno,
        // o periodi su giorni diversi) non collidono.
        if ($startTime !== null && $endTime !== null) {
            $reqStart = \Carbon\Carbon::parse($startDate . ' ' . $startTime);
            $reqEnd = \Carbon\Carbon::parse($endDate . ' ' . $endTime);

            // Finestra reale dal BLOCCO esclusivo (date+orari), per prenotazione.
            $blockWindows = TourCatamaranBlock::whereIn('catamaran_id', $catamaranIds)
                ->where(function ($q) use ($bookings) {
                    foreach ($bookings as $b) {
                        $q->orWhere('reason', 'like', '%#' . $b->booking_number . '%');
                    }
                    $q->orWhereRaw('1 = 0');
                })
                ->get(['reason', 'start_date', 'end_date', 'start_time', 'end_time']);

            $bookings = $bookings->filter(function ($b) use ($reqStart, $reqEnd, $blockWindows) {
                $blk = $blockWindows->first(fn ($w) =>
                    $w->reason && str_contains($w->reason, '#' . $b->booking_number)
                    && !empty($w->start_time) && !empty($w->end_time));

                if ($blk) {
                    // Periodo del blocco: data inizio+ora → data fine+ora.
                    $bs = \Carbon\Carbon::parse($blk->start_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($blk->start_time)->format('H:i'));
                    $be = \Carbon\Carbon::parse($blk->end_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($blk->end_time)->format('H:i'));
                } elseif ($b->departure) {
                    [$st, $et] = $this->departureTimeWindow($b->departure);
                    if ($st === null || $et === null) {
                        return true;
                    }
                    $day = $b->booking_date->format('Y-m-d');
                    $bs = \Carbon\Carbon::parse($day . ' ' . $st);
                    $be = \Carbon\Carbon::parse($day . ' ' . $et);
                } else {
                    return true; // niente info → prudenzialmente in conflitto
                }
                // Sovrapposizione di intervalli [bs,be) vs [reqStart,reqEnd).
                return $reqStart->lt($be) && $bs->lt($reqEnd);
            })->values();
        }

        return $bookings;
    }

    /**
     * Finestra oraria [start, end] di una partenza in formato 'H:i'.
     * L'orario di fine è end_time, oppure start_time + durata del tour come fallback.
     *
     * @return array{0:?string,1:?string}
     */
    /** Minuti dall'inizio giornata da 'HH:MM' o 'HH:MM:SS'. */
    protected function minutesOfDay(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');
        return ((int) $h) * 60 + (int) $m;
    }

    protected function departureTimeWindow(TourDeparture $departure): array
    {
        $start = $departure->start_time
            ? \Carbon\Carbon::parse($departure->start_time)->format('H:i')
            : null;

        if (!$start) {
            return [null, null];
        }

        if ($departure->end_time) {
            $end = \Carbon\Carbon::parse($departure->end_time)->format('H:i');
        } else {
            $durationMin = (int) round(((float) ($departure->tour?->duration_hours ?? 1)) * 60);
            $end = \Carbon\Carbon::parse($departure->start_time)->addMinutes($durationMin)->format('H:i');
        }

        return [$start, $end];
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
    /**
     * @param  int|array<int,int>|null  $forcedCatamaranIds  uno o più catamarani da
     *         usare obbligatoriamente (admin). Null = distribuzione automatica.
     * @param  bool  $allowOverflow  uso esclusivo: consenti di superare la capienza
     *         totale dei catamarani scelti (il gruppo viene comunque ripartito su di essi).
     */
    public function distributeSeats(Tour $tour, TourDeparture $departure, int $seats, int|array|null $forcedCatamaranIds = null, bool $allowOverflow = false): ?array
    {
        $catamarans = $tour->operatingCatamarans();

        // Normalizza i catamarani forzati in un set di interi (o null).
        $forcedSet = null;
        if ($forcedCatamaranIds !== null) {
            $forcedSet = array_map('intval', (array) $forcedCatamaranIds);
            $forcedSet = array_values(array_filter($forcedSet));
            if (empty($forcedSet)) {
                $forcedSet = null;
            }
        }

        // Catamarani bloccati per questo tour nella data della partenza
        $departureDate = is_string($departure->departure_date)
            ? $departure->departure_date
            : $departure->departure_date->format('Y-m-d');
        // Blocco GLOBALE per catamarano: una barca riservata è occupata per qualsiasi tour.
        [$winStart, $winEnd] = $this->departureTimeWindow($departure);
        $blockedIds = TourCatamaranBlock::blockedCatamaranIdsOn($departureDate, $winStart, $winEnd);

        $candidates = [];
        foreach ($catamarans as $cat) {
            // Catamarani forzati dall'admin: considera SOLO quelli.
            if ($forcedSet !== null && !in_array((int) $cat->id, $forcedSet, true)) {
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
            // In modalità overflow (uso esclusivo) i catamarani scelti sono "vuoti" per
            // questa prenotazione: usiamo la capienza piena come riferimento.
            if ($allowOverflow) {
                $free = (int) $cat->capacity;
            }
            if ($free <= 0) {
                continue;
            }
            $candidates[] = [
                'catamaran_id' => $cat->id,
                'free' => $free,
                'priority' => $cat->pivot->priority ?? $cat->sort_order ?? 0,
            ];
        }

        // Catamarani forzati ma nessuno idoneo: nessuna distribuzione possibile.
        if ($forcedSet !== null && empty($candidates)) {
            return null;
        }

        // Uso esclusivo con overflow: i passeggeri possono superare la capienza
        // totale dei catamarani scelti. Ripartiamo riempiendo in ordine e mettendo
        // l'eccedenza sull'ultimo catamarano (tutti vengono comunque bloccati).
        if ($allowOverflow && $forcedSet !== null && !empty($candidates)) {
            $sorted = collect($candidates)->sortByDesc('free')->values();
            $remaining = $seats;
            $assignment = [];
            $count = $sorted->count();
            foreach ($sorted as $idx => $c) {
                $isLast = ($idx === $count - 1);
                $take = $isLast ? $remaining : min($remaining, $c['free']);
                if ($take > 0) {
                    $assignment[] = ['catamaran_id' => $c['catamaran_id'], 'seats' => $take];
                    $remaining -= $take;
                }
            }
            return $remaining <= 0 ? $assignment : null;
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
    /**
     * Estrae i campi documento d'identità da un array guest, normalizzati per
     * il modello BookingSeat. Restituisce chiavi sempre presenti (null se assenti)
     * così da poter fare merge diretto nel create().
     *
     * @param  array<string,mixed>  $guest
     * @return array<string,mixed>
     */
    protected function documentColumns(array $guest): array
    {
        return [
            'doc_type' => $guest['doc_type'] ?? null,
            'doc_number' => isset($guest['doc_number']) ? strtoupper(trim((string) $guest['doc_number'])) : null,
            'doc_expiry' => $guest['doc_expiry'] ?? null,
            'doc_issue_country' => isset($guest['doc_issue_country']) ? strtoupper(trim((string) $guest['doc_issue_country'])) : null,
            'doc_issue_province' => $guest['doc_issue_province'] ?? null,
            'doc_issue_place' => $guest['doc_issue_place'] ?? null,
        ];
    }

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
            BookingSeat::create(array_merge([
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
            ], $this->documentColumns($guest)));
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
            BookingSeat::create(array_merge([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $catamaranQueue[$countingIdx] ?? null,
                'tour_age_bracket_id' => $bracket?->id,
                'price_paid' => $price,
                'guest_first_name' => $entry['guest']['first_name'] ?? ($entry['child']['first_name'] ?? null),
                'guest_last_name' => $entry['guest']['last_name'] ?? ($entry['child']['last_name'] ?? null),
                'guest_date_of_birth' => $entry['child']['dob'] ?? null,
                'is_primary' => false,
            ], $this->documentColumns($entry['guest'] ?? [])));
            $countingIdx++;
        }

        // Infanti in braccio: stesso catamarano del primo posto
        $defaultCatamaran = $catamaranQueue[0] ?? null;
        foreach ($nonCountingChildren as $entry) {
            $bracket = $entry['bracket'];
            BookingSeat::create(array_merge([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $defaultCatamaran,
                'tour_age_bracket_id' => $bracket->id,
                'price_paid' => (float) $bracket->price * (float) $booking->departure->price_modifier,
                'guest_first_name' => $entry['guest']['first_name'] ?? ($entry['child']['first_name'] ?? null),
                'guest_last_name' => $entry['guest']['last_name'] ?? ($entry['child']['last_name'] ?? null),
                'guest_date_of_birth' => $entry['child']['dob'],
                'is_primary' => false,
            ], $this->documentColumns($entry['guest'] ?? [])));
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
    /**
     * @param  int  $complimentarySeats  Quanti posti sono OMAGGIO: vengono
     *   salvati con price_paid = 0. Si azzerano i posti di maggior valore,
     *   coerentemente con PricingService::calculate().
     */
    protected function createSeats(
        Booking $booking,
        array $bracketCounts,
        array $distribution,
        array $guests = [],
        int $complimentarySeats = 0
    ): void {
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

        // Quali posti sono omaggio: i più costosi, come in PricingService.
        // Indici riferiti a countingList seguita da nonCountingList.
        $modifier = (float) $booking->departure->price_modifier;
        $freeIndexes = collect($countingList)
            ->map(fn ($e, $i) => ['key' => 'c'.$i, 'price' => (float) $e['bracket']->price * $modifier])
            ->concat(collect($nonCountingList)
                ->map(fn ($e, $i) => ['key' => 'n'.$i, 'price' => (float) $e['bracket']->price * $modifier]))
            ->sortByDesc('price')
            ->take(max(0, $complimentarySeats))
            ->pluck('key')
            ->all();

        // Posti contanti → assegnati a catamarano dalla queue
        foreach ($countingList as $idx => $entry) {
            $bracket = $entry['bracket'];
            $guest = $guests[$idx] ?? [];
            $isFree = in_array('c'.$idx, $freeIndexes, true);
            BookingSeat::create(array_merge([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $catamaranQueue[$idx] ?? null,
                'tour_age_bracket_id' => $bracket->id,
                'price_paid' => $isFree ? 0 : (float) $bracket->price * $modifier,
                'guest_first_name' => $guest['first_name'] ?? null,
                'guest_last_name' => $guest['last_name'] ?? null,
                'guest_date_of_birth' => $guest['date_of_birth'] ?? null,
                'tax_code' => $idx === 0 ? ($guest['tax_code'] ?? null) : null,
                'is_primary' => $idx === 0,
            ], $this->documentColumns($guest)));
        }

        // Posti non contanti (es. neonati) → seguono il catamarano del primo posto contante
        $defaultCatamaran = $catamaranQueue[0] ?? null;
        foreach ($nonCountingList as $nIdx => $entry) {
            $bracket = $entry['bracket'];
            $isFree = in_array('n'.$nIdx, $freeIndexes, true);
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $seatNumber++,
                'catamaran_id' => $defaultCatamaran,
                'tour_age_bracket_id' => $bracket->id,
                'price_paid' => $isFree ? 0 : (float) $bracket->price * $modifier,
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

            // Difesa server-side: non si può spostare un posto su un catamarano
            // riservato in USO ESCLUSIVO da un'ALTRA prenotazione (la UI già non lo
            // offre tra le destinazioni, qui blocchiamo anche le richieste dirette).
            $day = $departure->departure_date instanceof \DateTimeInterface
                ? $departure->departure_date->format('Y-m-d')
                : (string) $departure->departure_date;
            $exclusiveBlock = TourCatamaranBlock::whereDate('start_date', '<=', $day)
                ->whereDate('end_date', '>=', $day)
                ->where('catamaran_id', $catamaran->id)
                ->where('reason', 'not like', '%#' . $booking->booking_number . '%')
                ->where('reason', 'like', '%#%')
                ->exists();
            if ($exclusiveBlock) {
                throw new \Exception("Catamarano {$catamaran->name} riservato in uso esclusivo: non disponibile.");
            }

            $seat->catamaran_id = $catamaran->id;
            $seat->save();
            return $seat->fresh('catamaran');
        });
    }

    /**
     * Sposta un'INTERA prenotazione a uso esclusivo da un catamarano all'altro:
     * ricolloca tutti i suoi posti sul nuovo catamarano E sposta i relativi
     * TourCatamaranBlock (la riserva). Il vecchio catamarano si libera, il nuovo
     * risulta riservato. Il nuovo catamarano deve essere completamente libero.
     *
     * @throws \Exception se il nuovo catamarano non è idoneo (non libero, non
     *                    disponibile, capienza insufficiente).
     */
    public function moveExclusiveReservation(Booking $booking, int $newCatamaranId): Booking
    {
        return DB::transaction(function () use ($booking, $newCatamaranId) {
            $departure = $booking->departure;
            $target = Catamaran::findOrFail($newCatamaranId);

            // Catamarani attualmente riservati da QUESTA prenotazione (dai blocchi).
            $blocks = TourCatamaranBlock::where('reason', 'like', '%#' . $booking->booking_number . '%')->get();
            $currentCatamaranIds = $blocks->pluck('catamaran_id')->map(fn ($id) => (int) $id)->all();

            if (in_array($target->id, $currentCatamaranIds, true)) {
                return $booking; // già su questo catamarano
            }

            // Una riserva multi-catamarano non si sposta su un singolo scafo.
            if (count(array_unique($currentCatamaranIds)) > 1) {
                throw new \Exception('Questa riserva occupa più catamarani: spostala modificando la prenotazione.');
            }

            if (!$target->isAvailableOn($departure->departure_date)) {
                throw new \Exception("Catamarano {$target->name} non disponibile nella data.");
            }

            // Il catamarano di destinazione deve essere COMPLETAMENTE libero in
            // questa partenza: nessun posto di altre prenotazioni…
            $booked = $target->seatsBookedOnDeparture($departure->id);
            if ($booked > 0) {
                throw new \Exception("Catamarano {$target->name} non è libero per questa partenza.");
            }
            // …e nessuna riserva esclusiva di ALTRE prenotazioni sul giorno.
            $day = $departure->departure_date instanceof \DateTimeInterface
                ? $departure->departure_date->format('Y-m-d')
                : (string) $departure->departure_date;
            $otherReservation = TourCatamaranBlock::whereDate('start_date', '<=', $day)
                ->whereDate('end_date', '>=', $day)
                ->where('catamaran_id', $target->id)
                ->where('reason', 'not like', '%#' . $booking->booking_number . '%')
                ->where('reason', 'like', '%#%')
                ->exists();
            if ($otherReservation) {
                throw new \Exception("Catamarano {$target->name} già riservato da un'altra prenotazione.");
            }

            // Capienza sufficiente per i posti della prenotazione.
            $seats = $booking->seatRecords()->whereNull('cancelled_at')->get();
            if ($seats->count() > $target->capacity) {
                throw new \Exception("Catamarano {$target->name}: capienza insufficiente per {$seats->count()} posti.");
            }

            // Sposta i posti.
            foreach ($seats as $seat) {
                $seat->catamaran_id = $target->id;
                $seat->save();
            }

            // Sposta la riserva (i blocchi) sul nuovo catamarano.
            foreach ($blocks as $block) {
                $block->catamaran_id = $target->id;
                $block->save();
            }

            \App\Support\BookingLog::info('reservation_move', 'Riserva catamarano spostata', $booking, [
                'from_catamaran_ids' => $currentCatamaranIds,
                'to_catamaran_id' => $target->id,
                'to_catamaran' => $target->name,
                'seats' => $seats->count(),
            ]);

            return $booking->fresh(['seatRecords.catamaran', 'departure']);
        });
    }
}
