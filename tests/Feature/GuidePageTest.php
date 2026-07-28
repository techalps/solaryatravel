<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La guida operativa in-app deve restare allineata alle modifiche: questo test
 * verifica che il capitolo sulla versione inglese del sito si renderizzi.
 */
class GuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guida_ruolo_skipper_si_renderizza(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'ruolo-skipper'))
            ->assertOk()
            ->assertSee('A cosa serve il ruolo Skipper', false)
            ->assertSee('Skipper (solo imbarco)', false);
    }

    public function test_guida_sito_inglese_si_renderizza(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'sito-inglese'))
            ->assertOk()
            ->assertSee('Il sito è bilingue', false)
            ->assertSee('i18n:missing', false);
    }
}
