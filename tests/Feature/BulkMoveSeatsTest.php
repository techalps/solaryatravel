<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Spostamento in blocco dei passeggeri fra catamarani (pagina Assegnazione).
 *
 * Caso operativo: "sposta tutti (o alcuni) i passeggeri di questa barca su
 * un'altra" — guasto, cambio scafo, riorganizzazione dei gruppi.
 *
 * Requisito chiave: si deve sapere PRIMA se il gruppo entra nella barca di
 * destinazione. Lato server vale il tutto-o-niente: se non ci stanno tutti non
 * si sposta nessuno, altrimenti il gruppo resterebbe diviso a metà.
 */
class BulkMoveSeatsTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    }

    /**
     * @param  array<int, int>  $capacities
     * @return array{tour: Tour, dep: TourDeparture, boats: Collection<int, Catamaran>}
     */
    private function scenario(array $capacities = [10, 10]): array
    {
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 4,
        ]);

        $boats = collect($capacities)->map(fn ($cap, $i) => Catamaran::create([
            'name' => 'Barca'.$i.'-'.uniqid(),
            'slug' => 'barca-'.$i.'-'.uniqid(),
            'capacity' => $cap,
            'is_active' => true,
        ]));

        $tour->catamarans()->attach($boats->pluck('id')->all());

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '14:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return ['tour' => $tour, 'dep' => $dep, 'boats' => $boats];
    }

    /** @return Collection<int, BookingSeat> */
    private function seatsOn(Tour $tour, TourDeparture $dep, Catamaran $boat, int $count)
    {
        $booking = Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'seats' => $count,
            'base_price' => 100,
            'total_amount' => 100 * $count,
            'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ]);

        return collect(range(1, $count))->map(fn ($n) => BookingSeat::create([
            'booking_id' => $booking->id,
            'seat_number' => $n,
            'catamaran_id' => $boat->id,
            'is_primary' => $n === 1,
            'price_paid' => 100,
            'qr_code' => strtoupper(uniqid('QR')),
        ]));
    }

    public function test_sposta_tutti_i_passeggeri_su_un_altra_barca(): void
    {
        $s = $this->scenario([10, 10]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 4);

        $moved = app(BookingService::class)->moveSeatsBulk($seats, $to->id);

        $this->assertSame(4, $moved);
        $this->assertSame(0, BookingSeat::whereIn('id', $seats->pluck('id'))->where('catamaran_id', $from->id)->count());
        $this->assertSame(4, BookingSeat::whereIn('id', $seats->pluck('id'))->where('catamaran_id', $to->id)->count());
    }

    public function test_sposta_solo_alcuni_passeggeri(): void
    {
        $s = $this->scenario([10, 10]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 5);

        // Solo i primi 2.
        $moved = app(BookingService::class)->moveSeatsBulk($seats->take(2), $to->id);

        $this->assertSame(2, $moved);
        $this->assertSame(3, BookingSeat::where('catamaran_id', $from->id)->count());
        $this->assertSame(2, BookingSeat::where('catamaran_id', $to->id)->count());
    }

    public function test_se_non_ci_stanno_tutti_non_si_sposta_nessuno(): void
    {
        // Destinazione da 4 posti, di cui 2 già occupati → 2 liberi.
        $s = $this->scenario([10, 4]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $this->seatsOn($s['tour'], $s['dep'], $to, 2);
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 3);

        try {
            app(BookingService::class)->moveSeatsBulk($seats, $to->id);
            $this->fail('Doveva rifiutare: 3 passeggeri per 2 posti liberi.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('non ci stanno tutti', $e->getMessage());
        }

        // Tutto-o-niente: nessuno spostato, il gruppo non resta diviso a metà.
        $this->assertSame(3, BookingSeat::whereIn('id', $seats->pluck('id'))->where('catamaran_id', $from->id)->count());
        $this->assertSame(2, BookingSeat::where('catamaran_id', $to->id)->count());
    }

    public function test_riempire_esattamente_la_destinazione_e_ammesso(): void
    {
        // 3 liberi, 3 da spostare: entra esatto.
        $s = $this->scenario([10, 5]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $this->seatsOn($s['tour'], $s['dep'], $to, 2);
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 3);

        $this->assertSame(3, app(BookingService::class)->moveSeatsBulk($seats, $to->id));
        $this->assertSame(5, BookingSeat::where('catamaran_id', $to->id)->count());
    }

    public function test_non_si_sposta_su_una_barca_in_uso_esclusivo_altrui(): void
    {
        $s = $this->scenario([10, 10]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 2);

        TourCatamaranBlock::create([
            'tour_id' => $s['tour']->id,
            'catamaran_id' => $to->id,
            'start_date' => $s['dep']->departure_date,
            'end_date' => $s['dep']->departure_date,
            'start_time' => '10:00',
            'end_time' => '14:00',
            'reason' => 'Riservato da prenotazione admin #SLY-ALTRA',
        ]);

        $this->expectExceptionMessageMatches('/uso esclusivo/');
        app(BookingService::class)->moveSeatsBulk($seats, $to->id);
    }

    public function test_spostare_su_dove_sono_gia_non_fa_nulla(): void
    {
        $s = $this->scenario([10, 10]);
        $from = $s['boats'][0];
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 3);

        $this->assertSame(0, app(BookingService::class)->moveSeatsBulk($seats, $from->id));
    }

    public function test_endpoint_admin_sposta_i_selezionati(): void
    {
        $s = $this->scenario([10, 10]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 3);

        $this->actingAs($this->admin())
            ->post(route('admin.assignments.move-bulk', $s['dep']), [
                'seat_ids' => $seats->pluck('id')->all(),
                'target_catamaran_id' => $to->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(3, BookingSeat::where('catamaran_id', $to->id)->count());
    }

    public function test_endpoint_segnala_l_errore_se_non_ci_stanno(): void
    {
        $s = $this->scenario([10, 3]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $this->seatsOn($s['tour'], $s['dep'], $to, 2);
        $seats = $this->seatsOn($s['tour'], $s['dep'], $from, 2);

        $this->actingAs($this->admin())
            ->post(route('admin.assignments.move-bulk', $s['dep']), [
                'seat_ids' => $seats->pluck('id')->all(),
                'target_catamaran_id' => $to->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(2, BookingSeat::whereIn('id', $seats->pluck('id'))->where('catamaran_id', $from->id)->count());
    }

    /**
     * Difesa contro id manomessi: un posto di un'ALTRA partenza non deve essere
     * spostato anche se il suo id viene inviato nel form.
     */
    public function test_ignora_i_posti_di_un_altra_partenza(): void
    {
        $s = $this->scenario([10, 10]);
        [$from, $to] = [$s['boats'][0], $s['boats'][1]];
        $mine = $this->seatsOn($s['tour'], $s['dep'], $from, 1);

        $other = $this->scenario([10, 10]);
        $foreign = $this->seatsOn($other['tour'], $other['dep'], $other['boats'][0], 1);

        $this->actingAs($this->admin())
            ->post(route('admin.assignments.move-bulk', $s['dep']), [
                'seat_ids' => $mine->pluck('id')->concat($foreign->pluck('id'))->all(),
                'target_catamaran_id' => $to->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Il posto estraneo non si è mosso.
        $this->assertSame(
            (int) $other['boats'][0]->id,
            (int) $foreign->first()->fresh()->catamaran_id
        );
    }

    public function test_serve_almeno_un_passeggero_selezionato(): void
    {
        $s = $this->scenario([10, 10]);

        $this->actingAs($this->admin())
            ->post(route('admin.assignments.move-bulk', $s['dep']), [
                'seat_ids' => [],
                'target_catamaran_id' => $s['boats'][1]->id,
            ])
            ->assertSessionHasErrors('seat_ids');
    }

    public function test_la_pagina_mostra_le_caselle_e_la_barra_di_spostamento(): void
    {
        $s = $this->scenario([10, 10]);
        $this->seatsOn($s['tour'], $s['dep'], $s['boats'][0], 2);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.assignments.show', $s['dep']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-seat-check', $html);
        $this->assertStringContainsString('data-bulk-move', $html);
        $this->assertStringContainsString('data-bulk-target', $html);
        // Il select destinazione deve esporre i posti liberi, per il controllo live.
        $this->assertStringContainsString('data-free=', $html);
        $this->assertStringContainsString('posti liberi', $html);
    }
}
