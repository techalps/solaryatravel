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
 *
 * Del passeggero si chiedono SOLO quattro campi: nome, cognome, tipo di documento
 * e numero. Si scegle la tipologia e si compila il numero.
 *
 * NB: data di scadenza, codice fiscale e luogo di rilascio (stato/provincia/
 * comune) NON sono più richiesti in NESSUN form — sito, agenzie, widget e admin
 * compresi: troppi campi in fase di prenotazione. Le colonne restano a database
 * per non perdere lo storico, ma non vengono più scritte né validate.
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
    private function fillValidBookerDocument($component): void
    {
        $component
            ->set('adults.0.doc_type', 'carta_identita')
            ->set('adults.0.doc_number', 'CA12345AB');
    }

    private function baseFilledComponent(Tour $tour, TourDeparture $dep)
    {
        return Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])
            ->set('customer_first_name', 'Mario')
            ->set('customer_last_name', 'Rossi')
            ->set('customer_email', 'mario'.uniqid().'@example.com')
            ->set('terms', true);
    }

    public function test_bastano_tipo_e_numero_per_prenotare(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        // Nessun luogo di rilascio, nessuna scadenza, nessun codice fiscale.
        $component = $this->baseFilledComponent($tour, $dep);
        $this->fillValidBookerDocument($component);
        $component->call('submit')->assertHasNoErrors();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $this->assertNotNull($booking, 'La prenotazione deve andare a buon fine con soli tipo e numero.');

        $seat = $booking->seatRecords()->where('is_primary', true)->first();
        $this->assertSame('carta_identita', $seat->doc_type);
        $this->assertSame('CA12345AB', $seat->doc_number);

        // Il documento è considerato completo, così la prenotazione non finisce
        // nel filtro admin "documenti mancanti".
        $this->assertTrue($seat->hasDocument());
    }

    public function test_i_campi_rimossi_non_vengono_piu_salvati(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $component = $this->baseFilledComponent($tour, $dep);
        $this->fillValidBookerDocument($component);
        $component->call('submit')->assertHasNoErrors();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $seat = $booking->seatRecords()->where('is_primary', true)->first();

        $this->assertNull($seat->doc_expiry, 'La scadenza non si chiede più: resta null.');
        $this->assertNull($seat->doc_issue_country, 'Il luogo di rilascio non si chiede più: resta null.');
        $this->assertNull($seat->doc_issue_province);
        $this->assertNull($seat->doc_issue_place);
        $this->assertNull($booking->customer_tax_code, 'Senza CF il campo resta null, non stringa vuota.');
    }

    public function test_documento_mancante_blocca(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $this->baseFilledComponent($tour, $dep)
            ->call('submit')
            ->assertHasErrors(['adults.0.doc_type', 'adults.0.doc_number'])
            // I campi rimossi non devono più comparire fra gli errori.
            ->assertHasNoErrors([
                'adults.0.doc_expiry',
                'adults.0.doc_country',
                'adults.0.doc_province',
                'adults.0.doc_place',
            ]);

        $this->assertSame(0, Booking::where('tour_id', $tour->id)->count());
    }

    public function test_il_tipo_documento_deve_essere_fra_quelli_previsti(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $this->baseFilledComponent($tour, $dep)
            ->set('adults.0.doc_type', 'tessera_sanitaria')  // non ammesso
            ->set('adults.0.doc_number', 'XX1234567')
            ->call('submit')
            ->assertHasErrors(['adults.0.doc_type']);
    }

    public function test_il_form_pubblico_non_mostra_piu_i_campi_rimossi(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $html = Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])->html();

        // Tipo e numero ci sono...
        $this->assertStringContainsString('adults.0.doc_type', $html);
        $this->assertStringContainsString('adults.0.doc_number', $html);
        // ...il resto no.
        $this->assertStringNotContainsString('doc_country', $html);
        $this->assertStringNotContainsString('doc_province', $html);
        $this->assertStringNotContainsString('doc_place', $html);
        $this->assertStringNotContainsString('doc_expiry', $html);
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
            ]],
            'customer_first_name' => 'Mario', 'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'status' => 'confirmed',
        ], $overrides);
    }

    public function test_admin_store_richiede_il_documento(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $payload = $this->adminBookingPayload($tour, $dep);
        unset($payload['adults'][0]['doc_type'], $payload['adults'][0]['doc_number']);

        $this->actingAs($admin)
            ->post('/admin/bookings', $payload)
            ->assertSessionHasErrors(['adults.0.doc_type', 'adults.0.doc_number']);

        $this->assertSame(0, Booking::where('tour_id', $tour->id)->count());
    }

    public function test_admin_store_non_pretende_piu_scadenza_e_luogo(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Payload con SOLI tipo e numero: prima veniva rifiutato perché scadenza
        // e luogo di rilascio erano obbligatori anche in admin.
        $this->actingAs($admin)
            ->post('/admin/bookings', $this->adminBookingPayload($tour, $dep))
            ->assertSessionHasNoErrors();

        $seat = Booking::where('tour_id', $tour->id)->latest('id')->first()
            ->seatRecords()->where('is_primary', true)->first();
        $this->assertSame('carta_identita', $seat->doc_type);
        $this->assertSame('CA12345AB', $seat->doc_number);
        $this->assertTrue($seat->hasDocument());
    }

    public function test_admin_puo_modificare_il_documento_di_un_passeggero(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->post('/admin/bookings', $this->adminBookingPayload($tour, $dep));
        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $seat = $booking->seatRecords()->where('is_primary', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.bookings.seats.document', [$booking, $seat]), [
                'doc_type' => 'passaporto',
                'doc_number' => 'YB7654321',
            ])
            ->assertSessionHasNoErrors();

        $seat->refresh();
        $this->assertSame('passaporto', $seat->doc_type);
        $this->assertSame('YB7654321', $seat->doc_number);
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
        $incomplete->seatRecords()->update(['doc_type' => null, 'doc_number' => null]);
        $this->assertTrue($incomplete->fresh()->load('seatRecords')->hasMissingDocuments());

        // Il filtro admin mostra solo quella incompleta (futura).
        $res = $this->actingAs($admin)->get('/admin/bookings?missing_docs=1');
        $res->assertOk();
        $res->assertSee('#' . $incomplete->booking_number);
        $res->assertDontSee('#' . $complete->booking_number);
    }

    /**
     * Un documento storico privo di luogo di rilascio (dato vecchio) non deve
     * risultare incompleto solo per quello: conta solo tipo + numero.
     */
    public function test_lo_storico_senza_luogo_di_rilascio_resta_completo(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->post('/admin/bookings', $this->adminBookingPayload($tour, $dep));
        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();

        $this->assertFalse($booking->fresh()->load('seatRecords')->hasMissingDocuments());
    }

    /**
     * Regressione: termini e privacy devono essere consultabili dal form anche
     * in modalità b2b/widget.
     *
     * Prima erano link al dominio principale, perché sull'host b2b le rotte
     * legali non esistono (404). Ora sono modali con il testo incluso nella
     * pagina: il problema del dominio non si pone più, ma il contenuto deve
     * comunque esserci — su b2b e widget come sul sito.
     */
    public function test_documenti_legali_consultabili_dal_form_in_b2b(): void
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        $html = Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep, 'b2bMode' => true])->html();

        $this->assertStringContainsString('data-legal="terms"', $html);
        $this->assertStringContainsString('data-legal="privacy"', $html);
        $this->assertStringContainsString('Titolare del Servizio', $html);
        $this->assertStringContainsString('Titolare del Trattamento', $html);

        // Nessun link verso rotte che sull'host b2b darebbero 404.
        $this->assertStringNotContainsString(config('b2b.domain') . '/termini-condizioni', $html);
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
