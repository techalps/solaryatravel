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

    /**
     * Ogni capitolo deve renderizzarsi: un errore Blade in una pagina della
     * guida passerebbe altrimenti inosservato fino alla segnalazione in uso.
     */
    public function test_tutti_i_capitoli_si_renderizzano(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $capitoli = collect(glob(resource_path('views/admin/guide/pages/*.blade.php')))
            ->map(fn ($p) => basename($p, '.blade.php'));

        $this->assertGreaterThan(5, $capitoli->count(), 'Capitoli non trovati: percorso cambiato?');

        foreach ($capitoli as $slug) {
            $this->actingAs($admin)
                ->get(route('admin.guide.show', $slug))
                ->assertOk();
        }
    }

    /**
     * Le funzioni che toccano il denaro devono restare documentate: sono quelle
     * dove un fraintendimento costa soldi veri (doppio incasso, storni, sconti).
     */
    public function test_la_guida_copre_incassi_storni_e_sconti(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'pagamenti-stati'))
            ->assertOk()
            // Dove si registra un bonifico e cosa succede premendo due volte.
            ->assertSee('Registrare l\'incasso di un bonifico', false)
            ->assertSee('Se premi due volte', false);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'prenotazioni'))
            ->assertOk()
            ->assertSee('Applicare uno sconto', false)
            ->assertSee('Cambiare il prezzo', false)
            // Nessuna email automatica: se l'admin lo ignora, il cliente
            // riceve un addebito senza spiegazioni.
            ->assertSee('non viene avvisato dal sistema', false);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'report'))
            ->assertOk()
            ->assertSee('per data di incasso', false)
            ->assertSee('Come si sistema', false);
    }

    public function test_la_guida_copre_completamento_e_contatto_whatsapp(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'prenotazioni'))
            ->assertOk()
            // Completamento dall'elenco: le due modalità e i due vincoli che
            // spiegano perché l'icona a volte non compare.
            ->assertSee('Segnare le prenotazioni come completate', false)
            ->assertSee('Non serve il check-in', false)
            ->assertSee('partenza già', false)
            // Contatto WhatsApp: cosa fare quando il pulsante non c'è.
            ->assertSee('Scrivere al cliente su WhatsApp', false)
            ->assertSee('Nessun numero di telefono in prenotazione', false)
            ->assertSee('Numero non valido per WhatsApp', false);

        $this->actingAs($admin)
            ->get(route('admin.guide.show', 'impostazioni'))
            ->assertOk()
            // Il numero è unico per tutto il sito: è il punto che evita di
            // cercarlo nel codice al prossimo cambio.
            ->assertSee('Numero WhatsApp', false)
            ->assertSee('si aggiorna da solo', false);
    }
}
