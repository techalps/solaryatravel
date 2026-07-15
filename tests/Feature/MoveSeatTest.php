<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Spostamento di un singolo passeggero (posto) da un catamarano all'altro dal
 * dettaglio prenotazione admin. Consentito solo su catamarani disponibili;
 * rifiutato se pieno o riservato in uso esclusivo da un'altra prenotazione.
 */
class MoveSeatTest extends TestCase
{
    use DatabaseTransactions;

    private function scenario(int $capB = 10): array
    {
        $catA = Catamaran::create(['name' => 'A'.uniqid(), 'slug' => 'a-'.uniqid(), 'capacity' => 10, 'is_active' => true]);
        $catB = Catamaran::create(['name' => 'B'.uniqid(), 'slug' => 'b-'.uniqid(), 'capacity' => $capB, 'is_active' => true]);
        $tour = Tour::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'is_active' => true, 'booking_on_request' => false]);
        $tour->catamarans()->attach([$catA->id, $catB->id]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id, 'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00', 'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);
        $b = Booking::create([
            'booking_number' => 'MS-'.uniqid(), 'tour_id' => $tour->id, 'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date, 'seats' => 1,
            'base_price' => 100, 'addons_total' => 0, 'discount_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 100, 'deposit_amount' => 0, 'balance_amount' => 0, 'amount_paid' => 0,
            'penalty_amount' => 0, 'payment_type' => 'full', 'currency' => 'EUR', 'status' => 'confirmed',
            'customer_first_name' => 'C', 'customer_last_name' => 'L', 'customer_email' => 'c'.uniqid().'@example.com',
            'source' => 'admin',
        ]);
        $seat = BookingSeat::create([
            'booking_id' => $b->id, 'seat_number' => 1, 'catamaran_id' => $catA->id,
            'qr_code' => strtoupper(uniqid()), 'price_paid' => 100,
        ]);

        return compact('tour', 'catA', 'catB', 'dep', 'b', 'seat');
    }

    public function test_admin_sposta_passeggero_su_catamarano_disponibile(): void
    {
        ['catB' => $catB, 'b' => $b, 'seat' => $seat] = $this->scenario();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.bookings.seats.move', [$b, $seat]), ['catamaran_id' => $catB->id])
            ->assertRedirect();

        $this->assertEquals($catB->id, $seat->fresh()->catamaran_id);
    }

    public function test_non_sposta_su_catamarano_riservato_da_altra_prenotazione(): void
    {
        ['tour' => $tour, 'catB' => $catB, 'dep' => $dep, 'b' => $b, 'seat' => $seat] = $this->scenario();
        $admin = User::factory()->create(['role' => 'super_admin']);

        // catB riservato in uso esclusivo da un'ALTRA prenotazione, in fascia sovrapposta.
        TourCatamaranBlock::create([
            'tour_id' => $tour->id, 'catamaran_id' => $catB->id,
            'start_date' => $dep->departure_date->toDateString(), 'end_date' => $dep->departure_date->toDateString(),
            'start_time' => '09:00', 'end_time' => '13:00',
            'reason' => 'Riservato da prenotazione admin #ALTRA-123',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.seats.move', [$b, $seat]), ['catamaran_id' => $catB->id]);

        // Il posto NON deve essere stato spostato.
        $this->assertNotEquals($catB->id, $seat->fresh()->catamaran_id);
    }

    public function test_non_sposta_su_catamarano_pieno(): void
    {
        ['tour' => $tour, 'catB' => $catB, 'dep' => $dep, 'b' => $b, 'seat' => $seat] = $this->scenario(1);
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Riempi catB (capienza 1) con un'altra prenotazione attiva.
        $other = Booking::create([
            'booking_number' => 'OTH-'.uniqid(), 'tour_id' => $tour->id, 'tour_departure_id' => $dep->id,
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

        $this->actingAs($admin)
            ->post(route('admin.bookings.seats.move', [$b, $seat]), ['catamaran_id' => $catB->id]);

        $this->assertNotEquals($catB->id, $seat->fresh()->catamaran_id);
    }
}
