<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourAgeBracket;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Sconto commerciale applicato dall'admin in creazione, in € o in %.
 *
 * Si somma agli altri meccanismi già esistenti (codice sconto, posti omaggio)
 * ma non li sostituisce: l'omaggio azzera dei posti, lo sconto riduce il
 * prezzo di quel che resta.
 */
class ManualDiscountTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: Tour, 1: TourDeparture} */
    private function scenario(): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat' . uniqid(), 'slug' => 'cat-' . uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-' . uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 4,
        ]);
        $tour->catamarans()->attach($cat->id);

        $period = TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 150,
        ]);

        TourAgeBracket::create([
            'tour_id' => $tour->id,
            'tour_period_id' => $period->id,
            'label' => 'Bambini 8-14',
            'min_age' => 8, 'max_age' => 14,
            'price' => 100, 'counts_as_seat' => true,
        ]);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(20)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '14:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return [$tour, $dep];
    }

    /** @param array<string, mixed> $extra */
    private function book(Tour $tour, TourDeparture $dep, array $extra = []): Booking
    {
        return app(BookingService::class)->create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'adults_count' => 3,   // 3 × 150 = 450
            'children' => [],
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario' . uniqid() . '@example.com',
            'admin_override' => true,
        ], $extra), 'admin');
    }

    public function test_sconto_in_euro(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep, [
            'manual_discount' => ['type' => 'amount', 'value' => 50],
        ]);

        $this->assertSame(400.0, (float) $booking->total_amount, '450 − 50 = 400.');
        $this->assertSame(50.0, (float) $booking->discount_amount);
    }

    public function test_sconto_in_percentuale(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep, [
            'manual_discount' => ['type' => 'percent', 'value' => 10],
        ]);

        $this->assertSame(405.0, (float) $booking->total_amount, '450 − 10% = 405.');
        $this->assertSame(45.0, (float) $booking->discount_amount);
    }

    public function test_lo_sconto_non_puo_superare_il_totale(): void
    {
        [$tour, $dep] = $this->scenario();

        // Errore di battitura: 5000 invece di 50. Il totale non deve andare
        // sotto zero, cioè non deve nascere un credito verso il cliente.
        $booking = $this->book($tour, $dep, [
            'manual_discount' => ['type' => 'amount', 'value' => 5000],
        ]);

        $this->assertSame(0.0, (float) $booking->total_amount);
        $this->assertSame(450.0, (float) $booking->discount_amount, 'Lo sconto si ferma al totale.');
    }

    public function test_percentuale_oltre_cento_azzera_al_massimo(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep, [
            'manual_discount' => ['type' => 'percent', 'value' => 150],
        ]);

        $this->assertSame(0.0, (float) $booking->total_amount);
    }

    public function test_sconto_e_omaggio_si_combinano_nell_ordine_giusto(): void
    {
        [$tour, $dep] = $this->scenario();

        // 3 adulti = 450. Un omaggio toglie 150 → 300. Poi 10% → 270.
        // Lo sconto NON deve applicarsi ai 450 pieni (sarebbe 255).
        $booking = $this->book($tour, $dep, [
            'complimentary_seats' => 1,
            'manual_discount' => ['type' => 'percent', 'value' => 10],
        ]);

        $this->assertSame(270.0, (float) $booking->total_amount,
            'Lo sconto si applica dopo l\'omaggio, su 300 e non su 450.');
    }

    public function test_valore_zero_non_produce_sconto(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep, [
            'manual_discount' => ['type' => 'amount', 'value' => 0],
        ]);

        $this->assertSame(450.0, (float) $booking->total_amount);
        $this->assertSame(0.0, (float) $booking->discount_amount);
    }

    public function test_senza_sconto_il_totale_resta_pieno(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep);

        $this->assertSame(450.0, (float) $booking->total_amount);
        $this->assertSame(0.0, (float) $booking->discount_amount);
    }

    public function test_il_form_admin_applica_lo_sconto(): void
    {
        [$tour, $dep] = $this->scenario();
        $admin = \App\Models\User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->post(route('admin.bookings.store'), [
            'tour_id' => $tour->id,
            'tour_departure_id' => (string) $dep->id,
            // Il documento è obbligatorio per ogni passeggero.
            'adults' => [
                ['first_name' => 'Mario', 'last_name' => 'Rossi', 'doc_type' => 'carta_identita', 'doc_number' => 'AA1111111'],
                ['first_name' => 'Anna', 'last_name' => 'Verdi', 'doc_type' => 'carta_identita', 'doc_number' => 'AA2222222'],
                ['first_name' => 'Luca', 'last_name' => 'Bianchi', 'doc_type' => 'carta_identita', 'doc_number' => 'AA3333333'],
            ],
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario' . uniqid() . '@example.com',
            'payment_method' => 'manual',
            'manual_discount_type' => 'percent',
            'manual_discount_value' => 20,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame(360.0, (float) $booking->total_amount, '450 − 20% = 360.');
    }
}
