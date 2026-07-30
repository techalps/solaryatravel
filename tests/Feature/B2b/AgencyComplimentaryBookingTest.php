<?php

namespace Tests\Feature\B2b;

use App\Enums\BookingStatus;
use App\Livewire\Public\BookingForm;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prenotazioni a 0€ per le AGENZIE.
 *
 * L'admin poteva già registrare ospiti a 0€ (posti omaggio); serviva lo stesso
 * per le agenzie, ma come concessione per SINGOLA agenzia: un flag nelle sue
 * impostazioni (users.can_book_complimentary), spento per default.
 *
 * Punti che questi test presidiano:
 *  - il permesso è per agenzia, non del ruolo b2b;
 *  - il campo del form non basta: il permesso è riverificato lato server, quindi
 *    manometterlo non produce sconti;
 *  - a totale zero la prenotazione nasce CONFERMATA, senza scadenza di pagamento
 *    (altrimenti il job di pulizia la annullerebbe) e senza email "paga 0 €";
 *  - i posti restano occupati e su di essi non maturano provvigioni.
 */
class AgencyComplimentaryBookingTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTourWithDeparture(): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro'.uniqid(), 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 150,
        ]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return [$tour, $dep];
    }

    private function agency(bool $canBookComplimentary): User
    {
        return User::factory()->create([
            'role' => 'b2b',
            'agency_name' => 'Agenzia Test',
            'commission_rate' => 20,
            'can_book_complimentary' => $canBookComplimentary,
        ]);
    }

    private function form(Tour $tour, TourDeparture $dep)
    {
        return Livewire::test(BookingForm::class, [
            'tour' => $tour, 'departure' => $dep, 'b2bMode' => true,
        ])
            ->set('customer_first_name', 'Ospite')
            ->set('customer_last_name', 'Omaggio')
            ->set('customer_email', 'ospite'.uniqid().'@example.com')
            ->set('terms', true)
            ->set('adults.0.doc_type', 'carta_identita')
            ->set('adults.0.doc_number', 'CA999XX');
    }

    public function test_il_permesso_e_per_singola_agenzia(): void
    {
        $this->assertTrue($this->agency(true)->canBookComplimentary());
        $this->assertFalse($this->agency(false)->canBookComplimentary());
    }

    public function test_il_permesso_non_vale_per_i_ruoli_non_agenzia(): void
    {
        // Anche col flag a true, un cliente non può fare omaggi: il permesso è
        // legato al ruolo b2b, non alla sola colonna.
        $customer = User::factory()->create(['role' => 'customer', 'can_book_complimentary' => true]);

        $this->assertFalse($customer->canBookComplimentary());
    }

    public function test_l_agenzia_autorizzata_vede_l_opzione_e_azzera_il_totale(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $this->actingAs($this->agency(true));

        $component = $this->form($tour, $dep);
        $this->assertTrue($component->instance()->canBookComplimentary());

        $pieno = (float) $component->instance()->pricing()['total_amount'];
        $this->assertGreaterThan(0, $pieno);

        $component->set('complimentary', true);
        $this->assertSame(0.0, (float) $component->instance()->pricing()['total_amount']);

        // L'opzione è visibile nel form dell'agenzia autorizzata.
        $this->assertStringContainsString('bk-complimentary', $component->html());
    }

    public function test_l_agenzia_non_autorizzata_non_ottiene_lo_sconto_nemmeno_manomettendo(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $this->actingAs($this->agency(false));

        $component = $this->form($tour, $dep);
        $this->assertFalse($component->instance()->canBookComplimentary());

        // L'opzione non compare nemmeno...
        $this->assertStringNotContainsString('bk-complimentary', $component->html());

        // ...e forzare la proprietà non azzera il totale: il permesso è
        // riverificato lato server in complimentarySeats().
        $pieno = (float) $component->instance()->pricing()['total_amount'];
        $component->set('complimentary', true);

        $this->assertSame($pieno, (float) $component->instance()->pricing()['total_amount']);
    }

    public function test_la_prenotazione_omaggio_nasce_confermata_e_senza_pagamento(): void
    {
        Mail::fake();

        [$tour, $dep] = $this->makeTourWithDeparture();
        $this->actingAs($this->agency(true));

        $this->form($tour, $dep)
            ->set('complimentary', true)
            ->set('complimentaryReason', 'Fam trip')
            ->call('submit')
            ->assertHasNoErrors();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $this->assertNotNull($booking);

        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame(0.0, (float) $booking->total_amount);
        // Nessuna scadenza: con payment_deadline valorizzato il job di pulizia
        // annullerebbe una prenotazione che non ha nulla da pagare.
        $this->assertNull($booking->payment_deadline);
        // Nessuna provvigione su un omaggio.
        $this->assertSame(0.0, (float) $booking->commission_amount);
        // Traccia dell'omaggio per i controlli.
        $this->assertSame('Fam trip', $booking->metadata['complimentary']['reason'] ?? null);
    }

    public function test_l_omaggio_non_manda_al_cliente_email_di_pagamento(): void
    {
        Mail::fake();

        [$tour, $dep] = $this->makeTourWithDeparture();
        $this->actingAs($this->agency(true));

        $this->form($tour, $dep)
            ->set('complimentary', true)
            ->call('submit')
            ->assertHasNoErrors();

        // Un'email "paga 0 €" al cliente sarebbe assurda.
        Mail::assertNotSent(\App\Mail\BookingPaymentLink::class);
        Mail::assertNotSent(\App\Mail\BookingAwaitingTransfer::class);
    }

    public function test_i_posti_omaggio_occupano_comunque_la_barca(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $this->actingAs($this->agency(true));

        $this->form($tour, $dep)
            ->set('complimentary', true)
            ->call('submit')
            ->assertHasNoErrors();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();

        // Il posto esiste, vale 0 € e la prenotazione conta nelle disponibilità.
        $this->assertSame(1, $booking->seatRecords()->count());
        $this->assertSame(0.0, (float) $booking->seatRecords()->first()->price_paid);
        $this->assertTrue(Booking::active()->whereKey($booking->id)->exists());
    }

    public function test_senza_omaggio_il_flusso_b2b_resta_a_pagamento(): void
    {
        Mail::fake();

        [$tour, $dep] = $this->makeTourWithDeparture();
        $this->actingAs($this->agency(true));

        // Permesso attivo ma opzione NON selezionata: nulla deve cambiare.
        $this->form($tour, $dep)->call('submit')->assertHasNoErrors();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();

        $this->assertGreaterThan(0, (float) $booking->total_amount);
        $this->assertNotSame(BookingStatus::CONFIRMED, $booking->status);
    }

    public function test_admin_puo_abilitare_il_permesso_dalle_impostazioni_agenzia(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $agency = $this->agency(false);

        $this->actingAs($admin)
            ->put('/admin/users/'.$agency->id, [
                'name' => $agency->name,
                'email' => $agency->email,
                'role' => 'b2b',
                'agency_name' => 'Agenzia Test',
                'commission_rate' => 20,
                'can_book_complimentary' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($agency->fresh()->canBookComplimentary());
    }

    public function test_uscendo_dal_ruolo_b2b_il_permesso_si_perde(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $agency = $this->agency(true);

        $this->actingAs($admin)
            ->put('/admin/users/'.$agency->id, [
                'name' => $agency->name,
                'email' => $agency->email,
                'role' => 'customer',
                'can_book_complimentary' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $agency->fresh()->can_book_complimentary);
    }
}
