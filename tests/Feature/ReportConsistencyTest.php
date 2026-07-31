<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Payment;
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

    /**
     * IL CASO DELLE RATE A CAVALLO DI MESE.
     *
     * Prenoto a luglio per agosto: acconto a luglio, saldo ad agosto. L'acconto
     * deve pesare sulla cassa di luglio e il saldo su quella di agosto. Con
     * `bookings.amount_paid` (cumulativo senza data) è impossibile: finivano
     * entrambi nello stesso mese. Solo `payments.paid_at` lo risolve.
     */
    public function test_la_cassa_attribuisce_ogni_rata_al_mese_in_cui_e_stata_incassata(): void
    {
        [$tour, $deps] = $this->makeTour();

        $booking = $this->makeBooking($tour, $deps[0], [
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'created_at' => '2026-07-10 09:00:00',
            'booking_date' => '2026-08-20',
        ]);

        // Acconto a luglio, saldo ad agosto: due righe distinte.
        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => 300,
            'currency' => 'EUR', 'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => '2026-07-15 10:00:00',
        ]);
        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => 700,
            'currency' => 'EUR', 'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => '2026-08-05 10:00:00',
        ]);

        $luglio = ReportCriteria::cash('2026-07-01 00:00:00', '2026-07-31 23:59:59');
        $agosto = ReportCriteria::cash('2026-08-01 00:00:00', '2026-08-31 23:59:59');

        $this->assertSame(300.0, $luglio['gross_in'], 'L\'acconto deve pesare su luglio.');
        $this->assertSame(700.0, $agosto['gross_in'], 'Il saldo deve pesare su agosto.');
    }

    /**
     * Stessa prenotazione, ma il saldo viene ANTICIPATO a luglio: deve cadere
     * tutto in luglio, senza alcun caso particolare nel codice.
     */
    public function test_il_saldo_anticipato_cade_nel_mese_in_cui_e_stato_pagato(): void
    {
        [$tour, $deps] = $this->makeTour();

        $booking = $this->makeBooking($tour, $deps[0], [
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'created_at' => '2026-07-10 09:00:00',
            'booking_date' => '2026-08-20',
        ]);

        foreach ([[300, '2026-07-15 10:00:00'], [700, '2026-07-28 16:00:00']] as [$amount, $when]) {
            Payment::create([
                'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => $amount,
                'currency' => 'EUR', 'status' => PaymentStatus::SUCCEEDED, 'paid_at' => $when,
            ]);
        }

        $luglio = ReportCriteria::cash('2026-07-01 00:00:00', '2026-07-31 23:59:59');
        $agosto = ReportCriteria::cash('2026-08-01 00:00:00', '2026-08-31 23:59:59');

        $this->assertSame(1000.0, $luglio['gross_in'], 'Saldo anticipato: tutto in luglio.');
        $this->assertSame(0.0, $agosto['gross_in'], 'Ad agosto non deve restare nulla.');
    }

    /**
     * I tre criteri devono dare risposte DIVERSE sulla stessa prenotazione:
     * è il motivo per cui esistono separati.
     */
    public function test_i_tre_criteri_collocano_la_stessa_prenotazione_in_periodi_diversi(): void
    {
        [$tour, $deps] = $this->makeTour();

        $booking = $this->makeBooking($tour, $deps[0], [
            'total_amount' => 1000,
            'amount_paid' => 300,
            'created_at' => '2026-07-10 09:00:00',   // prenotata a luglio
            'booking_date' => '2026-08-20',          // parte ad agosto
        ]);

        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => 300,
            'currency' => 'EUR', 'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => '2026-09-02 10:00:00',      // incassata a settembre
        ]);

        $lug = ReportCriteria::bothViews('2026-07-01 00:00:00', '2026-07-31 23:59:59');
        $ago = ReportCriteria::bothViews('2026-08-01 00:00:00', '2026-08-31 23:59:59');
        $set = ReportCriteria::bothViews('2026-09-01 00:00:00', '2026-09-30 23:59:59');

        // Raccolta: luglio (quando è stata venduta).
        $this->assertSame(1000.0, $lug['raccolta']['gross']);
        $this->assertSame(0.0, $ago['raccolta']['gross']);

        // Competenza: agosto (quando parte).
        $this->assertSame(0.0, $lug['competenza']['gross']);
        $this->assertSame(1000.0, $ago['competenza']['gross']);

        // Cassa: settembre (quando è entrato il denaro).
        $this->assertSame(0.0, $lug['cassa']['gross_in']);
        $this->assertSame(0.0, $ago['cassa']['gross_in']);
        $this->assertSame(300.0, $set['cassa']['gross_in']);
    }

    /**
     * "Da incassare" scomposto: un acconto parziale e una prenotazione senza
     * alcun pagamento sono problemi diversi e non vanno sommati in una voce.
     */
    public function test_il_da_incassare_distingue_saldi_aperti_e_incassi_non_registrati(): void
    {
        [$tour, $deps] = $this->makeTour();
        $dep = $deps[0];

        // Acconto versato, saldo aperto.
        $this->makeBooking($tour, $dep, ['total_amount' => 1000, 'amount_paid' => 400]);
        // Nessun incasso a sistema.
        $this->makeBooking($tour, $dep, ['total_amount' => 500, 'amount_paid' => 0]);
        // Saldata: non deve comparire.
        $this->makeBooking($tour, $dep, ['total_amount' => 200, 'amount_paid' => 200]);

        $out = ReportCriteria::outstandingBreakdown(now()->subMonth(), now()->addMonth());

        $this->assertSame(600.0, $out['partial']['amount'], 'Saldo aperto = 1000 - 400.');
        $this->assertSame(1, $out['partial']['count']);
        $this->assertSame(500.0, $out['unpaid']['amount'], 'Nessun pagamento registrato.');
        $this->assertSame(1, $out['unpaid']['count']);
        $this->assertSame(1100.0, $out['total']['amount']);
    }

    /**
     * I rimborsi vanno scalati dalla cassa del mese in cui sono stati erogati.
     */
    public function test_i_rimborsi_abbassano_la_cassa_del_mese_di_erogazione(): void
    {
        [$tour, $deps] = $this->makeTour();
        $booking = $this->makeBooking($tour, $deps[0], ['total_amount' => 1000, 'amount_paid' => 1000]);

        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => 1000,
            'currency' => 'EUR', 'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => '2026-07-05 10:00:00',
        ]);
        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => 250,
            'refunded_amount' => 250, 'currency' => 'EUR',
            'status' => PaymentStatus::REFUNDED,
            'paid_at' => '2026-07-05 10:00:00',
            'refunded_at' => '2026-08-03 11:00:00',
        ]);

        $luglio = ReportCriteria::cash('2026-07-01 00:00:00', '2026-07-31 23:59:59');
        $agosto = ReportCriteria::cash('2026-08-01 00:00:00', '2026-08-31 23:59:59');

        $this->assertSame(1000.0, $luglio['net'], 'A luglio il rimborso non è ancora uscito.');
        $this->assertSame(-250.0, $agosto['net'], 'Ad agosto la cassa scende del rimborso.');
    }

    /**
     * Il confronto col periodo precedente deve essere di PARI durata e
     * allineato al calendario. Prima si sottraeva un numero di giorni
     * variabile: a inizio mese si confrontavano 2 giorni contro 1.
     */
    public function test_il_periodo_precedente_e_il_mese_pieno_precedente(): void
    {
        $reflection = new \ReflectionMethod(
            \App\Http\Controllers\Admin\ReportController::class,
            'getPreviousRange'
        );
        $reflection->setAccessible(true);

        $this->travelTo('2026-08-02 09:00:00');

        [$start, $end] = $reflection->invoke(
            app(\App\Http\Controllers\Admin\ReportController::class),
            'month',
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertSame('2026-07-01', $start->toDateString(), 'Deve partire dal 1° luglio.');
        $this->assertSame('2026-07-31', $end->toDateString(), 'Deve arrivare al 31 luglio, non al 2.');

        $this->travelBack();
    }
}
