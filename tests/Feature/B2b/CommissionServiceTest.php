<?php

namespace Tests\Feature\B2b;

use App\Models\Booking;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Logica provvigioni B2B: calcolo snapshot sul totale IVA inclusa e storno.
 */
class CommissionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function agency(float $rate = 20.0): User
    {
        return User::factory()->create([
            'role' => 'b2b',
            'agency_name' => 'Test Agency',
            'commission_rate' => $rate,
        ]);
    }

    /** Crea una prenotazione minima con un dato totale (con tour+partenza di supporto). */
    private function booking(float $total = 150.0): Booking
    {
        $tour = \App\Models\Tour::create([
            'name' => 'Tour Test', 'slug' => 'tour-test-'.uniqid(), 'is_active' => true,
        ]);
        $departure = \App\Models\TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '13:00:00',
            'status' => 'scheduled',
            'price_modifier' => 1.0,
        ]);

        return Booking::create([
            'booking_number' => 'TST-'.uniqid(),
            'tour_id' => $tour->id,
            'tour_departure_id' => $departure->id,
            'booking_date' => now()->toDateString(),
            'seats' => 1,
            'base_price' => $total,
            'addons_total' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'deposit_amount' => 0,
            'balance_amount' => 0,
            'amount_paid' => 0,
            'penalty_amount' => 0,
            'payment_type' => 'full',
            'currency' => 'EUR',
            'status' => 'pending',
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'source' => 'b2b',
        ]);
    }

    public function test_commissione_calcolata_su_totale_iva_inclusa(): void
    {
        $agency = $this->agency(20.0);
        $booking = $this->booking(150.0);

        app(CommissionService::class)->attributeToAgency($booking, $agency, 'b2b_portal');
        $booking->refresh();

        $this->assertSame($agency->id, $booking->b2b_user_id);
        $this->assertSame('b2b_portal', $booking->attribution_source);
        $this->assertEquals(20.0, (float) $booking->commission_rate_snapshot);
        $this->assertEquals(30.0, (float) $booking->commission_amount); // 20% di 150
        $this->assertSame('earned', $booking->commission_status);
    }

    public function test_snapshot_non_cambia_se_cambia_la_rate_agenzia(): void
    {
        $agency = $this->agency(20.0);
        $booking = $this->booking(100.0);
        app(CommissionService::class)->attributeToAgency($booking, $agency, 'b2b_portal');

        // L'agenzia cambia provvigione DOPO la prenotazione.
        $agency->update(['commission_rate' => 50.0]);
        $booking->refresh();

        // Lo snapshot resta quello del momento della creazione.
        $this->assertEquals(20.0, (float) $booking->commission_rate_snapshot);
        $this->assertEquals(20.0, (float) $booking->commission_amount);
    }

    public function test_storno_azzera_la_commissione(): void
    {
        $agency = $this->agency(20.0);
        $booking = $this->booking(150.0);
        app(CommissionService::class)->attributeToAgency($booking, $agency, 'b2b_portal');

        app(CommissionService::class)->reverse($booking, 'annullamento');
        $booking->refresh();

        $this->assertEquals(0.0, (float) $booking->commission_amount);
        $this->assertSame('reversed', $booking->commission_status);
    }

    public function test_storno_idempotente_e_non_perde_traccia_se_gia_pagata(): void
    {
        $agency = $this->agency(20.0);
        $booking = $this->booking(150.0);
        app(CommissionService::class)->attributeToAgency($booking, $agency, 'b2b_portal');
        $booking->update(['commission_paid' => true, 'commission_paid_at' => now()]);

        app(CommissionService::class)->reverse($booking, 'rimborso');
        $booking->refresh();

        $this->assertSame('reversed', $booking->commission_status);
        // Il record resta (commission_paid true) per recupero al payout successivo.
        $this->assertTrue((bool) $booking->commission_paid);

        // Secondo storno: no-op.
        app(CommissionService::class)->reverse($booking, 'doppio');
        $this->assertSame('reversed', $booking->fresh()->commission_status);
    }

    public function test_non_attribuisce_a_utente_non_b2b(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $booking = $this->booking(150.0);

        app(CommissionService::class)->attributeToAgency($booking, $customer, 'b2b_portal');
        $booking->refresh();

        $this->assertNull($booking->b2b_user_id);
        $this->assertNull($booking->commission_amount);
    }
}
