<?php

namespace Tests\Feature;

use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourPeriod;
use App\Services\DepartureGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Anticipo minimo di prenotazione (booking_cutoff_hours): le partenze entro
 * il cutoff non devono essere generate (vale su sito e portale agenzie).
 */
class BookingCutoffTest extends TestCase
{
    use DatabaseTransactions;

    private function setCutoff(int $hours): void
    {
        $path = storage_path('app/settings.json');
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $data['booking_cutoff_hours'] = $hours;
        file_put_contents($path, json_encode($data));
        Cache::forget('app_settings');
    }

    protected function tearDown(): void
    {
        $this->setCutoff(0);
        parent::tearDown();
    }

    private function tourWithDailyDepartures(): Tour
    {
        $cat = Catamaran::create(['name' => 'Cat', 'slug' => 'cat-'.uniqid(), 'capacity' => 10, 'is_active' => true]);
        $tour = Tour::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'is_active' => true, 'booking_on_request' => false]);
        $tour->catamarans()->attach($cat->id);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 100,
        ]);
        return $tour;
    }

    public function test_cutoff_nasconde_le_partenze_troppo_vicine(): void
    {
        $tour = $this->tourWithDailyDepartures();
        $svc = app(DepartureGeneratorService::class);

        $this->setCutoff(0);
        $senza = $svc->upcoming($tour, 15);

        $this->setCutoff(72);
        $con = $svc->upcoming($tour, 15);

        // Con cutoff 72h ci sono meno partenze, e nessuna entro le prossime 72h.
        $this->assertLessThan($senza->count(), $con->count());
        $soglia = now()->addHours(72);
        $this->assertTrue($con->every(fn ($d) => $d['departure_at']->gte($soglia)));
    }

    public function test_admin_retroattivo_ignora_il_cutoff(): void
    {
        $tour = $this->tourWithDailyDepartures();
        $svc = app(DepartureGeneratorService::class);
        $this->setCutoff(72);

        // includePast=true (flusso admin) → il cutoff non si applica.
        $all = $svc->generate($tour, now(), now()->addDays(15), true);
        $soglia = now()->addHours(72);
        $this->assertTrue($all->contains(fn ($d) => $d['departure_at']->lt($soglia)));
    }
}
