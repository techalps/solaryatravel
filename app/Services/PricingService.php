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
    public function calculate(
        Tour $tour,
        TourDeparture $departure,
        array $bracketCounts,
        array $addonIds = [],
        ?string $discountCode = null
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
        ];
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

        $discountAmount = $discount->type === 'percentage'
            ? $amount * ((float) $discount->value / 100)
            : (float) $discount->value;

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
    public function calculateForParticipants(
        Tour $tour,
        TourDeparture $departure,
        int $adultsCount,
        array $children,
        array $addonIds = [],
        ?string $discountCode = null
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
