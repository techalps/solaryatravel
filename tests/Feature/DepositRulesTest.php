<?php

namespace Tests\Feature;

use App\Livewire\Public\BookingForm;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Support\Settings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regole sull'acconto:
 *  - proponibile solo se la partenza è a >= deposit_min_days giorni (default 7),
 *    perché sotto quella soglia non c'è il tempo di incassare il saldo;
 *  - il cliente vede un avviso con il termine per il saldo (default 3 giorni
 *    prima della partenza), che coincide con balance_due_at salvata a DB.
 */
class DepositRulesTest extends TestCase
{
    use DatabaseTransactions;

    /** Imposta le settings in cache (Settings legge da lì). */
    private function settings(array $overrides = []): void
    {
        Cache::put('app_settings', array_merge([
            'deposit_enabled' => true,
            'deposit_percentage' => 50,
            'deposit_min_days' => 7,
            'balance_due_days' => 3,
            'bank_transfer_enabled' => false,
        ], $overrides), 3600);
    }

    protected function tearDown(): void
    {
        Cache::forget('app_settings');
        parent::tearDown();
    }

    /** @return array{0: Tour, 1: TourDeparture} */
    private function makeTour(int $daysAhead): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 100,
        ]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays($daysAhead)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return [$tour, $dep];
    }

    public function test_acconto_disponibile_solo_dalla_soglia_in_su(): void
    {
        $this->settings();

        // 6 giorni: sotto soglia. 7 giorni: esattamente alla soglia → ammesso.
        $this->assertFalse(Settings::depositAvailableFor(now()->addDays(6)->setTime(10, 0)));
        $this->assertTrue(Settings::depositAvailableFor(now()->addDays(7)->setTime(10, 0)));
        $this->assertTrue(Settings::depositAvailableFor(now()->addDays(30)->setTime(10, 0)));
    }

    public function test_la_soglia_non_dipende_dall_ora_di_prenotazione(): void
    {
        $this->settings();

        // Prenotazione a tarda sera: "manca una settimana" deve valere comunque.
        Carbon::setTestNow(Carbon::parse('2026-08-01 23:30:00'));
        $this->assertTrue(Settings::depositAvailableFor(Carbon::parse('2026-08-08 09:00:00')));
        Carbon::setTestNow();
    }

    public function test_acconto_non_disponibile_se_disattivato(): void
    {
        $this->settings(['deposit_enabled' => false]);

        $this->assertFalse(Settings::depositAvailableFor(now()->addDays(30)->setTime(10, 0)));
    }

    public function test_soglia_zero_rende_acconto_sempre_disponibile(): void
    {
        $this->settings(['deposit_min_days' => 0]);

        $this->assertTrue(Settings::depositAvailableFor(now()->addDay()->setTime(10, 0)));
    }

    public function test_il_form_nasconde_l_acconto_sotto_soglia(): void
    {
        $this->settings();
        [$tour, $dep] = $this->makeTour(3);   // partenza fra 3 giorni

        $component = Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep]);

        $this->assertFalse($component->instance()->depositAvailable);
        // Nessuna opzione acconto nel markup.
        $component->assertDontSee('value="deposit"', false);
    }

    public function test_il_form_mostra_acconto_e_avviso_sopra_soglia(): void
    {
        $this->settings();
        [$tour, $dep] = $this->makeTour(20);  // partenza fra 20 giorni

        $component = Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep]);

        $this->assertTrue($component->instance()->depositAvailable);
        $component->assertSee('value="deposit"', false);

        // Scegliendo l'acconto compare l'avviso col termine per il saldo.
        $component->set('paymentChoice', 'deposit')
            ->assertSee('bk-balance-notice', false)
            ->assertSee('3 giorni', false);
    }

    public function test_sotto_soglia_il_submit_forza_l_intero_importo(): void
    {
        $this->settings();
        [$tour, $dep] = $this->makeTour(2);   // partenza fra 2 giorni

        // Anche forzando paymentChoice=deposit (radio manomesso o stato stantio)
        // la prenotazione deve nascere SENZA acconto.
        $component = Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])
            ->set('customer_first_name', 'Mario')
            ->set('customer_last_name', 'Rossi')
            ->set('customer_email', 'mario'.uniqid().'@example.com')
            ->set('terms', true)
            ->set('paymentChoice', 'deposit')
            ->set('adults.0.doc_type', 'carta_identita')
            ->set('adults.0.doc_number', 'CA12345AB')
            ->set('adults.0.doc_country', 'IT')
            ->set('adults.0.doc_province', 'TO')
            ->set('adults.0.doc_place', 'Torino');

        $component->call('submit');

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $this->assertNotNull($booking, 'La prenotazione deve essere creata.');
        $this->assertSame('full', $booking->payment_type);
        $this->assertNull($booking->balance_due_at);
    }

    public function test_sopra_soglia_la_scadenza_saldo_e_a_tre_giorni_dalla_partenza(): void
    {
        $this->settings();
        [$tour, $dep] = $this->makeTour(20);

        $component = Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])
            ->set('customer_first_name', 'Mario')
            ->set('customer_last_name', 'Rossi')
            ->set('customer_email', 'mario'.uniqid().'@example.com')
            ->set('terms', true)
            ->set('paymentChoice', 'deposit')
            ->set('adults.0.doc_type', 'carta_identita')
            ->set('adults.0.doc_number', 'CA12345AB')
            ->set('adults.0.doc_country', 'IT')
            ->set('adults.0.doc_province', 'TO')
            ->set('adults.0.doc_place', 'Torino');

        $component->call('submit');

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $this->assertNotNull($booking);
        $this->assertSame('deposit', $booking->payment_type);

        // La scadenza salvata deve coincidere con l'avviso mostrato al cliente.
        $expected = Carbon::parse($dep->departure_date)->startOfDay()->subDays(3)->toDateString();
        $this->assertSame($expected, Carbon::parse($booking->balance_due_at)->toDateString());
    }

    public function test_balance_due_days_converte_il_vecchio_valore_in_ore(): void
    {
        // Installazione già configurata: solo balance_due_hours, nessun _days.
        Cache::put('app_settings', ['balance_due_hours' => 48], 3600);

        $this->assertSame(2, Settings::balanceDueDays());
        $this->assertSame(48, Settings::balanceDueHours());
    }

    public function test_balance_due_days_default_tre_giorni(): void
    {
        Cache::put('app_settings', [], 3600);

        $this->assertSame(3, Settings::balanceDueDays());
    }
}
