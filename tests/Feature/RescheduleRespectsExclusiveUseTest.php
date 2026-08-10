<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Il CAMBIO DATA non deve poter scavalcare una riserva per uso esclusivo.
 *
 * Era l'unica via di prenotazione che non passava da distributeSeats(): i posti
 * si portavano dietro il catamaran_id della vecchia partenza, quindi spostare
 * una prenotazione su una data in cui quella barca era riservata in uso
 * esclusivo la infilava sopra la riserva, in silenzio. Da lì nasceva la doppia
 * prenotazione sulla stessa barca e sullo stesso giorno.
 */
class RescheduleRespectsExclusiveUseTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Un tour con due catamarani, una prenotazione normale di 3 posti su "A"
     * e una seconda partenza (data futura) su cui la si vuole spostare.
     *
     * @return array{0: Booking, 1: Catamaran, 2: Catamaran, 3: TourDeparture}
     */
    private function scenario(int $capacityB = 12): array
    {
        $catA = Catamaran::create([
            'name' => 'A' . uniqid(), 'slug' => 'a-' . uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $catB = Catamaran::create([
            'name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid(),
            'capacity' => $capacityB, 'is_active' => true,
        ]);

        // booking_on_request: il totale è manuale, così il test non dipende
        // dai listini/periodi (che qui non c'entrano nulla).
        $tour = Tour::create([
            'name' => 'Tour' . uniqid(), 'slug' => 'tour-' . uniqid(),
            'is_active' => true, 'booking_on_request' => true,
        ]);
        $tour->catamarans()->attach([$catA->id, $catB->id]);

        $depOrigine = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(20)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        $depTarget = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(40)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $depOrigine->id,
            'booking_date' => $depOrigine->departure_date,
            'customer_first_name' => 'Mario', 'customer_last_name' => 'Rossi',
            'customer_email' => uniqid() . '@example.com',
            'seats' => 3, 'base_price' => 900, 'total_amount' => 900,
            'amount_paid' => 900, 'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ]);

        foreach ([1, 2, 3] as $n) {
            $booking->seatRecords()->create([
                'seat_number' => $n, 'catamaran_id' => $catA->id,
                'guest_first_name' => 'Ospite', 'guest_last_name' => (string) $n,
                'price_paid' => $n === 1 ? 900 : 0, 'is_primary' => $n === 1,
            ]);
        }

        $booking->refresh();

        return [$booking, $catA, $catB, $depTarget];
    }

    /** Riserva un catamarano in uso esclusivo su tutta la giornata di una partenza. */
    private function riserva(Catamaran $cat, TourDeparture $dep, string $numero): void
    {
        TourCatamaranBlock::create([
            'tour_id' => $dep->tour_id,
            'catamaran_id' => $cat->id,
            'start_date' => $dep->departure_date,
            'end_date' => $dep->departure_date,
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'reason' => 'Riservato da prenotazione admin #' . $numero,
        ]);
    }

    private function catamaraniOccupati(Booking $booking): array
    {
        return $booking->fresh()->seatRecords()
            ->whereNull('cancelled_at')
            ->pluck('catamaran_id')
            ->map(fn ($id) => (int) $id)
            ->unique()->sort()->values()->all();
    }

    public function test_i_posti_si_spostano_sulla_barca_libera_invece_di_scavalcare_la_riserva(): void
    {
        [$booking, $catA, $catB, $target] = $this->scenario();

        // Sulla data di arrivo il catamarano A è riservato a un'altra prenotazione.
        $this->riserva($catA, $target, 'SLY-9999-00001');

        app(BookingService::class)->reschedule($booking, $target);

        $this->assertSame([$catB->id], $this->catamaraniOccupati($booking),
            'I posti devono finire su B: A è riservato in uso esclusivo.');
        $this->assertSame(0, $booking->fresh()->seatRecords()
            ->whereNull('cancelled_at')->where('catamaran_id', $catA->id)->count(),
            'Nessun posto può restare sul catamarano riservato.');
    }

    public function test_senza_barche_libere_il_cambio_data_viene_rifiutato(): void
    {
        [$booking, $catA, $catB, $target] = $this->scenario();
        $depOrigineId = $booking->tour_departure_id;

        // Entrambi i catamarani riservati sulla data di arrivo.
        $this->riserva($catA, $target, 'SLY-9999-00001');
        $this->riserva($catB, $target, 'SLY-9999-00002');

        try {
            app(BookingService::class)->reschedule($booking, $target);
            $this->fail('Lo spostamento doveva essere rifiutato: nessuna barca libera.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('non ci sono posti', $e->getMessage());
        }

        $this->assertSame($depOrigineId, $booking->fresh()->tour_departure_id,
            'La prenotazione deve restare sulla partenza di origine.');
    }

    public function test_la_capienza_residua_della_barca_libera_viene_rispettata(): void
    {
        // B ha 2 soli posti ma la prenotazione ne ha 3: non ci sta.
        [$booking, $catA, $catB, $target] = $this->scenario(capacityB: 2);
        $this->riserva($catA, $target, 'SLY-9999-00001');

        $this->expectException(\Exception::class);
        app(BookingService::class)->reschedule($booking, $target);
    }

    public function test_una_prenotazione_a_uso_esclusivo_puo_spostare_la_propria_riserva(): void
    {
        [$booking, $catA, , $target] = $this->scenario();

        // La riserva è di QUESTA prenotazione: non deve ostacolare sé stessa.
        $this->riserva($catA, $target, $booking->booking_number);

        app(BookingService::class)->reschedule($booking, $target);

        $this->assertSame([$catA->id], $this->catamaraniOccupati($booking),
            'La barca riservata dalla prenotazione stessa resta utilizzabile.');
    }

    public function test_il_cambio_data_da_admin_mostra_l_errore_senza_spostare(): void
    {
        [$booking, $catA, $catB, $target] = $this->scenario();
        $depOrigineId = $booking->tour_departure_id;

        $this->riserva($catA, $target, 'SLY-9999-00001');
        $this->riserva($catB, $target, 'SLY-9999-00002');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->post(route('admin.bookings.reschedule', $booking), [
                'tour_departure_id' => (string) $target->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($depOrigineId, $booking->fresh()->tour_departure_id);
    }
}
