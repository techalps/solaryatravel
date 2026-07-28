<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Frontend bilingue IT/EN: routing, switcher, SEO e dizionario dei contenuti.
 *
 * Nota sull'Accept-Language: il client di test di Symfony invia per default
 * "en-us,en;q=0.5". Dove il test verifica il comportamento italiano lo header
 * viene forzato a it-IT, altrimenti scatterebbe il rilevamento automatico.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /** Header di un browser italiano. */
    protected function itHeaders(): array
    {
        return ['Accept-Language' => 'it-IT,it;q=0.9'];
    }

    protected function makeTour(array $attributes = []): Tour
    {
        return Tour::create(array_merge([
            'name' => 'Solarya Daily Escape',
            'slug' => 'solarya-daily-escape',
            'description_short' => 'Il tramonto più bello della Costa',
            'duration_hours' => 8,
            'departure_point' => 'Cala Dei Sardi',
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    public function test_home_italiana_senza_prefisso(): void
    {
        $this->withHeaders($this->itHeaders())
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="it"', false)
            ->assertSee('Escursioni in Catamarano', false);
    }

    public function test_home_inglese_sotto_prefisso_en(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Catamaran Excursions', false)
            ->assertDontSee('Escursioni in Catamarano', false);
    }

    public function test_le_pagine_pubbliche_esistono_nelle_due_lingue(): void
    {
        $this->makeTour();

        // /prenota richiede il tour in query (senza, rimanda al listing: è la
        // logica applicativa preesistente, non un effetto della lingua).
        $paths = [
            '/tour',
            '/tour/solarya-daily-escape',
            '/prenota?tour=solarya-daily-escape',
            '/privacy-policy',
            '/termini-condizioni',
            '/cookie-policy',
        ];

        foreach ($paths as $path) {
            // flushSession() fra le due lingue: visitare /en salva la
            // preferenza EN e la URL italiana verrebbe (correttamente)
            // reindirizzata. Qui verifichiamo che ogni pagina RISPONDA nelle
            // due lingue, non la persistenza — coperta da un test dedicato.
            $this->flushSession();
            $this->withHeaders($this->itHeaders())->get($path)->assertOk();

            $this->flushSession();
            $this->get('/en'.$path)->assertOk();
        }
    }

    public function test_lo_slug_del_tour_e_identico_nelle_due_lingue(): void
    {
        $this->makeTour();

        $this->withHeaders($this->itHeaders())->get('/tour/solarya-daily-escape')->assertOk();
        $this->get('/en/tour/solarya-daily-escape')->assertOk();
    }

    public function test_hreflang_reciproci_sul_dettaglio_tour(): void
    {
        $this->makeTour();

        $it = $this->withHeaders($this->itHeaders())->get('/tour/solarya-daily-escape');
        $en = $this->get('/en/tour/solarya-daily-escape');

        foreach ([$it, $en] as $response) {
            $response->assertOk()
                ->assertSee('hreflang="it" href="'.url('/tour/solarya-daily-escape').'"', false)
                ->assertSee('hreflang="en" href="'.url('/en/tour/solarya-daily-escape').'"', false)
                // x-default punta all'inglese: target primario turisti stranieri.
                ->assertSee('hreflang="x-default" href="'.url('/en/tour/solarya-daily-escape').'"', false);
        }

        // Canonical = versione della lingua corrente.
        $it->assertSee('rel="canonical" href="'.url('/tour/solarya-daily-escape').'"', false);
        $en->assertSee('rel="canonical" href="'.url('/en/tour/solarya-daily-escape').'"', false);
    }

    public function test_lo_switcher_mostra_bandiera_e_sigla_per_ogni_lingua(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        // Bandiere come SVG inline, non emoji: su Windows le emoji bandiera
        // non hanno glifo e apparirebbero come due lettere in un riquadro.
        $this->assertStringContainsString('tg-lang__flag', $html);
        $this->assertStringNotContainsString('🇬🇧', $html);
        $this->assertStringNotContainsString('🇮🇹', $html);

        // Sigla sempre presente accanto alla bandiera: la bandiera è un paese,
        // la sigla è la lingua (per l'inglese non esiste bandiera "giusta").
        $this->assertStringContainsString('>IT</span>', $html);
        $this->assertStringContainsString('>EN</span>', $html);

        // Nome completo esposto agli screen reader.
        $this->assertStringContainsString('Italiano', $html);
        $this->assertStringContainsString('English', $html);
    }

    public function test_lo_switcher_marca_la_lingua_attiva_e_non_la_linka(): void
    {
        // Su /en la voce EN è lo stato corrente: non deve essere un link.
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('aria-current="true"', $html);
        $this->assertMatchesRegularExpression(
            '/<span class="tg-lang__item is-active"[^>]*>(?:(?!<\/span>).)*?>EN</s',
            $html,
            'La lingua attiva (EN) deve essere uno <span>, non un link'
        );

        // …e IT deve invece essere un link con hreflang corretto.
        $this->assertStringContainsString('hreflang="it"', $html);
    }

    public function test_lo_switcher_resta_sulla_stessa_pagina(): void
    {
        $this->makeTour();

        $this->get('/lingua/en?route=tours.show&params[slug]=solarya-daily-escape')
            ->assertRedirect(url('/en/tour/solarya-daily-escape'));

        $this->get('/lingua/it?route=tours.show&params[slug]=solarya-daily-escape')
            ->assertRedirect(url('/tour/solarya-daily-escape'));
    }

    public function test_lo_switcher_preserva_i_filtri_di_ricerca(): void
    {
        $this->get('/lingua/en?route=tours.index&q[adults]=3&q[date]=2026-06-15')
            ->assertRedirect(url('/en/tour').'?adults=3&date=2026-06-15');
    }

    public function test_la_preferenza_di_lingua_sopravvive_alla_navigazione(): void
    {
        $this->makeTour();

        $this->get('/lingua/en')->assertRedirect(url('/en'));

        // Con la preferenza EN in sessione, una URL senza prefisso rimanda alla
        // versione prefissata: una sola URL per lingua, nessun duplicato.
        $this->get('/tour')->assertRedirect(url('/en/tour'));
        $this->get('/en/tour')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_la_scelta_manuale_vince_sul_browser(): void
    {
        // Browser inglese, ma l'utente ha scelto l'italiano.
        $this->get('/lingua/it');

        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="it"', false);
    }

    public function test_url_vince_sulla_sessione(): void
    {
        $this->get('/lingua/it');

        $this->get('/en')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_accept_language_rileva_inglese_solo_sulla_home(): void
    {
        $this->makeTour();

        // Sulla home: rileva e rimanda a /en.
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/')
            ->assertRedirect(url('/en'));

        // Su un deep link: la URL vince, nessun rilevamento. Una pagina
        // condivisa o indicizzata rende sempre la lingua del suo prefisso.
        $this->flushSession();
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/tour/solarya-daily-escape')
            ->assertOk()
            ->assertSee('<html lang="it"', false);
    }

    public function test_lingua_non_supportata_ricade_su_italiano(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="it"', false);
    }

    public function test_prefisso_it_redirige_301_alla_url_senza_prefisso(): void
    {
        $this->get('/it/tour')->assertRedirect('/tour')->assertStatus(301);
        $this->get('/it')->assertRedirect('/')->assertStatus(301);
    }

    public function test_le_route_fuori_perimetro_non_sono_localizzate(): void
    {
        // Pagamenti, area utente, admin restano solo in italiano: /en/... non esiste.
        $this->get('/en/accedi')->assertNotFound();
        $this->get('/en/admin')->assertNotFound();
    }

    public function test_il_footer_mostra_il_credito_powered_by(): void
    {
        foreach (['/', '/en'] as $path) {
            $html = $this->withHeaders($this->itHeaders())->get($path)->assertOk()->getContent();

            $this->assertStringContainsString('tg-footer-credit', $html);
            $this->assertStringContainsString('powered by', $html);
            // Nome e URL vengono da config/site.php, non hardcodati nella Blade.
            $this->assertStringContainsString(config('site.vendor.name'), $html);
            $this->assertStringContainsString('href="'.config('site.vendor.url').'"', $html);
            // Link esterno: sempre con rel noopener.
            $this->assertStringContainsString('rel="noopener noreferrer"', $html);

            // Il logo è presente come <img> con alt e dimensioni intrinseche
            // (le dimensioni evitano il layout shift durante il caricamento).
            $this->assertStringContainsString(config('site.vendor.logo'), $html);
            $this->assertStringContainsString('alt="'.config('site.vendor.name').'"', $html);
            $this->assertStringContainsString('width="'.config('site.vendor.logo_width').'"', $html);
        }
    }

    public function test_il_credito_ricade_sul_testo_se_il_logo_manca(): void
    {
        config(['site.vendor.logo' => 'images/logo-che-non-esiste.png']);

        $html = $this->withHeaders($this->itHeaders())->get('/')->assertOk()->getContent();

        // Nessun <img> rotto: si mostra il nome testuale.
        $this->assertStringNotContainsString('logo-che-non-esiste.png', $html);
        $this->assertStringContainsString('powered by', $html);
        $this->assertStringContainsString('>TechAlps</a>', $html);
    }

    public function test_il_logo_del_vendor_esiste_su_disco(): void
    {
        // Se qualcuno rinomina o rimuove il file, il footer degrada al testo
        // senza errori: questo test però lo segnala subito.
        $this->assertFileExists(public_path(config('site.vendor.logo')));
    }

    public function test_il_credito_si_puo_nascondere_da_config(): void
    {
        config(['site.vendor.name' => '']);

        $html = $this->withHeaders($this->itHeaders())->get('/')->assertOk()->getContent();

        // Il CSS del layout contiene sia i selettori .tg-footer-credit* sia la
        // stringa "powered by" nei commenti: va servito comunque, quindi lo
        // togliamo prima di verificare che il credito non sia RENDERIZZATO.
        $body = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);

        $this->assertStringNotContainsString('tg-footer-credit', $body);
        $this->assertStringNotContainsString('powered by', $body);
        $this->assertStringNotContainsString('href="'.config('site.vendor.url').'"', $body);
    }

    public function test_le_pagine_fuori_perimetro_restano_in_italiano(): void
    {
        // Preferenza EN salvata in sessione…
        $this->get('/lingua/en');

        // …ma widget e login esistono solo in italiano: non devono ereditarla,
        // altrimenti avremmo <html lang="en"> su testo interamente italiano.
        $this->get('/widget')->assertOk()->assertSee('<html lang="it"', false);
        $this->get('/accedi')->assertOk()->assertSee('<html lang="it"', false);
    }

    public function test_le_richieste_livewire_conservano_la_lingua(): void
    {
        // Le richieste Livewire girano tutte su /livewire/update: se non
        // fossero trattate come "dentro perimetro", il form di prenotazione
        // inglese tornerebbe in italiano al primo aggiornamento.
        $this->get('/lingua/en');

        $middleware = new SetLocale;
        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('X-Livewire', '1');
        $request->setLaravelSession(session()->driver());

        $middleware->handle($request, fn () => new Response('', 200));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_il_dizionario_traduce_i_contenuti_da_db(): void
    {
        $this->makeTour();

        // "Il tramonto più bello della Costa" è nel dizionario lang/en/db.php.
        $this->get('/en/tour/solarya-daily-escape')
            ->assertOk()
            ->assertSee('The most beautiful sunset on the Coast', false)
            ->assertDontSee('Il tramonto più bello della Costa', false);
    }

    public function test_i_contenuti_db_non_tradotti_ricadono_sull_italiano(): void
    {
        // Testo non presente nel dizionario: il frontend EN mostra l'italiano,
        // mai una chiave grezza o una stringa vuota.
        $this->makeTour([
            'slug' => 'tour-senza-traduzione',
            'description_short' => 'Questo testo non è nel dizionario',
        ]);

        $this->get('/en/tour/tour-senza-traduzione')
            ->assertOk()
            ->assertSee('Questo testo non è nel dizionario', false);
    }

    public function test_i_nomi_propri_non_vengono_tradotti(): void
    {
        $this->makeTour();

        $this->get('/en/tour/solarya-daily-escape')
            ->assertOk()
            ->assertSee('Solarya Daily Escape', false)
            ->assertSee('Cala Dei Sardi', false);
    }

    public function test_la_sitemap_include_le_due_lingue_con_annotazioni_reciproche(): void
    {
        $this->makeTour();

        $response = $this->get('/sitemap.xml')->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringContainsString('<loc>'.url('/tour/solarya-daily-escape').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/en/tour/solarya-daily-escape').'</loc>', $xml);
        $this->assertStringContainsString('hreflang="x-default"', $xml);
    }

    public function test_nessuna_chiave_grezza_di_traduzione_nelle_pagine(): void
    {
        $this->makeTour();

        $paths = ['/en', '/en/tour', '/en/tour/solarya-daily-escape', '/en/prenota?tour=solarya-daily-escape'];

        foreach ($paths as $path) {
            $content = $this->get($path)->assertOk()->getContent();

            // Una chiave non risolta apparirebbe come "common.nav.home".
            $this->assertDoesNotMatchRegularExpression(
                '/\b(common|home|tours|booking)\.[a-z_]+\.[a-z_]+\b/',
                strip_tags($content),
                "Chiave di traduzione non risolta visibile in {$path}"
            );
        }
    }

    public function test_le_date_seguono_il_locale_attivo(): void
    {
        app()->setLocale('it');
        $this->assertSame('01/06/2026', locale_date('2026-06-01'));
        $this->assertSame('01/06/2026 – 30/06/2026', locale_date_range('2026-06-01', '2026-06-30'));

        app()->setLocale('en');
        $this->assertSame('1 June 2026', locale_date('2026-06-01'));
        // Stesso mese: mese e anno una sola volta.
        $this->assertSame('1 – 30 June 2026', locale_date_range('2026-06-01', '2026-06-30'));
        // A cavallo di due mesi.
        $this->assertSame('25 May – 3 June 2026', locale_date_range('2026-05-25', '2026-06-03'));
    }

    public function test_le_etichette_stagionali_usano_carbon_non_il_dizionario(): void
    {
        app()->setLocale('en');

        // "Giugno" è un nome di mese: reso da Carbon sulla data di inizio.
        $this->assertSame('June', season_label('Giugno', '2026-06-01'));

        // Un'etichetta descrittiva passa invece dal dizionario (qui assente →
        // fallback italiano).
        $this->assertSame('Alta stagione', season_label('Alta stagione', '2026-06-01'));
    }

    public function test_tdb_normalizza_apostrofi_e_spazi(): void
    {
        app()->setLocale('en');

        // Apostrofo tipografico e spazi doppi non devono far fallire il match.
        $this->assertSame(
            'Your exclusive Sardinia starts here.',
            tdb('La tua Sardegna esclusiva inizia qui.')
        );

        $this->assertSame(
            'On request',
            tdb('  Su   richiesta ')
        );
    }

    /**
     * Il dizionario deve coprire i contenuti REALI di produzione forniti dal
     * cliente, non solo i dati di seed locali. Verifica anche la tolleranza a
     * apostrofi tipografici, spazi doppi ed entità HTML introdotti dall'editor
     * dell'admin.
     */
    public function test_il_dizionario_copre_i_contenuti_di_produzione(): void
    {
        app()->setLocale('en');

        $samples = [
            'Frutta Mista',
            'Light Lunch',
            'Caffè',
            'Acqua e Caffè illimitato',
            'Due Calici di Prosecco o Vino',
            'Taralli, Olive e Pane Guttiau',
            'Su richiesta',
            'Fascia 0-12',
            'Fascia 13 in poi',
            'Il tramonto più bello della Costa',
            'La tua Sardegna esclusiva inizia qui.',
            'Aperitivo (16:00) — Prosecco, olive, taralli, pane Guttiau',
            'Spuntino (10:30 – 11:30) — Frutta fresca',
            'Piscina di Molara (sosta) – Cala Girgolu & Spiagge delle Vacche (navigazione) – Capo Coda Cavallo (sosta) – Cala Brandinchi (sosta)',
            // Apostrofo tipografico invece di quello ASCII del dizionario.
            "Non prenotare un posto \u{2728} Prenota l\u{2019}intero mare \u{1F30A}",
            // Spazi doppi e &nbsp;.
            '  Su   richiesta ',
            'Acqua e&nbsp;Caffè illimitato',
        ];

        foreach ($samples as $italian) {
            $this->assertNotSame(
                $italian,
                tdb($italian),
                "Contenuto di produzione senza traduzione nel dizionario: {$italian}"
            );
        }
    }

    public function test_tdb_non_tocca_i_contenuti_in_italiano(): void
    {
        app()->setLocale('it');

        $this->assertSame('Su richiesta', tdb('Su richiesta'));
        $this->assertNull(tdb(null));
    }
}
