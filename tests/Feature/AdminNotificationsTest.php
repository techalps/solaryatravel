<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\AdminNotification;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Centro notifiche dell'area admin.
 *
 * Il requisito che decide la struttura: letta/eliminata sono per SINGOLO
 * admin. Con un flag sulla notifica il primo che la apre la spegnerebbe per
 * tutti, quindi gli stati vivono in una tabella a parte.
 *
 * L'altro punto delicato e' "a meno che non l'abbia fatta io": chi provoca
 * l'evento non deve ricevere il toast.
 */
class AdminNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function service(): AdminNotificationService
    {
        return app(AdminNotificationService::class);
    }

    private function makeBooking(array $attributes = []): Booking
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

    // ===== Stati per-admin =====

    public function test_letta_da_un_admin_resta_non_letta_per_un_altro(): void
    {
        $mario = $this->admin();
        $anna = $this->admin();
        $n = $this->service()->notify('booking_created', 'Nuova prenotazione');

        $this->service()->markRead($n, $mario);

        $this->assertSame(0, $this->service()->unreadCount($mario));
        $this->assertSame(1, $this->service()->unreadCount($anna), 'Anna deve vederla ancora come nuova.');
    }

    public function test_eliminata_da_un_admin_resta_visibile_all_altro(): void
    {
        $mario = $this->admin();
        $anna = $this->admin();
        $n = $this->service()->notify('booking_created', 'Nuova prenotazione');

        $this->service()->delete($n, $mario);

        $this->assertCount(0, $this->service()->latestFor($mario));
        $this->assertCount(1, $this->service()->latestFor($anna));
    }

    public function test_eliminare_azzera_anche_il_contatore(): void
    {
        // Altrimenti una notifica eliminata continuerebbe a gonfiare il badge.
        $mario = $this->admin();
        $n = $this->service()->notify('booking_created', 'Nuova prenotazione');

        $this->service()->delete($n, $mario);

        $this->assertSame(0, $this->service()->unreadCount($mario));
    }

    public function test_segnare_letta_due_volte_non_rompe_nulla(): void
    {
        // L'indice unique su (notifica, admin) farebbe fallire un secondo
        // insert: il servizio usa updateOrCreate.
        $mario = $this->admin();
        $n = $this->service()->notify('booking_created', 'Nuova prenotazione');

        $this->service()->markRead($n, $mario);
        $this->service()->markRead($n, $mario);

        $this->assertSame(0, $this->service()->unreadCount($mario));
    }

    // ===== Chi ha causato l'evento =====

    public function test_chi_causa_l_evento_non_riceve_il_toast(): void
    {
        $mario = $this->admin();
        $anna = $this->admin();

        $this->service()->notify('booking_created', 'Nuova prenotazione', causedBy: $mario);

        $this->assertCount(0, $this->service()->pendingToasts($mario), 'L\'ha creata Mario: non deve avvisarlo.');
        $this->assertCount(1, $this->service()->pendingToasts($anna));
    }

    public function test_i_toast_ignorano_gli_eventi_vecchi(): void
    {
        // Riaprendo l'admin dopo giorni non deve comparire una pila di toast:
        // gli eventi vecchi restano nell'elenco.
        $mario = $this->admin();
        $n = $this->service()->notify('booking_created', 'Vecchia');
        $n->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->assertCount(0, $this->service()->pendingToasts($mario));
        $this->assertCount(1, $this->service()->latestFor($mario));
    }

    public function test_solo_i_tipi_marcati_toast_compaiono_a_schermo(): void
    {
        $mario = $this->admin();
        $this->service()->notify('payment_expiring', 'Scadenza vicina');  // toast: false

        $this->assertCount(0, $this->service()->pendingToasts($mario));
        $this->assertSame(1, $this->service()->unreadCount($mario), 'Deve comunque contare nel badge.');
    }

    // ===== Eventi generati dalle prenotazioni =====

    public function test_una_nuova_prenotazione_genera_la_notifica(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();

        $n = AdminNotification::where('type', 'booking_created')
            ->where('booking_id', $booking->id)->first();

        $this->assertNotNull($n);
        $this->assertStringContainsString($booking->booking_number, $n->title);
    }

    public function test_il_pagamento_ricevuto_genera_la_notifica(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();

        $booking->update(['status' => BookingStatus::CONFIRMED]);

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'payment_received',
            'booking_id' => $booking->id,
        ]);
    }

    public function test_l_annullamento_genera_la_notifica(): void
    {
        Mail::fake();
        $booking = $this->makeBooking(['status' => BookingStatus::CONFIRMED]);

        $booking->update(['status' => BookingStatus::CANCELLED]);

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'booking_cancelled',
            'booking_id' => $booking->id,
        ]);
    }

    // ===== Interfaccia e permessi =====

    public function test_il_feed_restituisce_contatore_ed_elenco(): void
    {
        $mario = $this->admin();
        $this->service()->notify('booking_created', 'Nuova prenotazione', 'Mario Rossi · Giro');

        $this->actingAs($mario)
            ->getJson(route('admin.notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('items.0.title', 'Nuova prenotazione')
            ->assertJsonPath('items.0.read', false);
    }

    public function test_la_pagina_notifiche_si_apre(): void
    {
        $mario = $this->admin();
        $this->service()->notify('booking_created', 'Nuova prenotazione');

        $this->actingAs($mario)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Nuova prenotazione')
            ->assertSee('valgono solo per te');
    }

    public function test_la_campanella_e_nell_header(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('notifBadge', false)
            ->assertSee('notifToasts', false);
    }

    public function test_lo_skipper_non_riceve_notifiche_gestionali(): void
    {
        // Nell'area admin vede solo l'imbarco: avvisarlo di incassi e rimborsi
        // non ha senso.
        $this->admin('skipper');

        $this->assertNotContains('skipper', $this->service()->recipients()->pluck('role')->all());
    }

    public function test_un_cliente_non_accede_alle_notifiche(): void
    {
        $cliente = User::factory()->create(['role' => 'customer']);

        // Il middleware admin nega l'accesso (403), non redirige.
        $this->actingAs($cliente)
            ->get(route('admin.notifications.index'))
            ->assertForbidden();
    }

    // ===== Notifiche da condizione (comando schedulato) =====

    public function test_il_comando_segnala_le_prenotazioni_in_scadenza(): void
    {
        Mail::fake();
        $booking = $this->makeBooking([
            'status' => BookingStatus::PENDING,
            'payment_deadline' => now()->addHours(2),
        ]);

        $this->artisan('admin:scan-notifications')->assertSuccessful();

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'payment_expiring',
            'booking_id' => $booking->id,
        ]);
    }

    public function test_il_comando_non_duplica_le_segnalazioni(): void
    {
        // Il cron su OVH gira ogni ora: senza il controllo di esistenza
        // l'admin troverebbe la stessa segnalazione ripetuta a ogni giro.
        Mail::fake();
        $this->makeBooking([
            'status' => BookingStatus::PENDING,
            'payment_deadline' => now()->addHours(2),
        ]);

        $this->artisan('admin:scan-notifications');
        $this->artisan('admin:scan-notifications');

        $this->assertSame(1, AdminNotification::where('type', 'payment_expiring')->count());
    }

    public function test_il_comando_ignora_le_scadenze_lontane(): void
    {
        Mail::fake();
        $this->makeBooking([
            'status' => BookingStatus::PENDING,
            'payment_deadline' => now()->addDays(3),
        ]);

        $this->artisan('admin:scan-notifications');

        $this->assertSame(0, AdminNotification::where('type', 'payment_expiring')->count());
    }
}
