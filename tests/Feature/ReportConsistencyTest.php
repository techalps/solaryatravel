<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Services\ReportExportService;
use App\Support\ReportCriteria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Coerenza dei report.
 *
 * Due difetti segnalati dal vivo:
 *
 * 1) CIFRE DIVERSE fra pagina Ricavi ed export Excel. Causa: i ricavi contavano
 *    per DATA ESCURSIONE con i soli stati di ricavo, mentre il foglio
 *    "Prenotazioni" elencava per DATA CREAZIONE includendo anche le ANNULLATE e
 *    sommandone il totale nella colonna Venduto. Nei dati reali: 4.320 € contro
 *    4.000 €, differenza = una prenotazione annullata da 320 €.
 *
 * 2) FASCE ORARIE sempre 09:00. Causa: il GROUP BY stava su tour_departure_id
 *    (una riga per ogni partenza) e l'orario veniva letto dopo, dalla relazione:
 *    la stessa fascia compariva ripetuta N volte invece di essere sommata.
 */
class ReportConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTour(array $times = ['09:00']): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro'.uniqid(), 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);

        // Lista, NON mappa per orario: servono anche due partenze diverse alla
        // stessa ora (è esattamente il caso che il vecchio raggruppamento sbagliava).
        $departures = [];
        foreach ($times as $k => $time) {
            $departures[] = TourDeparture::create([
                'tour_id' => $tour->id,
                'departure_date' => now()->addDays(3 + $k)->toDateString(),
                'start_time' => $time . ':00',
                'end_time' => '13:00:00',
                'status' => 'scheduled', 'price_modifier' => 1.0,
            ]);
        }

        return [$tour, $departures];
    }

    private function makeBooking(Tour $tour, TourDeparture $dep, array $attributes = []): Booking
    {
        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'm'.uniqid().'@example.com',
            'seats' => 2,
            'base_price' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
            'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ], $attributes));
    }

    public function test_le_annullate_non_entrano_nel_venduto(): void
    {
        [$tour, $deps] = $this->makeTour();
        $dep = $deps[0];

        $this->makeBooking($tour, $dep, ['total_amount' => 1000, 'amount_paid' => 1000]);
        // L'annullata NON deve pesare sul venduto (era il caso reale da 320 €).
        $this->makeBooking($tour, $dep, [
            'total_amount' => 320, 'amount_paid' => 0,
            'status' => BookingStatus::CANCELLED,
        ]);

        $venduto = (float) ReportCriteria::revenue(now()->subMonth(), now()->addMonth())
            ->where('tour_id', $tour->id)->sum('total_amount');

        $this->assertSame(1000.0, $venduto, 'Il venduto deve escludere le annullate.');
    }

    public function test_export_e_pagina_ricavi_danno_lo_stesso_totale(): void
    {
        [$tour, $deps] = $this->makeTour();
        $dep = $deps[0];

        $this->makeBooking($tour, $dep, ['total_amount' => 1000, 'amount_paid' => 1000]);
        $this->makeBooking($tour, $dep, [
            'total_amount' => 320, 'amount_paid' => 0,
            'status' => BookingStatus::CANCELLED,
        ]);

        $start = now()->subMonth();
        $end = now()->addMonth();

        $ricavi = (float) ReportCriteria::revenue($start, $end)->sum('total_amount');

        // Somma della colonna "Venduto" del foglio Prenotazioni dell'export.
        $book = app(ReportExportService::class)->build($start, $end, 'test');
        $sheet = $book->getSheetByName('Prenotazioni');

        $sommaExport = 0.0;
        $annullateInElenco = 0;
        foreach ($sheet->getRowIterator(3) as $row) {
            $i = $row->getRowIndex();
            if (! $sheet->getCell('A'.$i)->getValue()) {
                continue;
            }
            if ($sheet->getCell('K'.$i)->getValue() === 'No') {
                $annullateInElenco++;
            }
            $sommaExport += (float) $sheet->getCell('M'.$i)->getValue();
        }

        $this->assertSame(
            round($ricavi, 2),
            round($sommaExport, 2),
            'La colonna Venduto dell\'export deve coincidere col totale dei ricavi.'
        );
        // Le annullate restano visibili in elenco: sparire non va bene, pesare sì.
        $this->assertGreaterThan(0, $annullateInElenco, 'Le annullate devono restare in elenco.');
    }

    public function test_le_fasce_orarie_sono_aggregate_per_orario(): void
    {
        [$tour, $deps] = $this->makeTour(['09:00', '09:00', '18:30']);

        // Due partenze DIVERSE alla stessa ora + una a un'altra ora.
        $this->makeBooking($tour, $deps[0]);   // 09:00, partenza A
        $this->makeBooking($tour, $deps[1]);   // 09:00, partenza B (giorno diverso)
        $this->makeBooking($tour, $deps[2]);   // 18:30

        $admin = User::factory()->create(['role' => 'super_admin']);
        $html = $this->actingAs($admin)->get('/admin/reports/occupancy?period=all')
            ->assertOk()->getContent();

        // La fascia 09:00 deve comparire UNA volta sola, non una per partenza.
        // Prima del fix la stessa etichetta si ripeteva per ogni tour_departure_id.
        $slots = [];
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\ReportController::class, 'timeSlotPopularity');
        $reflection->setAccessible(true);
        $rows = $reflection->invoke(
            app(\App\Http\Controllers\Admin\ReportController::class),
            now()->subMonth(),
            now()->addMonth(),
            8
        );

        foreach ($rows as $row) {
            $this->assertArrayNotHasKey(
                $row->time_slot,
                $slots,
                "La fascia {$row->time_slot} è duplicata: si sta ancora raggruppando per partenza."
            );
            $slots[$row->time_slot] = $row->count;
        }

        // Le due prenotazioni delle 09:00 (su partenze diverse) vanno sommate.
        $this->assertSame(2, $slots['09:00'] ?? null, 'Le 09:00 devono sommare le partenze diverse.');
        $this->assertSame(1, $slots['18:30'] ?? null);
    }

    public function test_le_fasce_orarie_ignorano_le_annullate(): void
    {
        [$tour, $deps] = $this->makeTour(['18:30']);
        $dep = $deps[0];

        $this->makeBooking($tour, $dep);
        $this->makeBooking($tour, $dep, ['status' => BookingStatus::CANCELLED]);

        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\ReportController::class, 'timeSlotPopularity');
        $reflection->setAccessible(true);
        $rows = $reflection->invoke(
            app(\App\Http\Controllers\Admin\ReportController::class),
            now()->subMonth(),
            now()->addMonth(),
            8
        );

        $slot = collect($rows)->firstWhere('time_slot', '18:30');
        $this->assertSame(1, $slot->count, 'Una prenotazione annullata non è "richiesta".');
    }

    public function test_la_ripartizione_canali_scorpora_le_provvigioni(): void
    {
        [$tour, $deps] = $this->makeTour();
        $dep = $deps[0];

        $agency = User::factory()->create(['role' => 'b2b']);

        $this->makeBooking($tour, $dep, ['total_amount' => 1000, 'amount_paid' => 1000]);
        $this->makeBooking($tour, $dep, [
            'total_amount' => 200, 'amount_paid' => 200,
            'b2b_user_id' => $agency->id, 'commission_amount' => 30,
        ]);

        $c = ReportCriteria::channelBreakdown(now()->subMonth(), now()->addMonth());

        $this->assertSame(1000.0, $c['direct']['gross']);
        $this->assertSame(200.0, $c['agency']['gross']);
        $this->assertSame(30.0, $c['agency']['commission']);
        $this->assertSame(1200.0, $c['total']['gross']);
        // Il netto è il dato che prima mancava: venduto meno le provvigioni.
        $this->assertSame(1170.0, $c['total']['net']);
        // Sul diretto la provvigione non si applica.
        $this->assertSame(0.0, $c['direct']['commission']);
    }

    public function test_le_pagine_report_si_renderizzano(): void
    {
        [$tour, $deps] = $this->makeTour();
        $this->makeBooking($tour, $deps[0]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        foreach (['', '/revenue', '/bookings', '/occupancy'] as $path) {
            $this->actingAs($admin)
                ->get('/admin/reports'.$path.'?period=all')
                ->assertOk();
        }
    }
}
