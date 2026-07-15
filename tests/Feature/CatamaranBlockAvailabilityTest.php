<?php

namespace Tests\Feature;

use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Services\BookingService;
use App\Services\DepartureGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Regressione OVERBOOKING: un catamarano riservato (TourCatamaranBlock) deve
 * risultare occupato per l'INTERA GIORNATA, anche se il blocco ha orari e la
 * partenza cade in una fascia diversa. Copre tutti i flussi (frontend/admin/
 * b2b/widget) perché passano tutti da blockedCatamaranIdsOn + generatore +
 * distributeSeats. Verifica anche la riserva su PIÙ DATE.
 */
class CatamaranBlockAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTour(int $catCount = 1, int $capacity = 10): array
    {
        $cats = [];
        for ($i = 0; $i < $catCount; $i++) {
            $cats[] = Catamaran::create(['name' => 'C'.uniqid(), 'slug' => 'c-'.uniqid(), 'capacity' => $capacity, 'is_active' => true]);
        }
        $tour = Tour::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'is_active' => true, 'booking_on_request' => false]);
        $tour->catamarans()->attach(array_map(fn ($c) => $c->id, $cats));
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['14:00'],
            'base_price' => 100,
        ]);

        return [$tour, $cats];
    }

    private function departure(Tour $tour, Catamaran $cat, string $date, string $start = '14:00:00', string $end = '17:00:00'): TourDeparture
    {
        return TourDeparture::create([
            'tour_id' => $tour->id, 'departure_date' => $date,
            'start_time' => $start, 'end_time' => $end,
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);
    }

    public function test_blocco_con_orari_disgiunti_occupa_tutta_la_giornata(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $date = now()->addDays(5)->toDateString();

        // Riserva a uso esclusivo con fascia MATTINA (09:00–12:30).
        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $cat->id,
            'start_date' => $date, 'end_date' => $date,
            'start_time' => '09:00', 'end_time' => '12:30',
            'reason' => 'Riservato da prenotazione admin #TEST',
        ]);

        // Partenza normale nel POMERIGGIO (14:00–17:00): fascia disgiunta.
        $dep = $this->departure($tour, $cat, $date, '14:00:00', '17:00:00');

        $svc = app(BookingService::class);

        // Prima della fix qui c'erano 10 posti (bug). Ora: 0.
        $this->assertSame(0, $svc->remainingCapacity($dep), 'Il catamarano riservato deve essere occupato tutto il giorno.');
        $this->assertSame(0, $svc->largestSingleCatamaranFree($dep));
        $this->assertNull($svc->distributeSeats($tour, $dep, 2), 'Non deve poter distribuire posti su un catamarano riservato.');

        // blockedCatamaranIdsOn deve includere il catamarano anche con finestra diversa.
        $blocked = TourCatamaranBlock::blockedCatamaranIdsOn($date, '14:00', '17:00');
        $this->assertContains($cat->id, $blocked);
    }

    public function test_creazione_prenotazione_bloccata_su_catamarano_riservato(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $date = now()->addDays(6)->toDateString();

        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $cat->id,
            'start_date' => $date, 'end_date' => $date,
            'start_time' => '09:00', 'end_time' => '12:30',
            'reason' => 'Riservato da prenotazione admin #TEST2',
        ]);
        $dep = $this->departure($tour, $cat, $date);

        // La creazione (flusso condiviso da frontend/b2b/widget) deve fallire:
        // nessun catamarano disponibile → distributeSeats null → eccezione.
        $this->expectException(\Exception::class);
        app(BookingService::class)->create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'adults_count' => 2,
            'children' => [],
            'customer_first_name' => 'A', 'customer_last_name' => 'B',
            'customer_email' => 'a'.uniqid().'@x.it',
            'guests' => [
                ['first_name' => 'A', 'last_name' => 'B'],
                ['first_name' => 'C', 'last_name' => 'D'],
            ],
            'payment_type' => 'full',
        ], 'website');
    }

    public function test_riserva_su_piu_date_blocca_ogni_giorno(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $start = now()->addDays(7);
        $end = now()->addDays(9);

        // Riserva esclusiva su 3 giorni consecutivi.
        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $cat->id,
            'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
            'start_time' => '09:00', 'end_time' => '18:00',
            'reason' => 'Riservato da prenotazione admin #MULTI',
        ]);

        // Ogni giorno del periodo deve risultare bloccato (anche fascia diversa).
        foreach ([$start, $start->copy()->addDay(), $end] as $d) {
            $blocked = TourCatamaranBlock::blockedCatamaranIdsOn($d->toDateString(), '14:00', '17:00');
            $this->assertContains($cat->id, $blocked, 'Il catamarano deve essere bloccato il '.$d->toDateString());

            $dep = $this->departure($tour, $cat, $d->toDateString());
            $this->assertSame(0, app(BookingService::class)->remainingCapacity($dep));
        }

        // Un giorno FUORI dal periodo non è bloccato.
        $free = TourCatamaranBlock::blockedCatamaranIdsOn($end->copy()->addDay()->toDateString(), '14:00', '17:00');
        $this->assertNotContains($cat->id, $free);
    }

    public function test_generatore_esclude_data_se_unico_catamarano_riservato(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $date = now()->addDays(8);

        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $cat->id,
            'start_date' => $date->toDateString(), 'end_date' => $date->toDateString(),
            'start_time' => '09:00', 'end_time' => '12:30',
            'reason' => 'Riservato da prenotazione admin #GEN',
        ]);

        // Il calendario (usato da frontend/b2b/widget) NON deve offrire quella data:
        // l'unico catamarano è riservato tutto il giorno.
        $departures = app(DepartureGeneratorService::class)
            ->generate($tour, $date->copy()->startOfDay(), $date->copy()->endOfDay(), false);

        $this->assertTrue(
            $departures->every(fn ($d) => \Carbon\Carbon::parse($d['departure_date'] ?? $d->departure_date)->toDateString() !== $date->toDateString()),
            'La data con unico catamarano riservato non deve comparire nel calendario.'
        );
    }

    public function test_secondo_catamarano_libero_resta_disponibile(): void
    {
        // Con 2 catamarani, se ne riservo 1 l'altro resta prenotabile (no falso positivo).
        [$tour, $cats] = $this->makeTour(2, 10);
        $date = now()->addDays(10)->toDateString();

        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $cats[0]->id,
            'start_date' => $date, 'end_date' => $date,
            'start_time' => '09:00', 'end_time' => '12:30',
            'reason' => 'Riservato da prenotazione admin #ONE',
        ]);
        $dep = $this->departure($tour, $cats[0], $date);

        // Un catamarano riservato, l'altro (capienza 10) libero.
        $this->assertSame(10, app(BookingService::class)->remainingCapacity($dep));
        $blocked = TourCatamaranBlock::blockedCatamaranIdsOn($date);
        $this->assertContains($cats[0]->id, $blocked);
        $this->assertNotContains($cats[1]->id, $blocked);
    }
}
