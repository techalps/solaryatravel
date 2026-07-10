<?php

namespace Tests\Feature;

use App\Livewire\Public\BookingForm;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Documento d'identità obbligatorio per OGNI passeggero (intestatario compreso).
 * Copre: validazione bloccante della scadenza (>= data del viaggio) e persistenza
 * dei campi documento sui booking_seats attraverso il flusso pubblico.
 */
class PassengerDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTourWithDeparture(): array
    {
        $cat = Catamaran::create(['name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(), 'capacity' => 10, 'is_active' => true]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(), 'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 100,
        ]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return [$tour, $dep];
    }

    /** Compila i campi documento validi dell'intestatario (adulto 0) sul componente. */
    private function fillValidBookerDocument($component, TourDeparture $dep): void
    {
        $expiry = $dep->departure_date->copy()->addYear()->toDateString();
        $component
            ->set('adults.0.doc_type', 'carta_identita')
            ->set('adults.0.doc_number', 'CA12345AB')
            ->set('adults.0.doc_expiry', $expiry)
            ->set('adults.0.doc_country', 'IT')
            ->set('adults.0.doc_province', 'TO')
            ->set('adults.0.doc_place', 'Torino');
    }

    private function baseFilledComponent(Tour $tour, TourDeparture $dep)
    {
        return Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])
            ->set('customer_first_name', 'Mario')
            ->set('customer_last_name', 'Rossi')
            ->set('customer_email', 'mario'.uniqid().'@example.com')
            ->set('customer_tax_code', 'RSSMRA80A01H501U')
            ->set('terms', true);
    }

    public function test_documento_scaduto_prima_del_viaggio_blocca(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $component = $this->baseFilledComponent($tour, $dep);
        // Documento scaduto il giorno PRIMA del viaggio.
        $expired = $dep->departure_date->copy()->subDay()->toDateString();
        $component
            ->set('adults.0.doc_type', 'carta_identita')
            ->set('adults.0.doc_number', 'CA12345AB')
            ->set('adults.0.doc_expiry', $expired)
            ->set('adults.0.doc_country', 'IT')
            ->set('adults.0.doc_province', 'TO')
            ->set('adults.0.doc_place', 'Torino')
            ->call('submit')
            ->assertHasErrors(['adults.0.doc_expiry']);

        $this->assertSame(0, Booking::where('tour_id', $tour->id)->count());
    }

    public function test_documento_mancante_blocca(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $this->baseFilledComponent($tour, $dep)
            ->call('submit')
            ->assertHasErrors(['adults.0.doc_type', 'adults.0.doc_number', 'adults.0.doc_expiry', 'adults.0.doc_place']);

        $this->assertSame(0, Booking::where('tour_id', $tour->id)->count());
    }

    public function test_provincia_obbligatoria_se_italia(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $expiry = $dep->departure_date->copy()->addYear()->toDateString();

        $this->baseFilledComponent($tour, $dep)
            ->set('adults.0.doc_type', 'passaporto')
            ->set('adults.0.doc_number', 'YA1234567')
            ->set('adults.0.doc_expiry', $expiry)
            ->set('adults.0.doc_country', 'IT')
            ->set('adults.0.doc_province', '')   // manca la provincia
            ->set('adults.0.doc_place', 'Torino')
            ->call('submit')
            ->assertHasErrors(['adults.0.doc_province']);
    }

    public function test_documento_valido_viene_salvato_sul_seat(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $component = $this->baseFilledComponent($tour, $dep);
        $this->fillValidBookerDocument($component, $dep);
        $component->call('submit')->assertHasNoErrors();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $this->assertNotNull($booking, 'La prenotazione deve essere creata.');

        $seat = $booking->seatRecords()->where('is_primary', true)->first();
        $this->assertNotNull($seat);
        $this->assertSame('carta_identita', $seat->doc_type);
        $this->assertSame('CA12345AB', $seat->doc_number);
        $this->assertSame('IT', $seat->doc_issue_country);
        $this->assertSame('TO', $seat->doc_issue_province);
        $this->assertSame('Torino', $seat->doc_issue_place);
        $this->assertTrue($seat->hasDocument());
        // Scadenza salvata come data.
        $this->assertEquals(
            $dep->departure_date->copy()->addYear()->toDateString(),
            $seat->doc_expiry->toDateString()
        );
    }

    public function test_estero_senza_provincia_e_ammesso(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $expiry = $dep->departure_date->copy()->addYear()->toDateString();

        $component = $this->baseFilledComponent($tour, $dep)
            ->set('adults.0.doc_type', 'passaporto')
            ->set('adults.0.doc_number', 'FR9988776')
            ->set('adults.0.doc_expiry', $expiry)
            ->set('adults.0.doc_country', 'FR')
            ->set('adults.0.doc_place', 'Paris');

        $component->call('submit')->assertHasNoErrors();

        $seat = Booking::where('tour_id', $tour->id)->latest('id')->first()
            ->seatRecords()->where('is_primary', true)->first();
        $this->assertSame('FR', $seat->doc_issue_country);
        $this->assertNull($seat->doc_issue_province);
        $this->assertSame('Paris', $seat->doc_issue_place);
    }

    // ===== Admin =====

    private function adminBookingPayload(Tour $tour, TourDeparture $dep, array $overrides = []): array
    {
        return array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => (string) $dep->id,
            'adults' => [[
                'first_name' => 'Mario', 'last_name' => 'Rossi',
                'doc_type' => 'carta_identita', 'doc_number' => 'CA12345AB',
                'doc_expiry' => $dep->departure_date->copy()->addYear()->toDateString(),
                'doc_country' => 'IT', 'doc_province' => 'TO', 'doc_place' => 'Torino',
            ]],
            'customer_first_name' => 'Mario', 'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'customer_tax_code' => 'RSSMRA80A01H501U',
            'status' => 'confirmed',
        ], $overrides);
    }

    public function test_admin_store_richiede_il_documento(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $payload = $this->adminBookingPayload($tour, $dep);
        // Rimuovi i campi documento dall'adulto.
        unset($payload['adults'][0]['doc_type'], $payload['adults'][0]['doc_number'],
            $payload['adults'][0]['doc_expiry'], $payload['adults'][0]['doc_place']);

        $this->actingAs($admin)
            ->post('/admin/bookings', $payload)
            ->assertSessionHasErrors(['adults.0.doc_type', 'adults.0.doc_number', 'adults.0.doc_expiry', 'adults.0.doc_place']);

        $this->assertSame(0, Booking::where('tour_id', $tour->id)->count());
    }

    public function test_admin_store_rifiuta_documento_scaduto(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $payload = $this->adminBookingPayload($tour, $dep);
        $payload['adults'][0]['doc_expiry'] = $dep->departure_date->copy()->subDay()->toDateString();

        $this->actingAs($admin)
            ->post('/admin/bookings', $payload)
            ->assertSessionHasErrors(['adults.0.doc_expiry']);
    }

    public function test_admin_store_salva_il_documento(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post('/admin/bookings', $this->adminBookingPayload($tour, $dep))
            ->assertSessionHasNoErrors();

        $seat = Booking::where('tour_id', $tour->id)->latest('id')->first()
            ->seatRecords()->where('is_primary', true)->first();
        $this->assertSame('carta_identita', $seat->doc_type);
        $this->assertSame('TO', $seat->doc_issue_province);
        $this->assertSame('Torino', $seat->doc_issue_place);
        $this->assertTrue($seat->hasDocument());
    }

    public function test_admin_puo_modificare_il_documento_di_un_passeggero(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Crea una prenotazione con documento iniziale.
        $this->actingAs($admin)->post('/admin/bookings', $this->adminBookingPayload($tour, $dep));
        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $seat = $booking->seatRecords()->where('is_primary', true)->first();

        // Modifica: passaporto emesso all'estero.
        $this->actingAs($admin)
            ->post(route('admin.bookings.seats.document', [$booking, $seat]), [
                'doc_type' => 'passaporto',
                'doc_number' => 'YB7654321',
                'doc_expiry' => $dep->departure_date->copy()->addYears(2)->toDateString(),
                'doc_country' => 'FR',
                'doc_place' => 'Nice',
            ])
            ->assertSessionHasNoErrors();

        $seat->refresh();
        $this->assertSame('passaporto', $seat->doc_type);
        $this->assertSame('YB7654321', $seat->doc_number);
        $this->assertSame('FR', $seat->doc_issue_country);
        $this->assertNull($seat->doc_issue_province);
        $this->assertSame('Nice', $seat->doc_issue_place);
    }

    public function test_admin_edit_rifiuta_documento_scaduto(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post('/admin/bookings', $this->adminBookingPayload($tour, $dep));
        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $seat = $booking->seatRecords()->where('is_primary', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.bookings.seats.document', [$booking, $seat]), [
                'doc_type' => 'carta_identita',
                'doc_number' => 'CA0000001',
                'doc_expiry' => $dep->departure_date->copy()->subDay()->toDateString(),
                'doc_country' => 'IT',
                'doc_province' => 'MI',
                'doc_place' => 'Milano',
            ])
            ->assertSessionHasErrors(['doc_expiry']);
    }

    public function test_helper_documenti_mancanti_e_filtro_admin(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Prenotazione completa (con documento).
        $this->actingAs($admin)->post('/admin/bookings', $this->adminBookingPayload($tour, $dep));
        $complete = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $this->assertFalse($complete->fresh()->load('seatRecords')->hasMissingDocuments());

        // Prenotazione a cui svuotiamo il documento del passeggero (simula storica).
        $this->actingAs($admin)->post('/admin/bookings', $this->adminBookingPayload($tour, $dep, [
            'customer_email' => 'senza'.uniqid().'@example.com',
        ]));
        $incomplete = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $incomplete->seatRecords()->update([
            'doc_type' => null, 'doc_number' => null, 'doc_expiry' => null,
            'doc_issue_country' => null, 'doc_issue_place' => null,
        ]);
        $this->assertTrue($incomplete->fresh()->load('seatRecords')->hasMissingDocuments());

        // Il filtro admin mostra solo quella incompleta (futura).
        $res = $this->actingAs($admin)->get('/admin/bookings?missing_docs=1');
        $res->assertOk();
        $res->assertSee('#' . $incomplete->booking_number);
        $res->assertDontSee('#' . $complete->booking_number);
    }

    /**
     * Regressione: la pagina di dettaglio admin deve renderizzare senza errori di
     * parsing Blade (il redirect post-creazione ci passa: un ParseError qui dava
     * 500 pur avendo già salvato la prenotazione).
     */
    public function test_dettaglio_admin_si_renderizza_dopo_creazione(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Il POST di creazione redirige su admin.bookings.show: deve dare 302, non 500.
        $this->actingAs($admin)
            ->post('/admin/bookings', $this->adminBookingPayload($tour, $dep))
            ->assertRedirect();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();

        // Render diretto della pagina di dettaglio (con documento presente).
        $this->actingAs($admin)
            ->get('/admin/bookings/' . $booking->id)
            ->assertOk()
            ->assertSee('doc-edit-fields-');
    }
}
