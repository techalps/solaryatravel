<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourAgeBracket;
use App\Models\TourPeriod;
use App\Models\Addon;
use App\Models\DiscountCode;
use Illuminate\Support\Collection;

class PricingService
{
    /**
     * Calcola il prezzo totale di una prenotazione su un tour.
     *
     * @param  array<int,int>  $bracketCounts  mappa bracket_id => quantità
     * @param  array<int,int>  $addonIds       id degli addon selezionati
     */
    /**
     * @param  int  $complimentarySeats  Posti OMAGGIO concessi dall'admin: occupano
     *   il posto in barca (capienza, biglietto, QR) ma valgono 0€. Vengono
     *   scontati partendo dalle fasce più costose, così l'omaggio vale sul
     *   posto di maggior valore.
     * @param  bool  $complimentaryIncludesAddons  Se true anche la quota extra
     *   dei posti omaggio è gratuita; se false gli extra restano addebitati
     *   (il fornitore va pagato comunque).
     */
    /**
     * @param  array{type:'percent'|'amount', value:float}|null  $manualDiscount
     *         Sconto commerciale deciso dall'admin in fase di creazione, in
     *         percentuale o in euro. Si somma all'eventuale codice sconto.
     */
    public function calculate(
        Tour $tour,
        TourDeparture $departure,
        array $bracketCounts,
        array $addonIds = [],
        ?string $discountCode = null,
        int $complimentarySeats = 0,
        bool $complimentaryIncludesAddons = false,
        ?array $manualDiscount = null
    ): array {
        $brackets = $this->resolveBrackets($tour, $departure->departure_date)
            ->whereIn('id', array_keys($bracketCounts))
            ->keyBy('id');

        // Calcolo posti totali e dettaglio fasce
        $bracketDetails = [];
        $basePrice = 0.0;
        $totalSeats = 0;
        $countingSeats = 0;

        foreach ($bracketCounts as $bracketId => $count) {
            $count = (int) $count;
            if ($count <= 0) {
                continue;
            }
            $bracket = $brackets->get($bracketId);
            if (!$bracket) {
                continue;
            }
            $unit = (float) $bracket->price * (float) $departure->price_modifier;
            $line = $unit * $count;
            $basePrice += $line;
            $totalSeats += $count;
            if ($bracket->counts_as_seat) {
                $countingSeats += $count;
            }
            $bracketDetails[] = [
                'bracket_id' => $bracket->id,
                'label' => $bracket->label,
                'unit_price' => round($unit, 2),
                'count' => $count,
                'line_total' => round($line, 2),
                'counts_as_seat' => $bracket->counts_as_seat,
            ];
        }

        // Posti omaggio: i posti restano occupati, si azzera il loro valore
        // partendo dalle fasce più costose (vedi applyComplimentarySeats).
        [$basePrice, $bracketDetails, $complimentarySeats, $complimentaryAmount]
            = $this->applyComplimentarySeats($basePrice, $bracketDetails, $totalSeats, $complimentarySeats);

        // Extra: se l'omaggio li include, si pagano solo per i posti non omaggio.
        $payingSeatsForAddons = $complimentaryIncludesAddons
            ? max(0, $countingSeats - $complimentarySeats)
            : $countingSeats;

        $addonsTotal = $this->calculateAddonsTotal($addonIds, $payingSeatsForAddons, $tour->duration_hours ?? 0);

        $discountAmount = 0.0;
        $discountCodeId = null;
        if ($discountCode) {
            $applied = $this->validateAndApplyDiscount($discountCode, $basePrice + $addonsTotal);
            if ($applied) {
                $discountAmount = $applied['amount'];
                $discountCodeId = $applied['id'];
            }
        }

        // Sconto manuale dell'admin: si applica su quanto resta dopo l'eventuale
        // codice sconto, così i due non insistono sulla stessa base.
        $discountAmount += $this->resolveManualDiscount(
            $manualDiscount ?? null,
            max(0, $basePrice + $addonsTotal - $discountAmount)
        );

        $subtotal = max(0, $basePrice + $addonsTotal - $discountAmount);
        $taxRate = (float) config('booking.tax_rate', 0) / 100;
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        return [
            'base_price' => round($basePrice, 2),
            'addons_total' => round($addonsTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code_id' => $discountCodeId,
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate * 100,
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'total_seats' => $totalSeats,
            'counting_seats' => $countingSeats,
            'brackets' => $bracketDetails,
            'complimentary_seats' => $complimentarySeats,
            'complimentary_amount' => $complimentaryAmount,
            'complimentary_includes_addons' => $complimentaryIncludesAddons,
        ];
    }

    /**
     * Sconto manuale in percentuale o in euro, tradotto in importo.
     *
     * Non può mai superare la base su cui si applica: uno sconto del 150% o di
     * 500 € su un totale di 300 € produrrebbe un totale negativo, cioè un
     * credito verso il cliente creato per errore di battitura.
     *
     * @param  array{type?:string, value?:mixed}|null  $discount
     */
    protected function resolveManualDiscount(?array $discount, float $base): float
    {
        if (! $discount || $base <= 0) {
            return 0.0;
        }

        $value = (float) ($discount['value'] ?? 0);
        if ($value <= 0) {
            return 0.0;
        }

        $amount = ($discount['type'] ?? 'amount') === 'percent'
            ? $base * min($value, 100) / 100
            : $value;

        return round(min($amount, $base), 2);
    }

    protected function calculateAddonsTotal(array $addonIds, int $seats, float $hours): float
    {
        $total = 0.0;
        foreach ($addonIds as $addonId) {
            $addon = Addon::find($addonId);
            if ($addon && $addon->is_active) {
                // quantity=1 (1 selezione per extra), persons=$seats per il calcolo per_person
                $total += (float) $addon->calculatePrice(1, $seats);
            }
        }
        return $total;
    }

    protected function validateAndApplyDiscount(string $code, float $amount): ?array
    {
        $discount = DiscountCode::where('code', strtoupper($code))
            ->where('is_active', true)
            ->first();

        if (!$discount || !$discount->isValid()) {
            return null;
        }

        $discountAmount = $discount->calculateDiscount($amount);

        return [
            'id' => $discount->id,
            'amount' => min($discountAmount, $amount),
        ];
    }

    /**
     * Calcolo prezzi col nuovo modello "adulti + bambini con DOB".
     *
     * - L'adulto paga il `base_price` del periodo che copre la data di partenza.
     * - Ogni bambino è associato a una "riduzione" (bracket) in base all'età
     *   calcolata alla data di partenza; il prezzo del bracket è quello applicato.
     *
     * @param  array<int, array{dob: string, bracket_id?: int|null}>  $children
     *         Ogni elemento è un bambino con DOB ('Y-m-d') ed eventuale bracket
     *         già risolto. Se `bracket_id` è null/assente viene risolto qui.
     * @param  array<int>  $addonIds
     */
    /**
     * @param  int  $complimentarySeats  Posti OMAGGIO (vedi calculate()).
     * @param  bool  $complimentaryIncludesAddons  Omaggio anche sugli extra.
     */
    /**
     * @param  array{type:'percent'|'amount', value:float}|null  $manualDiscount
     *         Sconto commerciale deciso dall'admin (percentuale o euro).
     */
    public function calculateForParticipants(
        Tour $tour,
        TourDeparture $departure,
        int $adultsCount,
        array $children,
        array $addonIds = [],
        ?string $discountCode = null,
        int $complimentarySeats = 0,
        bool $complimentaryIncludesAddons = false,
        ?array $manualDiscount = null
    ): array {
        $adultsCount = max(0, $adultsCount);
        $period = $this->resolvePeriod($tour, $departure->departure_date);
        $adultUnit = $period
            ? (float) $period->base_price * (float) $departure->price_modifier
            : 0.0;

        $bracketDetails = [];
        $basePrice = 0.0;
        $totalSeats = 0;
        $countingSeats = 0;

        // Linea adulti (se presenti)
        if ($adultsCount > 0) {
            $adultsLine = $adultUnit * $adultsCount;
            $basePrice += $adultsLine;
            $totalSeats += $adultsCount;
            $countingSeats += $adultsCount; // gli adulti contano sempre come posto
            $bracketDetails[] = [
                'bracket_id' => null,
                'label' => 'Adulto',
                'unit_price' => round($adultUnit, 2),
                'count' => $adultsCount,
                'line_total' => round($adultsLine, 2),
                'counts_as_seat' => true,
            ];
        }

        // Linee bambini — risolvi bracket per ognuno e raggruppa per riduzione
        $brackets = $this->resolveBrackets($tour, $departure->departure_date)->keyBy('id');
        $childrenByBracket = []; // bracket_id => count
        $unresolved = 0;
        foreach ($children as $child) {
            $dob = $child['dob'] ?? null;
            if (!$dob) {
                $unresolved++;
                continue;
            }
            $bracketId = $child['bracket_id'] ?? null;
            if ($bracketId && $brackets->has($bracketId)) {
                $bracket = $brackets->get($bracketId);
            } else {
                $bracket = $this->resolveBracketForDob($brackets, $dob, $departure->departure_date);
            }
            if (!$bracket) {
                $unresolved++;
                continue;
            }
            $childrenByBracket[$bracket->id] = ($childrenByBracket[$bracket->id] ?? 0) + 1;
        }

        foreach ($childrenByBracket as $bracketId => $count) {
            $bracket = $brackets->get($bracketId);
            $unit = (float) $bracket->price * (float) $departure->price_modifier;
            $line = $unit * $count;
            $basePrice += $line;
            $totalSeats += $count;
            if ($bracket->counts_as_seat) {
                $countingSeats += $count;
            }
            $bracketDetails[] = [
                'bracket_id' => $bracket->id,
                'label' => $bracket->label,
                'unit_price' => round($unit, 2),
                'count' => $count,
                'line_total' => round($line, 2),
                'counts_as_seat' => (bool) $bracket->counts_as_seat,
            ];
        }

        // Posti omaggio: stessa logica di calculate() — si azzerano i posti di
        // maggior valore, così l'omaggio vale sul biglietto più caro.
        [$basePrice, $bracketDetails, $complimentarySeats, $complimentaryAmount]
            = $this->applyComplimentarySeats($basePrice, $bracketDetails, $totalSeats, $complimentarySeats);

        $payingSeatsForAddons = $complimentaryIncludesAddons
            ? max(0, $countingSeats - $complimentarySeats)
            : $countingSeats;

        $addonsTotal = $this->calculateAddonsTotal($addonIds, $payingSeatsForAddons, $tour->duration_hours ?? 0);

        $discountAmount = 0.0;
        $discountCodeId = null;
        if ($discountCode) {
            $applied = $this->validateAndApplyDiscount($discountCode, $basePrice + $addonsTotal);
            if ($applied) {
                $discountAmount = $applied['amount'];
                $discountCodeId = $applied['id'];
            }
        }

        // Sconto manuale dell'admin: si applica su quanto resta dopo l'eventuale
        // codice sconto, così i due non insistono sulla stessa base.
        $discountAmount += $this->resolveManualDiscount(
            $manualDiscount ?? null,
            max(0, $basePrice + $addonsTotal - $discountAmount)
        );

        $subtotal = max(0, $basePrice + $addonsTotal - $discountAmount);
        $taxRate = (float) config('booking.tax_rate', 0) / 100;
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        return [
            'base_price' => round($basePrice, 2),
            'addons_total' => round($addonsTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code_id' => $discountCodeId,
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate * 100,
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'total_seats' => $totalSeats,
            'counting_seats' => $countingSeats,
            'brackets' => $bracketDetails,
            'adults_count' => $adultsCount,
            'adult_unit_price' => round($adultUnit, 2),
            'unresolved_children' => $unresolved,
            'complimentary_seats' => $complimentarySeats,
            'complimentary_amount' => $complimentaryAmount,
            'complimentary_includes_addons' => $complimentaryIncludesAddons,
        ];
    }

    /**
     * Azzera il valore di N posti, partendo dalle fasce più costose.
     *
     * Estratto perché lo usano sia calculate() (fasce esplicite) sia
     * calculateForParticipants() (adulti + bambini): l'omaggio deve comportarsi
     * identico nei due percorsi.
     *
     * @param  array<int, array<string, mixed>>  $bracketDetails
     * @return array{0: float, 1: array<int, array<string, mixed>>, 2: int, 3: float}
     */
    protected function applyComplimentarySeats(
        float $basePrice,
        array $bracketDetails,
        int $totalSeats,
        int $complimentarySeats
    ): array {
        $complimentarySeats = max(0, min($complimentarySeats, $totalSeats));

        if ($complimentarySeats === 0) {
            return [$basePrice, $bracketDetails, 0, 0.0];
        }

        $remaining = $complimentarySeats;
        $amount = 0.0;

        $order = collect($bracketDetails)->sortByDesc('unit_price')->keys()->all();

        foreach ($order as $i) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (int) $bracketDetails[$i]['count']);
            $amount += $take * (float) $bracketDetails[$i]['unit_price'];
            $bracketDetails[$i]['complimentary_count'] = $take;
            $remaining -= $take;
        }

        $amount = round($amount, 2);

        return [max(0, round($basePrice - $amount, 2)), $bracketDetails, $complimentarySeats, $amount];
    }

