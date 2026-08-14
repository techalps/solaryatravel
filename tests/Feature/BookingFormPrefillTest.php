<?php

namespace Tests\Feature;

use App\Livewire\Public\BookingForm;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Precompilazione dei dati cliente per l'utente già registrato.
 *
 * Chi prenota da loggato non deve reinserire quello che ha già nel profilo.
 * Il telefono in particolare: se non viene ereditato, la prenotazione resta
 * senza numero e il contatto WhatsApp non compare, pur avendo il dato a
 * sistema. In modalità B2B invece l'utente loggato è l'agenzia, non il
 * cliente: lì non si deve precompilare nulla.
 */
class BookingFormPrefillTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTourAndDeparture(): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 10, 'is_active' => true,
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

        return [$tour, $dep];
    }

    public function test_il_telefono_del_profilo_viene_precompilato(): void
    {
        [$tour, $dep] = $this->makeTourAndDeparture();

        $user = User::factory()->create([
            'name' => 'Mario Rossi',
            'phone' => '+39 347 372 5939',
        ]);

        $component = Livewire::actingAs($user)
            ->test(BookingForm::class, ['tour' => $tour, 'departure' => $dep]);

        $component->assertSet('customer_phone', '+39 347 372 5939')
            ->assertSet('customer_first_name', 'Mario')
            ->assertSet('customer_last_name', 'Rossi')
            ->assertSet('customer_email', $user->email);
    }

    public function test_il_profilo_senza_telefono_lascia_il_campo_vuoto(): void
    {
        [$tour, $dep] = $this->makeTourAndDeparture();

        $user = User::factory()->create(['name' => 'Mario Rossi', 'phone' => null]);

        Livewire::actingAs($user)
            ->test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])
            ->assertSet('customer_phone', '');
    }

    public function test_l_ospite_non_loggato_ha_i_campi_vuoti(): void
    {
        [$tour, $dep] = $this->makeTourAndDeparture();

        Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])
            ->assertSet('customer_phone', '')
            ->assertSet('customer_email', '');
    }
}
