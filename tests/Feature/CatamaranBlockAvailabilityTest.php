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
 * Disponibilità catamarani vs riserve a uso esclusivo (TourCatamaranBlock).
 *
 * Semantica (decisa dall'utente): il blocco è un INTERVALLO CONTINUO tra due
 * istanti (start_date+start_time → end_date+end_time).
 *  - Giorno singolo con orari (09:00–12:30): libero FUORI dalla fascia.
 *  - Multi-giorno (20/07 09:00 → 21/07 18:00): occupato in continuo; il 21/07
 *    dalle 18:00 in poi è di nuovo libero, prima no; giorni interi in mezzo.
 *
 * Copre tutti i flussi (frontend/admin/b2b/widget) che passano da
 * blockedCatamaranIdsOn + generatore + distributeSeats.
 */
class CatamaranBlockAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTour(int $catCount = 1, int $capacity = 10, string $time = '14:00'): array
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
            'end_date' => now()->addDays(40)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => [$time],
            'base_price' => 100,
        ]);

        return [$tour, $cats];
    }

    private function departure(Tour $tour, string $date, string $start = '14:00:00', string $end = '17:00:00'): TourDeparture
    {
        return TourDeparture::create([
            'tour_id' => $tour->id, 'departure_date' => $date,
            'start_time' => $start, 'end_time' => $end,
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);
    }

    private function block(Tour $tour, Catamaran $cat, string $startDate, string $endDate, ?string $startTime, ?string $endTime): TourCatamaranBlock
    {
        return TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $cat->id,
            'start_date' => $startDate, 'end_date' => $endDate,
            'start_time' => $startTime, 'end_time' => $endTime,
            'reason' => 'Riservato da prenotazione admin #TEST',
        ]);
    }

    public function test_giorno_singolo_libero_fuori_dalla_fascia(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $date = now()->addDays(5)->toDateString();

        // Riserva MATTINA 09:00–12:30.
        $this->block($tour, $cat, $date, $date, '09:00', '12:30');

        // Partenza POMERIGGIO 14:00–17:00 → fuori dalla fascia: catamarano LIBERO.
        $depPomeriggio = $this->departure($tour, $date, '14:00:00', '17:00:00');
        $this->assertSame(10, app(BookingService::class)->remainingCapacity($depPomeriggio));
        $this->assertNotContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($date, '14:00', '17:00'));

        // Partenza MATTINA 10:00–12:00 → sovrapposta: catamarano OCCUPATO.
        $depMattina = $this->departure($tour, $date, '10:00:00', '12:00:00');
        $this->assertSame(0, app(BookingService::class)->remainingCapacity($depMattina));
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($date, '10:00', '12:00'));
    }

    public function test_riserva_parzialmente_sovrapposta_blocca(): void
    {
        // Scenario reale: tour prenotabile 09:00–12:00, catamarano riservato
        // 10:00–14:00. Le fasce si sovrappongono (10–12) → NON prenotabile.
        [$tour, $cats] = $this->makeTour(1, 10, '09:00');
        $cat = $cats[0];
        $date = now()->addDays(5)->toDateString();

        $this->block($tour, $cat, $date, $date, '10:00', '14:00');
        $dep = $this->departure($tour, $date, '09:00:00', '12:00:00');

        $this->assertSame(0, app(BookingService::class)->remainingCapacity($dep));
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($date, '09:00', '12:00'));
    }

    public function test_riserva_adiacente_non_blocca(): void
    {
        // Riserva 12:00–14:00, tour 09:00–12:00: si toccano ma non si sovrappongono.
        [$tour, $cats] = $this->makeTour(1, 10, '09:00');
        $cat = $cats[0];
        $date = now()->addDays(5)->toDateString();

        $this->block($tour, $cat, $date, $date, '12:00', '14:00');
        $this->assertNotContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($date, '09:00', '12:00'));
    }

    public function test_multi_giorno_intervallo_continuo(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $g20 = now()->addDays(6);
        $g21 = now()->addDays(7);

        // Riserva 20/07 09:00 → 21/07 18:00 (crociera continua).
        $this->block($tour, $cat, $g20->toDateString(), $g21->toDateString(), '09:00', '18:00');

        // 20/07 pomeriggio → occupato (primo giorno, dalle 09:00 in poi).
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($g20->toDateString(), '14:00', '17:00'));
        // 20/07 mattina prestissimo (07:00–08:00) → prima delle 09:00: libero.
        $this->assertNotContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($g20->toDateString(), '07:00', '08:00'));

        // 21/07 mattina/pomeriggio fino alle 18 → occupato.
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($g21->toDateString(), '10:00', '13:00'));
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($g21->toDateString(), '16:00', '18:00'));
        // 21/07 dalle 18:00 in poi → LIBERO (fine riserva).
        $this->assertNotContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($g21->toDateString(), '19:00', '21:00'));
        $depSera = $this->departure($tour, $g21->toDateString(), '19:00:00', '21:00:00');
        $this->assertSame(10, app(BookingService::class)->remainingCapacity($depSera));
    }

    public function test_multi_giorno_intermedio_occupato_tutto_il_giorno(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $start = now()->addDays(8);
        $mid = now()->addDays(9);
        $end = now()->addDays(10);

        $this->block($tour, $cat, $start->toDateString(), $end->toDateString(), '09:00', '18:00');

        // Giorno INTERMEDIO: qualunque ora è occupata (crociera in corso).
        foreach (['07:00' => '08:00', '13:00' => '15:00', '20:00' => '22:00'] as $s => $e) {
            $this->assertContains(
                $cat->id,
                TourCatamaranBlock::blockedCatamaranIdsOn($mid->toDateString(), $s, $e),
                "Giorno intermedio deve essere occupato ($s-$e)"
            );
        }
    }

    public function test_creazione_prenotazione_bloccata_in_fascia_riservata(): void
    {
        [$tour, $cats] = $this->makeTour(1, 10, '10:00');
        $cat = $cats[0];
        $date = now()->addDays(11)->toDateString();

        // Riserva 09:00–13:00; partenza 10:00–12:00 (dentro la fascia) → deve fallire.
        $this->block($tour, $cat, $date, $date, '09:00', '13:00');
        $dep = $this->departure($tour, $date, '10:00:00', '12:00:00');

        $this->expectException(\Exception::class);
        app(BookingService::class)->create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'adults_count' => 2, 'children' => [],
            'customer_first_name' => 'A', 'customer_last_name' => 'B',
            'customer_email' => 'a'.uniqid().'@x.it',
            'guests' => [['first_name' => 'A', 'last_name' => 'B'], ['first_name' => 'C', 'last_name' => 'D']],
            'payment_type' => 'full',
        ], 'website');
    }

    public function test_blocco_intera_giornata_senza_orari(): void
    {
        // Blocco senza orari (es. manutenzione o riserva whole-day) → tutto il giorno.
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $date = now()->addDays(12)->toDateString();
        $this->block($tour, $cat, $date, $date, null, null);

        $dep = $this->departure($tour, $date, '14:00:00', '17:00:00');
        $this->assertSame(0, app(BookingService::class)->remainingCapacity($dep));
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($date, '14:00', '17:00'));
    }

    public function test_calendario_per_sola_data_e_conservativo(): void
    {
        // Senza finestra oraria (calendario), il blocco vale per l'intera data.
        [$tour, $cats] = $this->makeTour(1, 10);
        $cat = $cats[0];
        $date = now()->addDays(13)->toDateString();
        $this->block($tour, $cat, $date, $date, '09:00', '12:30');

        // blockedCatamaranIdsOn senza orari → include comunque il catamarano.
        $this->assertContains($cat->id, TourCatamaranBlock::blockedCatamaranIdsOn($date));
    }

    public function test_secondo_catamarano_libero_resta_disponibile(): void
    {
        [$tour, $cats] = $this->makeTour(2, 10);
        $date = now()->addDays(14)->toDateString();
        // Riserva un catamarano tutto il giorno; l'altro resta libero.
        $this->block($tour, $cats[0], $date, $date, null, null);
        $dep = $this->departure($tour, $date, '14:00:00', '17:00:00');

        $this->assertSame(10, app(BookingService::class)->remainingCapacity($dep));
        $blocked = TourCatamaranBlock::blockedCatamaranIdsOn($date);
        $this->assertContains($cats[0]->id, $blocked);
        $this->assertNotContains($cats[1]->id, $blocked);
    }
}
