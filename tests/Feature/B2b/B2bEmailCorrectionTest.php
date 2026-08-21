<?php

namespace Tests\Feature\B2b;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Correzione dell'email cliente e reinvio comunicazioni dal portale agenzie.
 *
 * Un'email sbagliata in fase di prenotazione rende il cliente irraggiungibile:
 * niente link di pagamento, niente biglietti, niente promemoria. Per questo è
 * l'unico dato che l'agenzia corregge da sé, senza passare da un'approvazione
 * admin come per date e passeggeri (dove invece cambiano prezzi e disponibilità).
 *
 * Punti presidiati:
 *  - isolamento: un'agenzia non tocca le prenotazioni di un'altra;
 *  - il reinvio manda la comunicazione pertinente allo STATO, non a scelta
 *    dell'agenzia (che non può quindi spedire email arbitrarie);
 *  - i biglietti ripartono anche se erano già stati inviati, altrimenti dopo la
 *    correzione dell'email il cliente resterebbe senza;
 *  - l'email precedente resta tracciata, per ricostruire dove era finita.
 */
class B2bEmailCorrectionTest extends TestCase
{
    use DatabaseTransactions;

    private function b2bHost(string $uri = '/'): string
    {
        return 'http://'.config('b2b.domain').$uri;
    }

    private function agency(): User
    {
        return User::factory()->create(['role' => 'b2b', 'agency_name' => 'Agenzia'.uniqid(), 'commission_rate' => 20]);
    }

    private function makeBooking(User $agency, BookingStatus $status, array $attributes = []): Booking
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

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'b2b_user_id' => $agency->getKey(),
            'attribution_source' => 'b2b_portal',
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'sbagliata@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => $status,
            'payment_type' => 'full',
        ], $attributes));
    }

    // ===== Correzione email =====

    public function test_l_agenzia_corregge_l_email_del_cliente(): void
    {
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/email'), [
                'customer_email' => 'giusta@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('giusta@example.com', $booking->fresh()->customer_email);
    }

    public function test_l_email_precedente_resta_tracciata(): void
    {
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/email'), [
                'customer_email' => 'giusta@example.com',
            ]);

        $storico = $booking->fresh()->metadata['email_changes'] ?? [];
        $this->assertCount(1, $storico);
        $this->assertSame('sbagliata@example.com', $storico[0]['from']);
        $this->assertSame('giusta@example.com', $storico[0]['to']);
        $this->assertSame($agency->getKey(), $storico[0]['agency_id']);
    }

    public function test_un_email_non_valida_viene_rifiutata(): void
    {
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/email'), [
                'customer_email' => 'non-una-email',
            ])
            ->assertSessionHasErrors('customer_email');

        $this->assertSame('sbagliata@example.com', $booking->fresh()->customer_email);
    }

    public function test_la_stessa_email_non_produce_una_modifica(): void
    {
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/email'), [
                'customer_email' => 'sbagliata@example.com',
            ])
            ->assertSessionHas('warning');

        $this->assertArrayNotHasKey('email_changes', $booking->fresh()->metadata ?? []);
    }

    public function test_su_una_prenotazione_annullata_l_email_non_si_modifica(): void
    {
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CANCELLED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/email'), [
                'customer_email' => 'giusta@example.com',
            ])
            ->assertSessionHas('warning');

        $this->assertSame('sbagliata@example.com', $booking->fresh()->customer_email);
    }

    public function test_un_agenzia_non_puo_toccare_la_prenotazione_di_un_altra(): void
    {
        $mia = $this->agency();
        $altra = $this->agency();
        $booking = $this->makeBooking($altra, BookingStatus::CONFIRMED);

        $this->actingAs($mia)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/email'), [
                'customer_email' => 'giusta@example.com',
            ])
            ->assertForbidden();

        $this->assertSame('sbagliata@example.com', $booking->fresh()->customer_email);
    }

    // ===== Reinvio comunicazioni =====

    public function test_su_una_confermata_reinvia_i_biglietti(): void
    {
        Mail::fake();
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/reinvia-comunicazioni'))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(\App\Mail\BookingTickets::class);
    }

    public function test_i_biglietti_ripartono_anche_se_gia_inviati(): void
    {
        // Il caso reale: i biglietti erano partiti verso l'email sbagliata.
        // Se il reinvio fosse idempotente il cliente resterebbe senza.
        Mail::fake();
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED, [
            'tickets_sent_at' => now()->subDay(),
        ]);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/reinvia-comunicazioni'))
            ->assertSessionHas('success');

        Mail::assertSent(\App\Mail\BookingTickets::class);
    }

    public function test_su_una_da_pagare_reinvia_gli_estremi_di_pagamento(): void
    {
        Mail::fake();
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::PENDING);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/reinvia-comunicazioni'))
            ->assertSessionHas('success');

        Mail::assertSent(\App\Mail\BookingPaymentLink::class);
        Mail::assertNotSent(\App\Mail\BookingTickets::class);
    }

    public function test_su_una_annullata_non_c_e_nulla_da_reinviare(): void
    {
        Mail::fake();
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CANCELLED);

        $this->actingAs($agency)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/reinvia-comunicazioni'))
            ->assertSessionHas('warning');

        Mail::assertNothingSent();
    }

    public function test_il_reinvio_e_isolato_per_agenzia(): void
    {
        Mail::fake();
        $mia = $this->agency();
        $booking = $this->makeBooking($this->agency(), BookingStatus::CONFIRMED);

        $this->actingAs($mia)
            ->post($this->b2bHost('/prenotazioni/'.$booking->uuid.'/reinvia-comunicazioni'))
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    // ===== Interfaccia =====

    public function test_il_dettaglio_mostra_correzione_email_e_reinvio(): void
    {
        $agency = $this->agency();
        $booking = $this->makeBooking($agency, BookingStatus::CONFIRMED);

        $this->actingAs($agency)
            ->get($this->b2bHost('/prenotazioni/'.$booking->uuid))
            ->assertOk()
            ->assertSee(route('b2b.bookings.update-email', $booking->uuid), false)
            ->assertSee(route('b2b.bookings.resend-communications', $booking->uuid), false);
    }
}
