<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\DiscountCode;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dettaglio di un extra e di un codice sconto.
 *
 * Entrambe le pagine andavano in 500: caricavano `catamaran` su Booking, dove
 * quella relazione non esiste (sta su BookingSeat). L'errore era nel log di
 * produzione — "Call to undefined relationship [catamaran]" — e nessun test
 * apriva queste due pagine.
 *
 * Sull'extra c'era anche un secondo difetto: la relazione dichiarava
 * withTimestamps() ma la pivot booking_addons non ha updated_at.
 */
class AddonDiscountDetailTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeBooking(): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ]);
    }

    public function test_il_dettaglio_di_un_extra_con_prenotazioni_si_apre(): void
    {
        $booking = $this->makeBooking();
        $addon = Addon::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Transfer', 'slug' => 'transfer-'.uniqid(),
            'price' => 20, 'price_type' => 'per_person',
        ]);
        $addon->bookings()->attach($booking->id, [
            'quantity' => 2, 'unit_price' => 20, 'total_price' => 40,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.addons.show', $addon))
            ->assertOk();
    }

    public function test_il_dettaglio_di_un_codice_sconto_con_prenotazioni_si_apre(): void
    {
        $booking = $this->makeBooking();
        $discount = DiscountCode::create([
            'code' => 'TEST'.strtoupper(Str::random(5)),
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);
        $booking->update(['discount_code_id' => $discount->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.discounts.show', $discount))
            ->assertOk();
    }

    public function test_la_relazione_prenotazioni_di_un_extra_non_esplode(): void
    {
        // Query diretta: senza il fix sulla pivot fallisce prima di arrivare
        // alla vista, quindi vale la pena presidiarla a parte.
        $addon = Addon::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Pranzo', 'slug' => 'pranzo-'.uniqid(),
            'price' => 15, 'price_type' => 'per_person',
        ]);

        $this->assertCount(0, $addon->bookings()->latest('bookings.created_at')->get());
    }
}