    /**
     * Pricing con prezzo TOTALE inserito a mano dall'admin (tour "su richiesta",
     * es. catamarano riservato: prezzo unico per l'intera prenotazione).
     * Adulti e bambini servono solo a contare i posti; ognuno occupa un posto.
     * Il totale viene attribuito al primo posto (intestatario), gli altri a 0.
     *
     * @param  float  $totalPrice  prezzo totale della prenotazione
     * @param  int  $childrenCount  numero di bambini (ognuno = un posto)
     * @param  array<int, int>  $addonIds
     */
    /**
     * @param  array{type:'percent'|'amount', value:float}|null  $manualDiscount
     *         Sconto commerciale deciso dall'admin (percentuale o euro).
     */
    public function calculateManual(
        Tour $tour,
        int $adultsCount,
        int $childrenCount,
        float $totalPrice,
        array $addonIds = [],
        ?string $discountCode = null,
        ?array $manualDiscount = null
    ): array {
        $adultsCount = max(0, $adultsCount);
        $childrenCount = max(0, $childrenCount);
        $totalPrice = max(0.0, $totalPrice);

        // Ogni partecipante (adulto o bambino) occupa un posto.
        $countingSeats = $adultsCount + $childrenCount;
        $totalSeats = $countingSeats;

        // Il prezzo è il totale "secco" della prenotazione.
        $basePrice = $totalPrice;

        $addonsTotal = $this->calculateAddonsTotal($addonIds, $countingSeats, $tour->duration_hours ?? 0);

        $discountAmount = 0.0;
        $discountCodeId = null;
        if ($discountCode) {
            $applied = $this->validateAndApplyDiscount($discountCode, $basePrice + $addonsTotal);
            if ($applied) {
                $discountAmount = $applied['amount'];
                $discountCodeId = $applied['id'];
            }
        }

        // Sconto manuale dell'admin: si applica su quanto resta dopo l'eventuale
        // codice sconto, così i due non insistono sulla stessa base.
        $discountAmount += $this->resolveManualDiscount(
            $manualDiscount ?? null,
            max(0, $basePrice + $addonsTotal - $discountAmount)
        );

        $subtotal = max(0, $basePrice + $addonsTotal - $discountAmount);
        $taxRate = (float) config('booking.tax_rate', 0) / 100;
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        $bracketDetails = [];
        if ($adultsCount > 0) {
            $bracketDetails[] = [
                'bracket_id' => null,
                'label' => 'Adulto',
                'unit_price' => 0,
                'count' => $adultsCount,
                'line_total' => round($basePrice, 2), // il totale è attribuito qui
                'counts_as_seat' => true,
            ];
        }
        if ($childrenCount > 0) {
            $bracketDetails[] = [
                'bracket_id' => null,
                'label' => 'Bambini',
                'unit_price' => 0,
                'count' => $childrenCount,
                'line_total' => 0,
                'counts_as_seat' => true,
            ];
        }

        return [
            'base_price' => round($basePrice, 2),
            'addons_total' => round($addonsTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code_id' => $discountCodeId,
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate * 100,
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'total_seats' => $totalSeats,
            'counting_seats' => $countingSeats,
            'brackets' => $bracketDetails,
            'adults_count' => $adultsCount,
            // Tutto il prezzo sul primo posto (intestatario); gli altri a 0.
            'adult_unit_price' => $adultsCount > 0 ? round($basePrice, 2) : 0,
            'manual_total_on_first_seat' => round($basePrice, 2),
            'unresolved_children' => 0,
        ];
    }

    /**
     * Trova la fascia bambini che copre l'età alla data di partenza.
     */
    public function resolveBracketForDob(
        \Illuminate\Support\Collection $brackets,
        string|\DateTimeInterface $dob,
        string|\DateTimeInterface $departureDate
    ): ?TourAgeBracket {
        $dobCarbon = $dob instanceof \DateTimeInterface ? \Carbon\Carbon::instance($dob) : \Carbon\Carbon::parse($dob);
        $depCarbon = $departureDate instanceof \DateTimeInterface ? \Carbon\Carbon::instance($departureDate) : \Carbon\Carbon::parse($departureDate);
        $age = (int) floor($dobCarbon->diffInYears($depCarbon));

        return $brackets->first(function (TourAgeBracket $b) use ($age) {
            $min = (int) ($b->min_age ?? 0);
            $max = $b->max_age !== null ? (int) $b->max_age : PHP_INT_MAX;
            return $age >= $min && $age <= $max;
        });
    }

    /**
     * Periodo applicabile a una data (start_date <= data <= end_date).
     */
    public function resolvePeriod(Tour $tour, string|\DateTimeInterface|null $date): ?TourPeriod
    {
        if (!$date) {
            return null;
        }
        $d = is_string($date) ? $date : $date->format('Y-m-d');
        return TourPeriod::where('tour_id', $tour->id)
            ->whereDate('start_date', '<=', $d)
            ->whereDate('end_date', '>=', $d)
            ->orderBy('sort_order')
            ->orderBy('start_date')
            ->first();
    }

    /**
     * Brackets applicabili a una data: quelle del periodo che la copre;
     * fallback alle brackets "orfane" del tour (vecchio sistema senza periodi).
     */
    public function resolveBrackets(Tour $tour, string|\DateTimeInterface|null $date): Collection
    {
        $period = $this->resolvePeriod($tour, $date);
        if ($period) {
            return $period->ageBrackets()->orderBy('sort_order')->get();
        }
        return TourAgeBracket::where('tour_id', $tour->id)
            ->whereNull('tour_period_id')
            ->orderBy('sort_order')
            ->get();
    }
}
