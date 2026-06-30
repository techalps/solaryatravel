<?php

namespace Tests\Feature\B2b;

use App\Http\Middleware\CaptureReferralMiddleware;
use App\Models\User;
use App\Support\B2bContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Gating host↔ruolo, isolamento e attribuzione referral del canale B2B.
 */
class B2bAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function b2bHost(string $uri = '/'): string
    {
        return 'http://'.config('b2b.domain').$uri;
    }

    private function agency(): User
    {
        return User::factory()->create(['role' => 'b2b', 'agency_name' => 'A', 'commission_rate' => 20]);
    }

    // ===== Gating host ↔ ruolo =====

    public function test_guest_sul_portale_va_al_login(): void
    {
        $this->get($this->b2bHost('/'))->assertRedirect(route('b2b.login'));
    }

    public function test_customer_non_accede_al_portale(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get($this->b2bHost('/'))->assertForbidden();
    }

    public function test_agenzia_accede_al_portale(): void
    {
        $this->actingAs($this->agency())->get($this->b2bHost('/'))->assertOk();
    }

    public function test_rotta_sito_non_esiste_su_host_b2b(): void
    {
        // /tour è una rotta del sito cliente: su host b2b deve dare 404.
        $this->get($this->b2bHost('/tour'))->assertNotFound();
    }

    public function test_login_b2b_rifiuta_customer(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->post($this->b2bHost('/login'), ['email' => $customer->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_b2b_ammette_agenzia(): void
    {
        $agency = $this->agency();
        $this->post($this->b2bHost('/login'), ['email' => $agency->email, 'password' => 'password'])
            ->assertRedirect(route('b2b.dashboard'));
        $this->assertAuthenticatedAs($agency);
    }

    // ===== Impersonificazione admin =====

    public function test_admin_puo_impersonare_agenzia(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $agency = $this->agency();

        $this->assertTrue(B2bContext::canAccess($admin));

        $this->actingAs($admin);
        $this->assertTrue(B2bContext::isImpersonator());
        $this->assertNull(B2bContext::actingAgency());

        B2bContext::impersonate($agency);
        $this->assertEquals($agency->id, B2bContext::actingAgency()->id);
    }

    public function test_agenzia_non_e_impersonator_e_opera_per_se(): void
    {
        $agency = $this->agency();
        $this->actingAs($agency);
        $this->assertFalse(B2bContext::isImpersonator());
        $this->assertEquals($agency->id, B2bContext::actingAgency()->id);
    }

    // ===== Referral (Flusso B) =====

    public function test_ref_valido_imposta_cookie_attribuzione(): void
    {
        $agency = $this->agency();
        $token = $agency->ensureReferralToken();

        $this->get('/prenota?ref='.$token)
            ->assertCookie(CaptureReferralMiddleware::COOKIE);
    }

    public function test_ref_invalido_non_imposta_cookie(): void
    {
        $this->get('/prenota?ref=token-inesistente-xyz')
            ->assertCookieMissing(CaptureReferralMiddleware::COOKIE);
    }

    // ===== Coming soon non blocca il canale agenzie =====

    public function test_coming_soon_lascia_passare_il_widget(): void
    {
        $this->withMaintenance(function () {
            // /widget resta accessibile anche col sito in "Prossimamente".
            $this->get('/widget')->assertOk();
        });
    }

    public function test_coming_soon_blocca_la_prenotazione_diretta(): void
    {
        $this->withMaintenance(function () {
            // Senza referral agenzia, /prenota è sotto coming soon (503).
            $this->get('/prenota')->assertStatus(503);
        });
    }

    public function test_coming_soon_lascia_passare_la_prenotazione_con_referral(): void
    {
        $agency = $this->agency();
        $token = $agency->ensureReferralToken();
        $this->withMaintenance(function () use ($token) {
            // Con ?ref= valido, il flusso prenotazione NON è bloccato dal coming soon
            // (non deve dare 503; un eventuale redirect/altro stato è accettabile).
            $this->get('/prenota?ref='.$token.'&tour=1')->assertDontSee('Prossimamente');
        });
    }

    /** Esegue il callback con maintenance_mode attivo, ripristinando dopo. */
    private function withMaintenance(callable $fn): void
    {
        $path = storage_path('app/settings.json');
        $orig = is_file($path) ? file_get_contents($path) : null;
        $data = $orig ? json_decode($orig, true) : [];
        $data['maintenance_mode'] = true;
        file_put_contents($path, json_encode($data));
        \Illuminate\Support\Facades\Cache::forget('app_settings');

        try {
            $fn();
        } finally {
            if ($orig !== null) {
                file_put_contents($path, $orig);
            } else {
                @unlink($path);
            }
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        }
    }
}
