<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Variazione di prezzo su prenotazioni a prezzo manuale (tour su richiesta /
 * catamarano riservato).
 *
 * Il cliente aggiunge servizi in corsa (prezzo su) o si concorda uno sconto
 * (prezzo giù): prima il totale cambiava in silenzio, senza traccia di cosa
 * fosse dovuto a chi.
 *
 * Regole volute da Solarya:
 *   - nessuna email automatica: il link Stripe si genera e lo invia l'admin;
 *   - il canale non dipende dal pagamento originale: lo sceglie l'admin;
 *   - sugli aumenti la prenotazione resta CONFERMATA.
 */
class PriceChangeTest extends TestCase
{
    use DatabaseTransactions;

    private function makeOnRequestBooking(array $attributes = []): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat' . uniqid(), 'slug' => 'cat-' . uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        // booking_on_request = prezzo totale manuale.
        $tour = Tour::create([
            'name' => 'Esclusiva' . uniqid(), 'slug' => 'escl-' . uniqid(),
            'is_active' => true, 'booking_on_request' => true,
        ]);
        $tour->catamarans()->attach($cat->id);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        $booking = Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Bianchi',
            'customer_email' => uniqid() . '@example.com',
            'seats' => 4,
            'base_price' => 1000,
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ], $attributes));

        $booking->seatRecords()->create([
            'seat_number' => 1,
            'guest_first_name' => 'Anna', 'guest_last_name' => 'Bianchi',
            'price_paid' => $booking->total_amount, 'is_primary' => true,
        ]);

        return $booking;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function update(Booking $booking, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())
            ->put(route('admin.bookings.update', $booking), array_merge([
                'status' => $booking->status->value,
            ], $payload));
    }

    public function test_aumento_a_bonifico_lascia_la_prenotazione_confermata(): void
    {
        $booking = $this->makeOnRequestBooking();

        $this->update($booking, [
            'total_price' => 1300,
            'price_change_action' => 'bank_transfer',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(1300.0, (float) $booking->total_amount);
        // Richiesta esplicita: la prenotazione è valida, il cliente parte.
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        // La differenza resta come residuo da incassare.
        $this->assertSame(300.0, round((float) $booking->total_amount - (float) $booking->amount_paid, 2));
    }

    /**
     * Caso SLY-2026-00025: prenotazione da 6.000 € pagata a bonifico, poi
     * portata a 6.600 €. La differenza di 600 € deve essere visibile nella
     * scheda con il pulsante per registrarne l'incasso.
     *
     * Prima non compariva nulla: hasBalanceDue() richiedeva lo stato
     * deposit_paid, mentre sugli aumenti la prenotazione resta confirmed, e
     * balance_amount non veniva riallineato al nuovo totale.
     */
    public function test_dopo_un_aumento_la_scheda_mostra_il_residuo_e_il_pulsante(): void
    {
        $booking = $this->makeOnRequestBooking([
            'total_amount' => 6000, 'base_price' => 6000, 'amount_paid' => 6000,
            'payment_type' => 'bank_transfer',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => $booking->status->value,
            'total_price' => 6600,
            'price_change_action' => 'bank_transfer',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(600.0, $booking->outstandingAmount());
        $this->assertTrue($booking->hasBalanceDue(), 'Il residuo deve risultare da incassare.');
        $this->assertSame(600.0, (float) $booking->balance_amount, 'balance_amount va riallineato.');

        $html = $this->actingAs($admin)->get(route('admin.bookings.show', $booking))
            ->assertOk()->getContent();
        $this->assertStringContainsString('Da incassare', $html);
        $this->assertStringContainsString('Registra incasso', $html);

        // E il pulsante deve funzionare: incassa i 600, non altro.
        $this->actingAs($admin)
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(6600.0, (float) $booking->amount_paid);
        $this->assertSame(0.0, $booking->outstandingAmount());
        $this->assertFalse($booking->hasBalanceDue());
    }

    public function test_riduzione_con_storno_bonifico_registra_il_dovuto_senza_muovere_denaro(): void
    {
        $booking = $this->makeOnRequestBooking();

        $this->update($booking, [
            'total_price' => 800,
            'price_change_action' => 'bank_transfer',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(800.0, (float) $booking->total_amount);
        // Il bonifico lo esegue l'admin fuori dal sistema: qui resta l'impegno.
        $this->assertSame(200.0, (float) $booking->pending_refund_amount);
        // Finché non è confermato, l'incassato non cambia.
        $this->assertSame(1000.0, (float) $booking->amount_paid);
    }

    public function test_la_conferma_dello_storno_registra_l_uscita_di_cassa(): void
    {
        $booking = $this->makeOnRequestBooking();

        $this->update($booking, [
            'total_price' => 800,
            'price_change_action' => 'bank_transfer',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.confirm-refund', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(0.0, (float) $booking->pending_refund_amount, 'L\'impegno deve azzerarsi.');
        $this->assertSame(800.0, (float) $booking->amount_paid, 'L\'incassato scende dello storno.');

        // Il movimento deve esistere come rimborso, così la cassa lo vede.
        $refund = $booking->payments()->where('status', PaymentStatus::REFUNDED)->first();
        $this->assertNotNull($refund, 'Deve esistere il movimento di rimborso.');
        $this->assertSame(200.0, (float) $refund->refunded_amount);
        $this->assertNotNull($refund->refunded_at);
    }

    public function test_riduzione_senza_storno_abbassa_solo_il_prezzo(): void
    {
        $booking = $this->makeOnRequestBooking();

        $this->update($booking, [
            'total_price' => 700,
            'price_change_action' => 'none',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(700.0, (float) $booking->total_amount);
        $this->assertSame(0.0, (float) $booking->pending_refund_amount, 'Nessuno storno richiesto.');
        $this->assertSame(1000.0, (float) $booking->amount_paid, 'L\'incassato resta invariato.');
    }

    public function test_non_si_puo_stornare_piu_di_quanto_incassato(): void
    {
        // Cliente che non ha ancora versato nulla.
        $booking = $this->makeOnRequestBooking(['amount_paid' => 0]);

        $this->update($booking, [
            'total_price' => 600,
            'price_change_action' => 'bank_transfer',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(600.0, (float) $booking->total_amount);
        $this->assertSame(0.0, (float) $booking->pending_refund_amount,
            'Senza incasso non c\'è denaro da restituire.');
    }

    public function test_prezzo_invariato_non_produce_alcun_movimento(): void
    {
        $booking = $this->makeOnRequestBooking();

        $this->update($booking, [
            'total_price' => 1000,
            'price_change_action' => 'none',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(1000.0, (float) $booking->total_amount);
        $this->assertSame(0.0, (float) $booking->pending_refund_amount);
        $this->assertSame(0, $booking->payments()->count());
    }

    public function test_lo_storno_non_applica_le_penali_di_cancellazione(): void
    {
        // Partenza vicina: la policy di cancellazione tratterrebbe una penale.
        // Su uno sconto concordato NON deve entrare in gioco.
        $booking = $this->makeOnRequestBooking([
            'booking_date' => now()->addDay()->toDateString(),
        ]);

        $this->update($booking, [
            'total_price' => 800,
            'price_change_action' => 'bank_transfer',
        ]);

        $booking->refresh();
        $this->assertSame(200.0, (float) $booking->pending_refund_amount,
            'Lo sconto concordato torna al cliente per intero, senza penali.');
        $this->assertSame(0.0, (float) ($booking->penalty_amount ?? 0),
            'Non deve essere registrata alcuna penale.');
    }

    /**
     * Se il cliente ha versato più del nuovo totale e non si sceglie lo storno,
     * il messaggio deve dirlo. Nasce da un caso reale: un errore JavaScript
     * impediva al modale di aprirsi, il form partiva con "nessuno storno" e
     * l'admin non aveva modo di accorgersene.
     */
    public function test_avvisa_se_resta_un_eccedenza_versata_senza_storno(): void
    {
        $booking = $this->makeOnRequestBooking();

        $this->update($booking, [
            'total_price' => 800,
            'price_change_action' => 'none',
        ])->assertSessionHas('success', function (string $msg) {
            return str_contains($msg, 'in più del dovuto');
        });
    }

    public function test_il_prezzo_non_cambia_sui_tour_non_su_richiesta(): void
    {
        $booking = $this->makeOnRequestBooking();
        $booking->tour->update(['booking_on_request' => false]);

        $this->update($booking, [
            'total_price' => 5000,
            'price_change_action' => 'bank_transfer',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(1000.0, (float) $booking->total_amount,
            'Sui tour a listino il totale non si tocca a mano.');
    }
}
