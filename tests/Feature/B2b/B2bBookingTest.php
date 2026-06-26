<?php

namespace Tests\Feature\B2b;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Isolamento dati tra agenzie e storno automatico della commissione
 * sull'annullamento (via BookingObserver).
 */
class B2bBookingTest extends TestCase
{
    use DatabaseTransactions;

    private function agency(float $rate = 20.0): User
    {
        return User::factory()->create(['role' => 'b2b', 'agency_name' => 'Ag', 'commission_rate' => $rate]);
    }

    private function bookingFor(?User $agency, float $total = 100.0): Booking
    {
        $tour = Tour::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'is_active' => true]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id, 'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00', 'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);
        $b = Booking::create([
            'booking_number' => 'TST-'.uniqid(), 'tour_id' => $tour->id, 'tour_departure_id' => $dep->id,
            'booking_date' => now()->toDateString(), 'seats' => 1,
            'base_price' => $total, 'addons_total' => 0, 'discount_amount' => 0, 'tax_amount' => 0,
            'total_amount' => $total, 'deposit_amount' => 0, 'balance_amount' => 0, 'amount_paid' => 0,
            'penalty_amount' => 0, 'payment_type' => 'full', 'currency' => 'EUR', 'status' => 'pending',
            'customer_first_name' => 'C', 'customer_last_name' => 'L', 'customer_email' => 'c'.uniqid().'@example.com',
            'source' => 'b2b',
        ]);
        if ($agency) {
            app(CommissionService::class)->attributeToAgency($b, $agency, 'b2b_portal');
        }
        return $b->refresh();
    }

    private function b2bHost(string $uri): string
    {
        return 'http://'.config('b2b.domain').$uri;
    }

    public function test_agenzia_vede_solo_le_proprie_prenotazioni(): void
    {
        $a1 = $this->agency();
        $a2 = $this->agency();
        $b1 = $this->bookingFor($a1);
        $b2 = $this->bookingFor($a2);

        // a1 vede la sua, NON quella di a2 (403).
        $this->actingAs($a1)->get($this->b2bHost('/prenotazioni/'.$b1->uuid))->assertOk();
        $this->actingAs($a1)->get($this->b2bHost('/prenotazioni/'.$b2->uuid))->assertForbidden();
    }

    public function test_lista_agenzia_filtra_per_proprietario(): void
    {
        $a1 = $this->agency();
        $a2 = $this->agency();
        $this->bookingFor($a1);
        $this->bookingFor($a2);

        $this->actingAs($a1)
            ->get($this->b2bHost('/prenotazioni'))
            ->assertOk()
            ->assertViewHas('bookings', function ($bookings) use ($a1) {
                return $bookings->every(fn ($b) => $b->b2b_user_id === $a1->id);
            });
    }

    public function test_annullamento_storna_la_commissione_via_observer(): void
    {
        $agency = $this->agency(20.0);
        $booking = $this->bookingFor($agency, 150.0);
        $this->assertSame('earned', $booking->commission_status);
        $this->assertEquals(30.0, (float) $booking->commission_amount);

        // Cambio stato a CANCELLED → l'observer deve stornare.
        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $booking->refresh();

        $this->assertSame('reversed', $booking->commission_status);
        $this->assertEquals(0.0, (float) $booking->commission_amount);
    }

    /**
     * Regressione: la vista commissioni admin non deve riportare commissioni
     * "pagate" inesistenti. (Bug: l'alias SUM 'commission_paid' veniva castato a
     * boolean dal modello → (float)true = 1€ fantasma. Fix: alias paid_sum.)
     */
    public function test_vista_commissioni_non_inventa_pagate(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $agency = $this->agency(20.0);
        // Due prenotazioni earned, NESSUNA pagata, nel mese corrente.
        $this->bookingFor($agency, 100.0);
        $this->bookingFor($agency, 50.0);

        $res = $this->actingAs($admin)
            ->get('/admin/commissioni?month='.now()->format('Y-m'))
            ->assertOk();

        $totals = $res->viewData('totals');
        $this->assertEquals(0.0, $totals->paid, 'Le commissioni pagate devono essere 0');
        $this->assertEquals(30.0, $totals->earned, 'Maturate = 20% di 150');
        $this->assertEquals(30.0, $totals->due, 'Da liquidare = maturate - pagate');
    }
}
