<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Cambiare lo stato a mano dalla pagina di Modifica deve liberare la barca
 * esattamente come fanno i pulsanti Annulla e Rimborsa.
 *
 * Caso reale SLY-2026-00216: prenotazione a uso esclusivo su 3 catamarani per
 * il 17/09, stornata portando lo stato a "Rimborsata" dalla Modifica. Le
 * riserve sono sopravvissute e i catamarani sono rimasti invendibili su quella
 * data, pur comparendo come liberi nella lista: un blocco orfano.
 */
class StatusChangeReleasesTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: Booking, 1: Catamaran} */
    private function scenario(): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat' . uniqid(), 'slug' => 'cat-' . uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Esclusiva' . uniqid(), 'slug' => 'escl-' . uniqid(),
            'is_active' => true, 'booking_on_request' => true,
        ]);
        $tour->catamarans()->attach($cat->id);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(30)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Anna', 'customer_last_name' => 'Bianchi',
            'customer_email' => uniqid() . '@example.com',
            'seats' => 2, 'base_price' => 6000, 'total_amount' => 6000,
            'amount_paid' => 6000, 'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ]);

        foreach ([1, 2] as $n) {
            $booking->seatRecords()->create([
                'seat_number' => $n, 'catamaran_id' => $cat->id,
                'guest_first_name' => 'Ospite', 'guest_last_name' => (string) $n,
                'price_paid' => $n === 1 ? 6000 : 0, 'is_primary' => $n === 1,
            ]);
        }

        // La riserva di uso esclusivo, come la crea il flusso admin. Il
        // booking_number è assegnato alla creazione: va riletto dal DB.
        $booking->refresh();

        TourCatamaranBlock::create([
            'tour_id' => $tour->id,
            'catamaran_id' => $cat->id,
            'start_date' => $dep->departure_date,
            'end_date' => $dep->departure_date,
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'reason' => 'Riservato da prenotazione admin #' . $booking->booking_number,
        ]);

        return [$booking, $cat];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function blocchiDi(Booking $booking): int
    {
        return TourCatamaranBlock::where('reason', 'like', '%#' . $booking->booking_number . '%')->count();
    }

    public function test_portare_a_rimborsata_libera_riserve_e_posti(): void
    {
        [$booking] = $this->scenario();
        $this->assertSame(1, $this->blocchiDi($booking), 'La riserva deve esistere in partenza.');

        $this->actingAs($this->admin())
            ->put(route('admin.bookings.update', $booking), [
                'status' => BookingStatus::REFUNDED->value,
            ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::REFUNDED, $booking->status);
        $this->assertSame(0, $this->blocchiDi($booking), 'La riserva sul catamarano va rilasciata.');
        $this->assertSame(0, $booking->seatRecords()->whereNull('cancelled_at')->count(),
            'I posti non devono restare attivi.');
        $this->assertNotNull($booking->cancelled_at);
    }

    public function test_portare_ad_annullata_libera_riserve_e_posti(): void
    {
        [$booking] = $this->scenario();

        $this->actingAs($this->admin())
            ->put(route('admin.bookings.update', $booking), [
                'status' => BookingStatus::CANCELLED->value,
            ])->assertSessionHasNoErrors()->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::CANCELLED, $booking->status, 'Lo stato deve essere cambiato.');
        $this->assertSame(0, $this->blocchiDi($booking));
        $this->assertSame(0, $booking->seatRecords()->whereNull('cancelled_at')->count());
    }

    public function test_uno_stato_normale_non_libera_nulla(): void
    {
        [$booking] = $this->scenario();

        $this->actingAs($this->admin())
            ->put(route('admin.bookings.update', $booking), [
                'status' => BookingStatus::CHECKED_IN->value,
            ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(1, $this->blocchiDi($booking), 'La riserva deve restare.');
        $this->assertSame(2, $booking->seatRecords()->whereNull('cancelled_at')->count());
        $this->assertNull($booking->cancelled_at);
    }

    public function test_risalvare_una_gia_annullata_non_ripete_la_liberazione(): void
    {
        [$booking] = $this->scenario();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => BookingStatus::CANCELLED->value,
        ]);
        $booking->refresh();
        $primaData = $booking->cancelled_at;

        // Secondo salvataggio: la data di annullamento non deve spostarsi.
        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => BookingStatus::CANCELLED->value,
            'special_requests' => 'nota aggiunta dopo',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertEquals($primaData, $booking->cancelled_at, 'La data di annullamento non va riscritta.');
    }
}
