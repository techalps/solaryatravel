<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Genera "partenze virtuali" a partire dai periodi del tour
 * (giorni operativi + orari giornalieri).
 *
 * Una partenza virtuale è un array con:
 *  - tour_id
 *  - period_id
 *  - date (Y-m-d)
 *  - time (H:i)
 *  - departure_at (Carbon)
 *  - base_price (decimal)
 *  - seats_available (int) — basato sui catamarani non bloccati
 *  - capacity (int)
 */
class DepartureGeneratorService
{
    /**
     * Restituisce le partenze virtuali del tour entro l'intervallo dato.
     *
     * @return Collection<int, array>
     */
    public function generate(Tour $tour, Carbon $from, Carbon $to, bool $includePast = false): Collection
    {
        $tour->loadMissing(['periods', 'catamarans']);

        $blocks = TourCatamaranBlock::where('tour_id', $tour->id)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                  ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                  ->orWhere(function ($q2) use ($from, $to) {
                      $q2->where('start_date', '<=', $from->toDateString())
                         ->where('end_date', '>=', $to->toDateString());
                  });
            })
            ->get();

        // Catamarani disponibili per il tour
        $tourCatamarans = $tour->catamarans()->where('is_active', true)->get();
        if ($tourCatamarans->isEmpty()) {
            // fallback: tutti i catamarani attivi
            $tourCatamarans = \App\Models\Catamaran::where('is_active', true)->get();
        }
        $tourCatamaranIds = $tourCatamarans->pluck('id')->all();
        $totalCapacity = (int) $tourCatamarans->sum('capacity');

        $now = Carbon::now();
        $results = collect();

        foreach ($tour->periods as $period) {
            $pStart = Carbon::parse($period->start_date)->max($from);
            $pEnd = Carbon::parse($period->end_date)->min($to);
            if ($pStart->gt($pEnd)) {
                continue;
            }

            $weekdays = is_array($period->weekdays) && !empty($period->weekdays)
                ? array_map('intval', $period->weekdays)
                : [1, 2, 3, 4, 5, 6, 7];
            $times = is_array($period->times) && !empty($period->times)
                ? $period->times
                : ['10:00'];

            foreach (CarbonPeriod::create($pStart->copy()->startOfDay(), $pEnd->copy()->startOfDay()) as $date) {
                /** @var Carbon $date */
                if (!in_array($date->isoWeekday(), $weekdays, true)) {
                    continue;
                }

                // Catamarani bloccati in quella data
                $blockedIds = $blocks->filter(fn ($b) =>
                    $date->between(Carbon::parse($b->start_date), Carbon::parse($b->end_date))
                )->pluck('catamaran_id')->all();

                $availableCatamarans = array_values(array_diff($tourCatamaranIds, $blockedIds));
                if (empty($availableCatamarans)) {
                    continue; // tutti bloccati: niente partenze
                }
                $availableCapacity = (int) $tourCatamarans
                    ->whereIn('id', $availableCatamarans)
                    ->sum('capacity');

                foreach ($times as $time) {
                    $depAt = $date->copy()->setTimeFromTimeString($time);
                    if (!$includePast && $depAt->lt($now)) {
                        continue; // skip nel passato (salvo richiesta esplicita, es. admin retroattivo)
                    }
                    $results->push([
                        'tour_id' => $tour->id,
                        'period_id' => $period->id,
                        'date' => $date->toDateString(),
                        'time' => substr($time, 0, 5),
                        'departure_at' => $depAt,
                        'base_price' => (float) $period->base_price,
                        'capacity' => $availableCapacity,
                        'total_capacity' => $totalCapacity,
                    ]);
                }
            }
        }

        return $results->sortBy('departure_at')->values();
    }

    /**
     * Prossime N partenze a partire da oggi.
     */
    public function upcoming(Tour $tour, int $days = 60): Collection
    {
        return $this->generate($tour, Carbon::today(), Carbon::today()->addDays($days));
    }
}
