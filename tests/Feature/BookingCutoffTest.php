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
 * Orario limite di prenotazione: si prenota fino a un orario del GIORNO PRIMA
 * della partenza (globale, con override per-tour). L'admin (includePast) lo ignora.
 */
class BookingCutoffTest extends TestCase
{
    use DatabaseTransactions;

    private function setGlobalCutoff(string $time): void
    {
        $path = storage_path('app/settings.json');
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $data['booking_cutoff_time'] = $time;
        file_put_contents($path, json_encode($data));
        Cache::forget('app_settings');
    }

    protected function tearDown(): void
    {
        $this->setGlobalCutoff('22:00');
        parent::tearDown();
    }

    private function tourWithDailyDepartures(?string $cutoff = null): Tour
    {
        $cat = Catamaran::create(['name' => 'Cat', 'slug' => 'cat-'.uniqid(), 'capacity' => 10, 'is_active' => true]);
        $tour = Tour::create([
            'name' => 'T', 'slug' => 't-'.uniqid(), 'is_active' => true,
            'booking_on_request' => false,
            'booking_cutoff_time' => $cutoff ? $cutoff.':00' : null,
        ]);
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

    public function test_deadline_e_lorario_del_giorno_prima(): void
    {
        $tour = $this->tourWithDailyDepartures('22:00');
        $depAt = now()->addDays(5)->setTime(10, 0);
        $deadline = $tour->bookingDeadlineFor($depAt);

        // Partenza il giorno X ore 10 → deadline il giorno X-1 alle 22:00.
        $this->assertEquals($depAt->copy()->subDay()->format('Y-m-d').' 22:00', $deadline->format('Y-m-d H:i'));
    }

    public function test_override_per_tour_ha_priorita_sul_globale(): void
    {
        $this->setGlobalCutoff('22:00');
        $tour = $this->tourWithDailyDepartures('12:00'); // override
        $this->assertSame('12:00', $tour->effectiveCutoffTime());

        $tourGlobal = $this->tourWithDailyDepartures(null); // usa globale
        $this->assertSame('22:00', $tourGlobal->effectiveCutoffTime());
    }

    public function test_admin_ignora_il_cutoff(): void
    {
        $tour = $this->tourWithDailyDepartures('22:00');
        $svc = app(DepartureGeneratorService::class);

        // includePast=true (admin) → include anche partenze oltre la deadline.
        $all = $svc->generate($tour, now(), now()->addDays(3), true);
        $public = $svc->generate($tour, now(), now()->addDays(3), false);

        $this->assertGreaterThanOrEqual($public->count(), $all->count());
    }
}
