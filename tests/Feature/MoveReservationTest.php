<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Spostamento di una riserva a uso esclusivo tra catamarani: posti + blocco
 * si spostano insieme; il catamarano di partenza si libera.
 */
class MoveReservationTest extends TestCase
{
    use DatabaseTransactions;

    private array $ctx;

    private function makeReservation(): array
    {
        $catA = Catamaran::create(['name' => 'A'.uniqid(), 'slug' => 'a-'.uniqid(), 'capacity' => 10, 'is_active' => true]);
        $catB = Catamaran::create(['name' => 'B'.uniqid(), 'slug' => 'b-'.uniqid(), 'capacity' => 10, 'is_active' => true]);
        $tour = Tour::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'is_active' => true, 'booking_on_request' => false]);
        $tour->catamarans()->attach([$catA->id, $catB->id]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id, 'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00', 'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);
        $b = Booking::create([
            'booking_number' => 'RSV-'.uniqid(), 'tour_id' => $tour->id, 'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date, 'seats' => 2,
            'base_price' => 200, 'addons_total' => 0, 'discount_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 200, 'deposit_amount' => 0, 'balance_amount' => 0, 'amount_paid' => 0,
            'penalty_amount' => 0, 'payment_type' => 'full', 'currency' => 'EUR', 'status' => 'confirmed',
            'customer_first_name' => 'R', 'customer_last_name' => 'E', 'customer_email' => 'r'.uniqid().'@example.com',
            'source' => 'admin',
        ]);
        foreach (range(1, 2) as $n) {
            BookingSeat::create([
                'booking_id' => $b->id, 'seat_number' => $n, 'catamaran_id' => $catA->id,
                'qr_code' => strtoupper(uniqid()), 'price_paid' => 100,
            ]);
        }
        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $catA->id,
            'start_date' => $dep->departure_date->toDateString(), 'end_date' => $dep->departure_date->toDateString(),
            'reason' => 'Riservato da prenotazione admin #'.$b->booking_number,
        ]);

        return compact('tour', 'catA', 'catB', 'dep', 'b');
    }

    public function test_sposta_posti_e_riserva_e_libera_il_vecchio(): void
    {
        ['catA' => $catA, 'catB' => $catB, 'b' => $b] = $this->makeReservation();

        app(BookingService::class)->moveExclusiveReservation($b, $catB->id);

        // Posti sul nuovo catamarano.
        $this->assertEquals([$catB->id], $b->fresh()->seatRecords->pluck('catamaran_id')->unique()->values()->all());
        // Blocco (riserva) sul nuovo catamarano; nessuno più su A.
        $blockCat = TourCatamaranBlock::where('reason', 'like', '%#'.$b->booking_number.'%')->pluck('catamaran_id')->all();
        $this->assertEquals([$catB->id], $blockCat);
    }

    public function test_rifiuta_catamarano_non_libero(): void
    {
        ['catA' => $catA, 'catB' => $catB, 'dep' => $dep, 'b' => $b] = $this->makeReservation();

        // Occupa il catamarano B con un altro posto.
        $other = Booking::create([
            'booking_number' => 'OTH-'.uniqid(), 'tour_id' => $b->tour_id, 'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date, 'seats' => 1,
            'base_price' => 100, 'addons_total' => 0, 'discount_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 100, 'deposit_amount' => 0, 'balance_amount' => 0, 'amount_paid' => 0,
            'penalty_amount' => 0, 'payment_type' => 'full', 'currency' => 'EUR', 'status' => 'confirmed',
            'customer_first_name' => 'O', 'customer_last_name' => 'T', 'customer_email' => 'o'.uniqid().'@example.com',
            'source' => 'website',
        ]);
        BookingSeat::create([
            'booking_id' => $other->id, 'seat_number' => 1, 'catamaran_id' => $catB->id,
            'qr_code' => strtoupper(uniqid()), 'price_paid' => 100,
        ]);

        $this->expectException(\Exception::class);
        app(BookingService::class)->moveExclusiveReservation($b, $catB->id);
    }
}
