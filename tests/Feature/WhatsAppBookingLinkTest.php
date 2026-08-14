<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Pulsanti "click to chat" WhatsApp sulle prenotazioni.
 *
 * Due direzioni distinte:
 *  - in admin l'operatore scrive al CLIENTE (numero della prenotazione);
 *  - nelle email/pagina di conferma il cliente scrive a SOLARYA (numero dalle
 *    impostazioni), nella lingua in cui ha prenotato.
 *
 * Il caso che conta di più è l'assenza del numero: il link non deve mai uscire
 * monco (un wa.me senza numero manda l'utente su una pagina di errore).
 */
class WhatsAppBookingLinkTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBooking(array $attributes = []): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 10, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro dell\'isola', 'slug' => 'giro-'.uniqid(),
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
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'customer_phone' => '345 088 4743',
            'customer_country' => 'IT',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
            'locale' => 'it',
        ], $attributes));
    }

    /** Imposta (o azzera) il numero WhatsApp aziendale nelle impostazioni. */
    private function setBusinessNumber(?string $number): void
    {
        Cache::put('app_settings', ['whatsapp_number' => $number], 3600);
    }

    public function test_link_admin_verso_il_cliente_contiene_numero_e_dati_prenotazione(): void
    {
        $booking = $this->makeBooking();

        $link = \App\Support\WhatsApp::adminLink($booking);

        $this->assertStringStartsWith('https://wa.me/393450884743?text=', $link);

        // Il messaggio deve arrivare già compilato: senza questi dati
        // l'operatore dovrebbe riscriverli a mano ogni volta.
        $text = rawurldecode(parse_url($link, PHP_URL_QUERY) ?? '');
        $this->assertStringContainsString($booking->booking_number, $text);
        $this->assertStringContainsString('Mario', $text);
        $this->assertStringContainsString('Giro dell\'isola', $text);
    }

    public function test_link_cliente_usa_il_numero_aziendale_non_quello_del_cliente(): void
    {
        $this->setBusinessNumber('+39 320 1234567');
        $booking = $this->makeBooking(['customer_phone' => '345 088 4743']);

        $link = \App\Support\WhatsApp::customerLink($booking);

        $this->assertStringStartsWith('https://wa.me/393201234567?text=', $link);
        $this->assertStringNotContainsString('393450884743', $link);
    }

    public function test_il_messaggio_al_cliente_segue_la_lingua_della_prenotazione(): void
    {
        $this->setBusinessNumber('+39 320 1234567');
        $booking = $this->makeBooking(['locale' => 'en']);

        $text = \App\Support\WhatsApp::customerMessage($booking);

        $this->assertStringContainsString('Hello', $text);
        $this->assertStringContainsString($booking->booking_number, $text);
    }

    public function test_senza_impostazione_si_usa_il_numero_storico_del_sito(): void
    {
        // Il numero è cablato in header, footer e pagine tour da prima di questa
        // funzione: il pulsante sulle prenotazioni deve comportarsi come quelli
        // invece di sparire, quindi l'impostazione vuota ricade sul fallback.
        $this->setBusinessNumber('');
        $booking = $this->makeBooking();

        $this->assertStringStartsWith(
            'https://wa.me/393450884743?text=',
            \App\Support\WhatsApp::customerLink($booking)
        );
    }

    public function test_l_impostazione_sovrascrive_il_numero_storico(): void
    {
        $this->setBusinessNumber('+39 320 1234567');
        $booking = $this->makeBooking();

        $link = \App\Support\WhatsApp::customerLink($booking);

        $this->assertStringStartsWith('https://wa.me/393201234567?text=', $link);
        $this->assertStringNotContainsString('393450884743', $link);
    }

    public function test_senza_telefono_del_cliente_il_link_admin_non_esiste(): void
    {
        $booking = $this->makeBooking(['customer_phone' => null]);

        $this->assertNull(\App\Support\WhatsApp::adminLink($booking));
    }

    public function test_la_scheda_admin_mostra_il_pulsante_whatsapp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->makeBooking();

        $this->actingAs($admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('https://wa.me/393450884743', false);
    }

    public function test_la_pagina_pubblica_della_prenotazione_mostra_il_pulsante(): void
    {
        // È la pagina che il cliente apre dal link ricevuto (/prenotazione/{uuid}):
        // il punto più naturale in cui cercare l'assistenza.
        // Numero diverso da quello fisso di header/footer, così l'asserzione
        // non può passare per merito di quelli.
        $this->setBusinessNumber('+39 320 1234567');
        $booking = $this->makeBooking();

        $this->get(route('booking.show', $booking->uuid))
            ->assertOk()
            ->assertSee('https://wa.me/393201234567?text=', false)
            ->assertSee($booking->booking_number);
    }

    public function test_la_pagina_pubblica_mostra_il_pulsante_anche_senza_impostazione(): void
    {
        // Con l'impostazione vuota si usa il numero storico del sito, quindi il
        // pulsante c'è comunque: si riconosce dal "?text=" col messaggio
        // precompilato, che i link fissi di header e footer non hanno.
        $this->setBusinessNumber('');
        $booking = $this->makeBooking();

        $this->get(route('booking.show', $booking->uuid))
            ->assertOk()
            ->assertSee('wa.me/393450884743?text=', false)
            ->assertSee($booking->booking_number);
    }

    public function test_la_scheda_admin_segnala_il_telefono_mancante_senza_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->makeBooking(['customer_phone' => null]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('https://wa.me/', false)
            ->assertSee(__('whatsapp.no_phone'));
    }
}
