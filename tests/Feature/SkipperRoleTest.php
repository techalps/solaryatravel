<?php

namespace Tests\Feature;

use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ruolo "skipper": accede all'area admin ma SOLO alla sezione Imbarco, per
 * scansionare i QR dei biglietti a bordo.
 *
 * Il gating è deny-by-default (SkipperAreaMiddleware): tutto ciò che non è
 * esplicitamente consentito resta vietato, così una rotta admin aggiunta in
 * futuro non diventa accessibile per dimenticanza.
 */
class SkipperRoleTest extends TestCase
{
    use DatabaseTransactions;

    private function skipper(): User
    {
        return User::factory()->create([
            'role' => 'skipper',
            'email_verified_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);
    }

    private function makeDeparture(): TourDeparture
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

        return TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);
    }

    public function test_lo_skipper_accede_alla_sezione_imbarco(): void
    {
        $this->actingAs($this->skipper())
            ->get(route('admin.boarding.index'))
            ->assertOk();
    }

    public function test_lo_skipper_accede_al_dettaglio_di_una_partenza(): void
    {
        $dep = $this->makeDeparture();

        $this->actingAs($this->skipper())
            ->get(route('admin.boarding.show', $dep))
            ->assertOk();
    }

    public function test_la_dashboard_rimanda_lo_skipper_all_imbarco(): void
    {
        // Aprendo /admin lo skipper non deve vedere un 403 secco: va portato
        // alla sua unica sezione.
        $this->actingAs($this->skipper())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.boarding.index'));
    }

    /**
     * Ogni sezione del gestionale che lo skipper NON deve raggiungere.
     */
    public static function sezioniVietate(): array
    {
        return [
            'prenotazioni' => ['admin.bookings.index'],
            'programma' => ['admin.schedule'],
            'assegnazione catamarani' => ['admin.assignments.index'],
            'tour' => ['admin.tours.index'],
            'flotta' => ['admin.catamarans.index'],
            'extra' => ['admin.addons.index'],
            'codici sconto' => ['admin.discounts.index'],
            'utenti' => ['admin.users.index'],
            'report' => ['admin.reports.index'],
            'pagamenti' => ['admin.payments.index'],
            'impostazioni' => ['admin.settings'],
            'guida' => ['admin.guide.index'],
        ];
    }

    #[DataProvider('sezioniVietate')]
    public function test_lo_skipper_non_accede_alle_altre_sezioni(string $routeName): void
    {
        $this->actingAs($this->skipper())
            ->get(route($routeName))
            ->assertRedirect(route('admin.boarding.index'));
    }

    public function test_le_azioni_non_get_sono_negate_con_403(): void
    {
        $admin = $this->admin();

        // Una POST fuori perimetro non va redirezionata (perderebbe il body):
        // deve essere negata esplicitamente.
        $this->actingAs($this->skipper())
            ->post(route('admin.users.store'), [])
            ->assertForbidden();

        // Lo stesso endpoint resta accessibile a un admin (qui fallisce la
        // validazione, non l'autorizzazione: 302 con errori, non 403).
        $this->actingAs($admin)
            ->post(route('admin.users.store'), [])
            ->assertStatus(302);
    }

    public function test_gli_altri_ruoli_admin_non_sono_limitati(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($this->admin())
            ->get(route('admin.bookings.index'))
            ->assertOk();
    }

    public function test_un_cliente_non_entra_nell_area_admin(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('admin.boarding.index'))
            ->assertForbidden();
    }

    public function test_dopo_il_login_lo_skipper_atterra_sull_imbarco(): void
    {
        $skipper = $this->skipper();

        $this->assertSame('admin.boarding.index', $skipper->homeRouteName());

        $this->post(route('login'), [
            'email' => $skipper->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.boarding.index'));
    }

    public function test_il_menu_admin_mostra_allo_skipper_solo_l_imbarco(): void
    {
        $html = $this->actingAs($this->skipper())
            ->get(route('admin.boarding.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.boarding.index'), $html);

        // Nessun link alle sezioni riservate nel menu.
        foreach (['admin.bookings.index', 'admin.tours.index', 'admin.settings', 'admin.users.index'] as $forbidden) {
            $this->assertStringNotContainsString(
                'href="'.route($forbidden).'"',
                $html,
                "Il menu non deve mostrare {$forbidden} a uno skipper"
            );
        }
    }

    public function test_lo_skipper_e_riconosciuto_come_ruolo_admin_ma_senza_pieni_poteri(): void
    {
        $skipper = $this->skipper();

        // Deve passare AdminMiddleware…
        $this->assertTrue($skipper->isAdmin());
        $this->assertTrue($skipper->isSkipper());
        // …ma non avere accesso pieno al gestionale.
        $this->assertFalse($skipper->hasFullAdminAccess());
        $this->assertFalse($skipper->hasSuperAdminPowers());
        $this->assertFalse($skipper->isSystemAdmin());
    }
}
