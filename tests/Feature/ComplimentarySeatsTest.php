<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourAgeBracket;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Posti omaggio concessi dall'admin: N partecipanti a 0€.
 *
 * Occupano il posto in barca (capienza, biglietto, QR) ma non si pagano.
 * L'omaggio si applica ai posti di MAGGIOR valore, così l'operatore sa sempre
 * cosa sta regalando. Gli extra sono omaggiati solo se richiesto.
 */
class ComplimentarySeatsTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: Tour, 1: TourDeparture} */
    private function scenario(): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 4,
        ]);
        $tour->catamarans()->attach($cat->id);

        $period = TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 150,   // prezzo adulto
        ]);

        // Riduzione bambini a 100€: serve a verificare che l'omaggio parta dal
        // posto più costoso (l'adulto a 150), non dal primo in lista.
        TourAgeBracket::create([
            'tour_id' => $tour->id,
            'tour_period_id' => $period->id,
            'label' => 'Bambini 8-14',
            'min_age' => 8,
            'max_age' => 14,
            'price' => 100,
            'counts_as_seat' => true,
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
            'adults_count' => 3,
            'children' => [],
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'admin_override' => true,
        ], $extra), 'admin');
    }

    public function test_senza_omaggio_il_totale_e_pieno(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep);

        // 3 adulti × 150
        $this->assertSame(450.0, (float) $booking->total_amount);
        $this->assertSame(3, $booking->seatRecords()->count());
        $this->assertSame(0, $booking->seatRecords()->where('price_paid', 0)->count());
    }

    public function test_un_posto_omaggio_azzera_un_biglietto(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep, ['complimentary_seats' => 1]);

        // 3 adulti × 150 − 1 omaggio = 300
        $this->assertSame(300.0, (float) $booking->total_amount);
        // I posti restano 3: l'omaggio non libera la barca.
        $this->assertSame(3, $booking->seatRecords()->count());
        $this->assertSame(1, $booking->seatRecords()->where('price_paid', 0)->count());
    }

    public function test_l_omaggio_parte_dal_posto_piu_costoso(): void
    {
        [$tour, $dep] = $this->scenario();

        // 1 adulto (150) + 1 bambino (100). L'omaggio deve valere 150, non 100.
        $booking = $this->book($tour, $dep, [
            'adults_count' => 1,
            'children' => [['dob' => now()->subYears(10)->toDateString()]],
            'complimentary_seats' => 1,
        ]);

        $this->assertSame(100.0, (float) $booking->total_amount, 'Deve restare il bambino a 100€.');
        $this->assertSame(
            150.0,
            (float) ($booking->metadata['complimentary']['amount'] ?? 0),
            'L\'importo omaggiato deve essere quello del posto più costoso.'
        );
    }

    public function test_i_posti_omaggio_occupano_la_barca(): void
    {
        [$tour, $dep] = $this->scenario();

        $before = app(BookingService::class)->remainingCapacity($dep);

        $this->book($tour, $dep, ['adults_count' => 3, 'complimentary_seats' => 3]);

        $after = app(BookingService::class)->remainingCapacity($dep);

        // Tutti e 3 omaggio: il totale è 0 ma i posti sono occupati comunque.
        $this->assertSame($before - 3, $after, 'I posti omaggio devono occupare la capienza.');
    }

    public function test_tutti_i_posti_omaggio_azzerano_il_totale(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep, ['adults_count' => 2, 'complimentary_seats' => 2]);

        $this->assertSame(0.0, (float) $booking->total_amount);
        $this->assertSame(2, $booking->seatRecords()->where('price_paid', 0)->count());
    }

    public function test_l_omaggio_non_puo_superare_i_partecipanti(): void
    {
        [$tour, $dep] = $this->scenario();

        // 2 adulti ma 5 posti omaggio richiesti: il calcolo si limita a 2.
        $booking = $this->book($tour, $dep, ['adults_count' => 2, 'complimentary_seats' => 5]);

        $this->assertSame(0.0, (float) $booking->total_amount);
        $this->assertSame(2, (int) ($booking->metadata['complimentary']['seats'] ?? 0));
    }

    /**
     * La scelta sugli extra: per default restano a pagamento (il fornitore va
     * pagato comunque), ma l'admin può includerli nell'omaggio.
     */
    public function test_extra_a_pagamento_per_default(): void
    {
        [$tour, $dep] = $this->scenario();

        $addon = Addon::create([
            'name' => 'Pranzo', 'slug' => 'pranzo-'.uniqid(),
            'price' => 20, 'price_type' => 'per_person', 'is_active' => true,
        ]);

        $booking = $this->book($tour, $dep, [
            'adults_count' => 2,
            'addons' => [$addon->id],
            'complimentary_seats' => 1,
        ]);

        // 2 adulti × 150 = 300, meno 1 omaggio (150) = 150.
        // Extra: 2 persone × 20 = 40 (l'omaggio NON li copre).
        $this->assertSame(40.0, (float) $booking->addons_total);
        $this->assertSame(190.0, (float) $booking->total_amount);
    }

    public function test_extra_omaggiati_se_richiesto(): void
    {
        [$tour, $dep] = $this->scenario();

        $addon = Addon::create([
            'name' => 'Pranzo', 'slug' => 'pranzo-'.uniqid(),
            'price' => 20, 'price_type' => 'per_person', 'is_active' => true,
        ]);

        $booking = $this->book($tour, $dep, [
            'adults_count' => 2,
            'addons' => [$addon->id],
            'complimentary_seats' => 1,
            'complimentary_includes_addons' => true,
        ]);

        // Extra pagati solo per 1 persona (l'altra è omaggio): 20.
        $this->assertSame(20.0, (float) $booking->addons_total);
        $this->assertSame(170.0, (float) $booking->total_amount);
        $this->assertTrue((bool) $booking->metadata['complimentary']['includes_addons']);
    }

    public function test_la_traccia_dell_omaggio_e_salvata(): void
    {
        [$tour, $dep] = $this->scenario();
        $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $booking = $this->book($tour, $dep, [
            'complimentary_seats' => 1,
            'complimentary_reason' => 'Ospite dello staff',
        ]);

        $meta = $booking->metadata['complimentary'] ?? null;

        $this->assertNotNull($meta, 'La traccia dell\'omaggio deve essere salvata.');
        $this->assertSame(1, $meta['seats']);
        $this->assertSame(150.0, (float) $meta['amount']);
        $this->assertSame('Ospite dello staff', $meta['reason']);
        $this->assertSame($admin->id, $meta['granted_by']);
    }

    public function test_senza_omaggio_nessuna_traccia(): void
    {
        [$tour, $dep] = $this->scenario();

        $booking = $this->book($tour, $dep);

        $this->assertNull($booking->metadata['complimentary'] ?? null);
    }

    public function test_il_form_admin_rifiuta_piu_omaggi_dei_partecipanti(): void
    {
        [$tour, $dep] = $this->scenario();
        $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'tour_departure_id' => (string) $dep->id,
                'complimentary_seats' => 5,   // ma i partecipanti sono 1
                'adults' => [[
                    'first_name' => 'Mario', 'last_name' => 'Rossi',
                    'doc_type' => 'carta_identita', 'doc_number' => 'CA1',
                    'doc_expiry' => now()->addYear()->toDateString(),
                    'doc_country' => 'IT', 'doc_province' => 'TO', 'doc_place' => 'Torino',
                ]],
                'customer_first_name' => 'Mario',
                'customer_last_name' => 'Rossi',
                'customer_email' => 'mario'.uniqid().'@example.com',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Booking::where('tour_id', $tour->id)->count());
    }
}
