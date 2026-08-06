<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * I link destinati al CLIENTE FINALE devono puntare al sito pubblico, anche
 * quando l'email parte dal portale agenzie.
 *
 * Caso reale: un'agenzia reinvia gli estremi di pagamento dal portale b2b. La
 * mail conteneva https://b2b.…/pagamento/{uuid}, ma le rotte del sito cliente
 * vivono in routes/web.php mentre sul sottodominio è caricato solo
 * routes/b2b.php (vedi bootstrap/app.php). Il cliente riceveva un 404 e non
 * poteva pagare.
 */
class PublicUrlFromB2bTest extends TestCase
{
    use DatabaseTransactions;

    private function booking(array $attributes = []): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat' . uniqid(), 'slug' => 'cat-' . uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro' . uniqid(), 'slug' => 'giro-' . uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario', 'customer_last_name' => 'Rossi',
            'customer_email' => uniqid() . '@example.com',
            'seats' => 2, 'base_price' => 300, 'total_amount' => 300,
            'amount_paid' => 0, 'status' => BookingStatus::PENDING,
            'payment_type' => 'full',
        ], $attributes));
    }

    /** Simula una richiesta proveniente dal sottodominio agenzie. */
    private function comeDaB2b(): void
    {
        $host = config('b2b.domain') ?: 'b2b.example.com';
        $request = \Illuminate\Http\Request::create('https://' . $host . '/prenotazioni', 'GET');
        $this->app->instance('request', $request);
        URL::setRequest($request);
    }

    public function test_route_normale_userebbe_l_host_b2b(): void
    {
        $this->comeDaB2b();

        // Fotografa il difetto: è proprio questo che finiva nella mail.
        $host = config('b2b.domain') ?: 'b2b.example.com';
        $this->assertStringContainsString($host, route('payment.show', 'abc'));
    }

    public function test_il_link_di_pagamento_punta_sempre_al_sito_pubblico(): void
    {
        $booking = $this->booking();
        $this->comeDaB2b();

        $url = (new \App\Mail\BookingPaymentLink($booking))->payUrl();

        // Si confronta l'HOST: lo schema può differire (la richiesta simulata
        // è https mentre APP_URL in locale è http).
        $this->assertSame(
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url($url, PHP_URL_HOST)
        );
        $this->assertStringNotContainsString((string) config('b2b.domain'), $url);
        $this->assertStringContainsString($booking->uuid, $url);
    }

    public function test_anche_il_link_del_saldo_punta_al_sito_pubblico(): void
    {
        // Acconto versato: payUrl() sceglie la pagina del saldo.
        $booking = $this->booking([
            'status' => BookingStatus::DEPOSIT_PAID,
            'payment_type' => 'deposit',
            'deposit_amount' => 150,
            'balance_amount' => 150,
            'amount_paid' => 150,
        ]);
        $this->comeDaB2b();

        $url = (new \App\Mail\BookingPaymentLink($booking))->payUrl();

        $this->assertStringContainsString('/saldo', $url, 'Deve puntare alla pagina del saldo.');
        $this->assertStringNotContainsString((string) config('b2b.domain'), $url);
    }

    public function test_l_helper_ripristina_l_host_dopo_l_uso(): void
    {
        $this->comeDaB2b();
        $host = config('b2b.domain') ?: 'b2b.example.com';

        public_site_route('payment.show', 'abc');

        // Una pagina b2b deve continuare a generare i propri link sul proprio
        // dominio: l'helper non deve avere effetti collaterali.
        $this->assertStringContainsString($host, route('payment.show', 'abc'));
    }

    public function test_dal_sito_pubblico_l_url_resta_invariato(): void
    {
        $booking = $this->booking();

        $url = (new \App\Mail\BookingPaymentLink($booking))->payUrl();

        // Si confronta l'HOST: lo schema può differire (la richiesta simulata
        // è https mentre APP_URL in locale è http).
        $this->assertSame(
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url($url, PHP_URL_HOST)
        );
    }
}
