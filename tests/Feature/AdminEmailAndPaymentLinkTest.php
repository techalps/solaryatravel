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
 * Due asimmetrie fra admin e portale agenzie, segnalate dall'uso reale.
 *
 * 1) L'email del cliente si poteva correggere SOLO dal portale agenzie: in
 *    admin era un dato in sola lettura. Chi gestisce tutto dall'admin non
 *    poteva riparare un indirizzo sbagliato.
 *
 * 2) Creando da admin col metodo "Link di pagamento (Stripe)" il link veniva
 *    generato e salvato ma NON inviato: il cliente non riceveva niente finche'
 *    l'admin non lo mandava a mano dal dettaglio. Dal portale agenzie invece
 *    l'email parte da sola. Ora l'invio e' il default anche in admin, con la
 *    scelta esplicita nel form.
 */
class AdminEmailAndPaymentLinkTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeTourWithDeparture(): array
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

        return [$tour, $dep];
    }

    private function makeBooking(BookingStatus $status = BookingStatus::CONFIRMED): Booking
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        return Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'sbagliata@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => $status,
            'payment_type' => 'full',
        ]);
    }

    // ===== 1. Email correggibile da admin =====

    public function test_l_admin_corregge_l_email_del_cliente(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin())
            ->patch(route('admin.bookings.update', $booking), [
                'status' => $booking->status->value,
                'customer_email' => 'giusta@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('giusta@example.com', $booking->fresh()->customer_email);
    }

    public function test_la_correzione_da_admin_resta_tracciata(): void
    {
        $admin = $this->admin();
        $booking = $this->makeBooking();

        $this->actingAs($admin)
            ->patch(route('admin.bookings.update', $booking), [
                'status' => $booking->status->value,
                'customer_email' => 'giusta@example.com',
            ]);

        $storico = $booking->fresh()->metadata['email_changes'] ?? [];
        $this->assertCount(1, $storico);
        $this->assertSame('sbagliata@example.com', $storico[0]['from']);
        $this->assertSame('admin', $storico[0]['by']);
        $this->assertSame($admin->getKey(), $storico[0]['user_id']);
    }

    public function test_un_email_non_valida_viene_rifiutata_anche_da_admin(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin())
            ->patch(route('admin.bookings.update', $booking), [
                'status' => $booking->status->value,
                'customer_email' => 'non-una-email',
            ])
            ->assertSessionHasErrors('customer_email');

        $this->assertSame('sbagliata@example.com', $booking->fresh()->customer_email);
    }

    public function test_non_passare_l_email_non_la_azzera(): void
    {
        // Altre parti del form fanno PATCH senza il campo email: il valore
        // esistente non deve perdersi.
        $booking = $this->makeBooking();

        $this->actingAs($this->admin())
            ->patch(route('admin.bookings.update', $booking), [
                'status' => $booking->status->value,
                'special_requests' => 'Nota di prova',
            ])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('sbagliata@example.com', $booking->customer_email);
        $this->assertSame('Nota di prova', $booking->special_requests);
    }

    public function test_il_form_di_modifica_contiene_il_campo_email(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin())
            ->get(route('admin.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('name="customer_email"', false);
    }

    // ===== 2. Link di pagamento inviato alla creazione =====

    private function createPayload(array $override = []): array
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        return array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => (string) $dep->id,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'cliente'.uniqid().'@example.com',
            // Documento obbligatorio per ogni passeggero (vedi PassengerDocumentTest).
            'adults' => [[
                'first_name' => 'Mario', 'last_name' => 'Rossi',
                'doc_type' => 'carta_identita', 'doc_number' => 'AB1234567',
            ]],
            'payment_method' => 'stripe',
        ], $override);
    }

    public function test_creando_con_stripe_l_email_col_link_parte(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->createPayload())
            ->assertRedirect();

        Mail::assertSent(\App\Mail\BookingPaymentLink::class);
    }

    public function test_si_puo_scegliere_di_non_inviare_il_link(): void
    {
        // Chi vuole mandarlo a mano (WhatsApp, telefono) toglie la spunta.
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->createPayload([
                'send_payment_link' => '0',
            ]))
            ->assertRedirect();

        Mail::assertNotSent(\App\Mail\BookingPaymentLink::class);
    }

    public function test_il_form_di_creazione_offre_la_scelta_sull_invio(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.bookings.create'))
            ->assertOk()
            ->assertSee('name="send_payment_link"', false);
    }
}
