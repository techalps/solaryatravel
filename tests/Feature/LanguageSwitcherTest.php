<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Selettore lingua: bandiera corrente + dropdown con le altre lingue attive.
 *
 * Sostituisce il vecchio toggle segmentato, che affiancava TUTTE le lingue: con
 * due stava in piedi, ma con quattro o cinque sbordava dall'header. Qui
 * l'ingombro del controllo è costante e le altre lingue stanno nel menu.
 */
class LanguageSwitcherTest extends TestCase
{
    use DatabaseTransactions;

    /** Le lingue attive si leggono dalle impostazioni (cache 'app_settings'). */
    private function activeLocales(array $locales): void
    {
        Cache::put('app_settings', ['active_locales' => $locales], 60);
    }

    protected function tearDown(): void
    {
        Cache::forget('app_settings');

        parent::tearDown();
    }

    private function render(string $current = 'it', string $variant = 'inline'): string
    {
        app()->setLocale($current);

        return view('partials.public.language-switcher', ['variant' => $variant])->render();
    }

    public function test_mostra_solo_la_lingua_corrente_nel_trigger(): void
    {
        $this->activeLocales(['it', 'en', 'fr', 'es']);

        $html = $this->render('it');

        // Un solo trigger, con la sigla della lingua corrente.
        $this->assertSame(1, substr_count($html, 'tg-lang__current'));
        $this->assertMatchesRegularExpression('/tg-lang__code">IT</', $html);
    }

    public function test_le_altre_lingue_attive_stanno_nel_dropdown(): void
    {
        $this->activeLocales(['it', 'en', 'fr', 'es']);

        $html = $this->render('it');

        preg_match_all('/hreflang="([a-z]{2})"/', $html, $m);

        // Tutte le attive tranne la corrente, e la corrente non si ripete.
        $this->assertSame(['en', 'fr', 'es'], $m[1]);
        $this->assertNotContains('it', $m[1]);
    }

    public function test_l_ingombro_del_trigger_non_cresce_con_le_lingue(): void
    {
        // È il motivo del cambio: con 2 o con 5 lingue il controllo visibile
        // resta uno solo. Prima erano N elementi affiancati.
        foreach ([['it', 'en'], ['it', 'en', 'de', 'fr', 'es']] as $locales) {
            $this->activeLocales($locales);
            $html = $this->render('it');

            $this->assertSame(1, substr_count($html, 'tg-lang__current'));
            $this->assertSame(count($locales) - 1, substr_count($html, 'tg-lang__option'));
        }
    }

    public function test_la_bandiera_mostrata_e_quella_della_lingua_corrente(): void
    {
        $this->activeLocales(['it', 'en', 'fr', 'es']);

        // In francese il trigger deve portare la bandiera francese, non l'italiana.
        $html = $this->render('fr');
        $trigger = substr($html, 0, strpos($html, '</button>'));

        $this->assertStringContainsString('#002654', $trigger, 'Blu di Francia atteso nel trigger.');
        $this->assertStringNotContainsString('#009246', $trigger, 'Il verde italiano non va nel trigger.');
        $this->assertMatchesRegularExpression('/tg-lang__code">FR</', $trigger);
    }

    public function test_con_una_sola_lingua_attiva_il_selettore_non_si_stampa(): void
    {
        $this->activeLocales(['it']);

        // Niente da scegliere: un controllo che non fa nulla è solo rumore.
        $this->assertSame('', trim($this->render('it')));
    }

    public function test_ogni_lingua_del_catalogo_ha_la_sua_bandiera(): void
    {
        // Il dropdown le elenca tutte: una bandiera mancante lascerebbe una
        // riga "nuda" rispetto alle altre.
        foreach (array_keys(config('locales.names')) as $locale) {
            $flag = config('locales.flags.'.$locale);
            $this->assertNotNull($flag, "Manca la bandiera per '{$locale}'.");
            $this->assertTrue(
                view()->exists('partials.public.flags.'.$flag),
                "Manca il file bandiera '{$flag}' per la lingua '{$locale}'."
            );
        }
    }

    public function test_il_nome_lingua_e_scritto_nella_lingua_stessa(): void
    {
        $this->activeLocales(['it', 'en', 'de', 'fr', 'es']);

        $html = $this->render('it');

        // "Deutsch", non "Tedesco": è ciò che chi la parla riconosce.
        $this->assertStringContainsString('Deutsch', $html);
        $this->assertStringContainsString('Español', $html);
        $this->assertStringContainsString('Français', $html);
    }

    public function test_il_cambio_lingua_resta_sulla_stessa_pagina(): void
    {
        $this->activeLocales(['it', 'en', 'fr', 'es']);

        // Regressione: lo switcher non deve rimandare alla home.
        $this->get('/lingua/fr?route=home')->assertRedirect(url('/fr'));
    }

    public function test_i_menu_multipli_nella_stessa_pagina_hanno_id_distinti(): void
    {
        $this->activeLocales(['it', 'en']);

        // Header desktop, offcanvas mobile e footer includono lo stesso partial:
        // con un id fisso i dropdown si aprirebbero a vicenda.
        $first = $this->render('it', 'inline');
        $second = $this->render('it', 'stacked');

        preg_match('/id="(tg-lang-[^"]+)"/', $first, $a);
        preg_match('/id="(tg-lang-[^"]+)"/', $second, $b);

        $this->assertNotEmpty($a[1] ?? null);
        $this->assertNotSame($a[1], $b[1] ?? null);
    }
}
