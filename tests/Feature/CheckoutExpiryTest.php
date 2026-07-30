<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\Settings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Scadenza della "sessione di prenotazione" (il carrello che blocca i posti).
 *
 * Due difetti risolti qui:
 *
 * 1) FUSO ORARIO. config/app.php forzava 'UTC' ignorando APP_TIMEZONE=Europe/Rome:
 *    now() era ora UTC, veniva salvata così com'era e stampata come se fosse ora
 *    locale. In CEST una scadenza a +30 minuti nasceva 1h30 nel PASSATO (segnalato
 *    dal vivo: scadenza mostrata 14:23 con orologio a 15:54).
 *
 * 2) DURATA E AVVIO. La scadenza partiva alla creazione della prenotazione, così i
 *    minuti utili venivano consumati compilando il form; e il job di pulizia
 *    guardava solo i bonifici, quindi un carrello carta abbandonato teneva i posti
 *    bloccati per sempre. Ora il conto parte all'APERTURA DEL CHECKOUT e la
 *    scadenza è verificata sia in lettura sia dal job.
 */
class CheckoutExpiryTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBooking(array $attributes = []): Booking
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

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => BookingStatus::PENDING,
            'payment_type' => 'full',
        ], $attributes));
    }

    public function test_app_e_database_condividono_il_fuso_orario_locale(): void
    {
        // Il bug della scadenza "già passata" nasceva proprio da qui: PHP su UTC
        // e MySQL su ora locale, con i timestamp scritti senza conversione.
        $this->assertSame('Europe/Rome', config('app.timezone'));
        $this->assertSame('Europe/Rome', date_default_timezone_get());

        $phpNow = now()->format('Y-m-d H:i');
        $dbNow = substr((string) DB::selectOne('SELECT NOW() as n')->n, 0, 16);

        $this->assertSame($phpNow, $dbNow, 'PHP e MySQL devono riportare la stessa ora locale.');
    }

    public function test_la_scadenza_mostrata_non_e_mai_nel_passato_appena_aperta(): void
    {
        $booking = $this->makeBooking();

        $booking->startCheckoutWindow();

        // Il sintomo esatto segnalato: aperta ora, scadenza già passata.
        $this->assertTrue(
            $booking->payment_deadline->isFuture(),
            'Appena aperto il checkout la scadenza deve essere nel futuro.'
        );
        $this->assertFalse($booking->checkoutWindowExpired());
    }

    public function test_il_carrello_nasce_senza_scadenza_e_parte_dal_checkout(): void
    {
        $booking = $this->makeBooking();

        // Compilare il form non consuma tempo utile.
        $this->assertNull($booking->payment_deadline);
        $this->assertFalse($booking->checkoutWindowExpired());

        $booking->startCheckoutWindow();

        $expected = now()->addMinutes(Settings::paymentDeadlineMinutes());
        $this->assertNotNull($booking->payment_deadline);
        $this->assertLessThanOrEqual(5, abs($expected->diffInSeconds($booking->payment_deadline)));
    }

    public function test_la_scadenza_dura_i_minuti_configurati(): void
    {
        // Settings legge da questa cache (stessa convenzione di DepositRulesTest).
        Cache::put('app_settings', ['payment_deadline_minutes' => 15], 60);

        $booking = $this->makeBooking();
        $booking->startCheckoutWindow();

        $this->assertSame(15, (int) round(now()->diffInMinutes($booking->payment_deadline, false)));
    }

    public function test_riaprire_il_checkout_non_rinnova_la_scadenza(): void
    {
        $booking = $this->makeBooking();
        $booking->startCheckoutWindow();
        $first = $booking->payment_deadline->copy();

        // Senza questo controllo, un refresh terrebbe i posti bloccati all'infinito.
        $booking->startCheckoutWindow();

        $this->assertTrue($first->equalTo($booking->fresh()->payment_deadline));
    }

    public function test_aprire_la_pagina_di_pagamento_avvia_il_conto(): void
    {
        $booking = $this->makeBooking();

        $this->get(route('payment.show', $booking->uuid))->assertOk();

        $this->assertNotNull($booking->fresh()->payment_deadline);
    }

    public function test_il_carrello_scaduto_si_svuota_alla_lettura(): void
    {
        // Scaduto e mai raccolto dallo scheduler: la sola apertura della pagina
        // deve annullarlo, così il limite vale anche a cron fermo.
        $booking = $this->makeBooking(['payment_deadline' => now()->subMinute()]);

        $this->assertTrue($booking->checkoutWindowExpired());

        $this->get(route('payment.show', $booking->uuid))
            ->assertRedirect(route('tours.index'));

        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
    }

    public function test_un_carrello_scaduto_non_puo_avviare_il_pagamento(): void
    {
        $booking = $this->makeBooking(['payment_deadline' => now()->subMinute()]);

        // La pagina può essere rimasta aperta a lungo prima del click.
        $this->post(route('payment.process', $booking->uuid))
            ->assertRedirect(route('tours.index'));

        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
    }

    public function test_il_job_annulla_i_carrelli_carta_scaduti_e_libera_i_posti(): void
    {
        // Prima il job guardava solo awaiting_transfer: i carrelli carta
        // abbandonati restavano pending per sempre, con i posti bloccati.
        $booking = $this->makeBooking(['payment_deadline' => now()->subMinutes(2)]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
    }

    public function test_il_job_non_tocca_i_carrelli_ancora_validi(): void
    {
        $booking = $this->makeBooking(['payment_deadline' => now()->addMinutes(10)]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $this->assertSame(BookingStatus::PENDING, $booking->fresh()->status);
    }

    public function test_il_job_non_tocca_i_carrelli_senza_checkout_aperto(): void
    {
        // Nessuna scadenza = checkout mai aperto: non deve essere annullato.
        $booking = $this->makeBooking(['payment_deadline' => null]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $this->assertSame(BookingStatus::PENDING, $booking->fresh()->status);
    }

    public function test_una_prenotazione_confermata_non_scade(): void
    {
        // Il pagamento vince sulla scadenza: se i soldi sono arrivati, un
        // payment_deadline passato non deve annullare nulla.
        $booking = $this->makeBooking([
            'status' => BookingStatus::CONFIRMED,
            'payment_deadline' => now()->subHour(),
        ]);

        $this->assertFalse($booking->checkoutWindowExpired());

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }
}
