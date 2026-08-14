<?php

namespace Tests\Feature;

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
 * Completamento delle prenotazioni dall'elenco: in blocco e per singola riga.
 *
 * È l'unico cambio di stato disponibile dall'elenco. Regola: solo da
 * "Confermata" e solo a partenza avvenuta. Il check-in NON è un requisito —
 * a bordo spesso non viene registrato, e senza questo salto le prenotazioni
 * resterebbero "confermate" a tempo indeterminato.
 */
class BulkStatusChangeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  int  $daysOffset Giorni rispetto a oggi: negativo = partenza passata.
     */
    private function makeBooking(BookingStatus $status, int $daysOffset = -10): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);

        $date = now()->addDays($daysOffset)->toDateString();

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => $date,
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => $status,
            'payment_type' => 'full',
        ]);
    }

    // ===== In blocco =====

    public function test_completa_in_blocco_le_confermate_passate(): void
    {
        $a = $this->makeBooking(BookingStatus::CONFIRMED);
        $b = $this->makeBooking(BookingStatus::CONFIRMED);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), ['booking_ids' => [$a->id, $b->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(BookingStatus::COMPLETED, $a->fresh()->status);
        $this->assertSame(BookingStatus::COMPLETED, $b->fresh()->status);
        $this->assertNotNull($a->fresh()->completed_at);
    }

    public function test_non_serve_il_check_in(): void
    {
        // Il punto della richiesta: si salta confirmed -> checked_in -> completed.
        $booking = $this->makeBooking(BookingStatus::CONFIRMED);
        $this->assertNull($booking->checked_in_at);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), ['booking_ids' => [$booking->id]])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::COMPLETED, $booking->status);
        // Non si inventa un imbarco che non è avvenuto.
        $this->assertNull($booking->checked_in_at);
    }

    public function test_salta_le_prenotazioni_non_confermate(): void
    {
        $confermata = $this->makeBooking(BookingStatus::CONFIRMED);
        $annullata = $this->makeBooking(BookingStatus::CANCELLED);
        $inAttesa = $this->makeBooking(BookingStatus::PENDING);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), [
                'booking_ids' => [$confermata->id, $annullata->id, $inAttesa->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $this->assertSame(BookingStatus::COMPLETED, $confermata->fresh()->status);
        $this->assertSame(BookingStatus::CANCELLED, $annullata->fresh()->status);
        $this->assertSame(BookingStatus::PENDING, $inAttesa->fresh()->status);
    }

    public function test_salta_le_prenotazioni_con_partenza_futura(): void
    {
        $passata = $this->makeBooking(BookingStatus::CONFIRMED, -5);
        $futura = $this->makeBooking(BookingStatus::CONFIRMED, 5);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), ['booking_ids' => [$passata->id, $futura->id]])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(BookingStatus::COMPLETED, $passata->fresh()->status);
        $this->assertSame(BookingStatus::CONFIRMED, $futura->fresh()->status);
    }

    public function test_completare_non_invia_email_al_cliente(): void
    {
        // I biglietti partono solo sul passaggio a CONFIRMED: qui nessuna email.
        Mail::fake();
        $booking = $this->makeBooking(BookingStatus::CONFIRMED);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), ['booking_ids' => [$booking->id]])
            ->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_una_gia_completata_non_e_un_errore(): void
    {
        $booking = $this->makeBooking(BookingStatus::COMPLETED);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), ['booking_ids' => [$booking->id]])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(BookingStatus::COMPLETED, $booking->fresh()->status);
    }

    public function test_richiede_almeno_una_prenotazione(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.bookings.bulk-complete'), [])
            ->assertSessionHasErrors('booking_ids');
    }

    public function test_un_utente_non_admin_non_puo_completare(): void
    {
        $booking = $this->makeBooking(BookingStatus::CONFIRMED);
        $cliente = User::factory()->create(['role' => 'customer']);

        $this->actingAs($cliente)
            ->post(route('admin.bookings.bulk-complete'), ['booking_ids' => [$booking->id]])
            ->assertForbidden();

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    // ===== Singola riga =====

    public function test_completa_una_singola_prenotazione(): void
    {
        $booking = $this->makeBooking(BookingStatus::CONFIRMED);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.complete', $booking))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(BookingStatus::COMPLETED, $booking->fresh()->status);
    }

    public function test_la_singola_rifiuta_una_partenza_futura(): void
    {
        // Il pulsante non comparirebbe, ma la richiesta può arrivare da una
        // pagina rimasta aperta: la condizione va riverificata lato server.
        $booking = $this->makeBooking(BookingStatus::CONFIRMED, 5);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.complete', $booking))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_la_singola_rifiuta_una_non_confermata(): void
    {
        $booking = $this->makeBooking(BookingStatus::PENDING);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.complete', $booking))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(BookingStatus::PENDING, $booking->fresh()->status);
    }

    // ===== Interfaccia =====

    public function test_l_icona_compare_solo_sulle_completabili(): void
    {
        $completabile = $this->makeBooking(BookingStatus::CONFIRMED, -5);

        $this->actingAs($this->admin())
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee(route('admin.bookings.complete', $completabile), false)
            ->assertSee('name="booking_ids[]"', false);
    }

    public function test_i_form_della_pagina_non_sono_annidati(): void
    {
        // L'HTML non ammette form annidati: il browser li spezza silenziosamente.
        // Avvolgere la tabella nel form del bulk rompeva SIA le checkbox (inviate
        // a vuoto: "0 prenotazioni") SIA i pulsanti di riga (click senza effetto).
        // Le checkbox si agganciano con l'attributo form=, non per annidamento.
        $this->makeBooking(BookingStatus::CONFIRMED, -5);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->getContent();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $annidati = 0;
        foreach ($dom->getElementsByTagName('form') as $form) {
            for ($p = $form->parentNode; $p && $p->nodeName !== 'body'; $p = $p->parentNode) {
                if ($p->nodeName === 'form') {
                    $annidati++;
                    break;
                }
            }
        }

        $this->assertSame(0, $annidati, 'Ci sono form annidati: selezione e azioni di riga non funzionerebbero.');
    }

    public function test_le_caselle_sono_agganciate_al_form_del_completamento(): void
    {
        $this->makeBooking(BookingStatus::CONFIRMED, -5);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->getContent();

        preg_match_all('#name="booking_ids\[\]"[^>]*#', $html, $m);

        $this->assertNotEmpty($m[0], 'Nessuna casella di selezione nella pagina.');
        foreach ($m[0] as $checkbox) {
            $this->assertStringContainsString('form="bulkStatusForm"', $checkbox);
        }
    }

    public function test_l_icona_non_compare_su_una_partenza_futura(): void
    {
        $futura = $this->makeBooking(BookingStatus::CONFIRMED, 5);

        $this->actingAs($this->admin())
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertDontSee(route('admin.bookings.complete', $futura), false);
    }

    public function test_un_tour_di_oggi_gia_concluso_e_completabile(): void
    {
        // Partenza 10:00-13:00 di oggi: a giornata in corso ma tour finito, la
        // prenotazione è completabile senza aspettare mezzanotte.
        $booking = $this->makeBooking(BookingStatus::CONFIRMED, 0);
        $booking->departure->update(['start_time' => '00:01:00', 'end_time' => '00:02:00']);

        $this->assertTrue($booking->fresh()->canBeCompleted());
    }

    public function test_un_tour_di_oggi_ancora_da_svolgere_non_e_completabile(): void
    {
        $booking = $this->makeBooking(BookingStatus::CONFIRMED, 0);
        $booking->departure->update(['start_time' => '23:58:00', 'end_time' => '23:59:00']);

        $this->assertFalse($booking->fresh()->canBeCompleted());
    }
}
