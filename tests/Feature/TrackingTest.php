<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tracciamento (Google Tag Manager) — attivo SOLO in produzione.
 *
 * Requisito esplicito: in locale il tag NON deve partire, altrimenti le visite
 * di sviluppo falsano le statistiche (e in GA4 non si ripuliscono). Il controllo
 * sta in config/services.php → tracking.enabled, così vale in un colpo solo per
 * GTM, GA4 e Meta Pixel: gli ID possono restare in .env anche fuori produzione
 * (comodo per allineare gli ambienti) senza che venga emesso nulla.
 */
class TrackingTest extends TestCase
{
    use DatabaseTransactions;

    /** Il partial è quello incluso dal layout pubblico in <head>. */
    private function trackingHtml(): string
    {
        return view('partials.public.tracking')->render();
    }

    public function test_in_locale_non_viene_emesso_nessun_tag(): void
    {
        config([
            'services.tracking.enabled' => false,
            'services.tracking.gtm_id' => 'GTM-MKQLNHZ7',
            'services.tracking.ga4_id' => 'G-TEST12345',
            'services.tracking.meta_pixel_id' => '1234567890',
        ]);

        $html = $this->trackingHtml();

        // Gli ID sono configurati, ma fuori produzione non deve uscire nulla.
        $this->assertStringNotContainsString('GTM-MKQLNHZ7', $html);
        $this->assertStringNotContainsString('googletagmanager', $html);
        $this->assertStringNotContainsString('gtm.js', $html);
        $this->assertStringNotContainsString('G-TEST12345', $html);
        $this->assertStringNotContainsString('connect.facebook.net', $html);
    }

    public function test_in_produzione_il_tag_gtm_viene_emesso(): void
    {
        config([
            'services.tracking.enabled' => true,
            'services.tracking.gtm_id' => 'GTM-MKQLNHZ7',
        ]);

        $html = $this->trackingHtml();

        $this->assertStringContainsString('GTM-MKQLNHZ7', $html);
        $this->assertStringContainsString('googletagmanager.com/gtm.js', $html);
    }

    public function test_il_consent_mode_precede_il_tag_gtm(): void
    {
        config([
            'services.tracking.enabled' => true,
            'services.tracking.gtm_id' => 'GTM-MKQLNHZ7',
        ]);

        $html = $this->trackingHtml();

        // Il consenso di default (tutto negato) deve stare PRIMA di GTM,
        // altrimenti il container potrebbe scrivere cookie senza consenso.
        $posConsent = strpos($html, "gtag('consent', 'default'");
        $posGtm = strpos($html, 'googletagmanager.com/gtm.js');

        $this->assertNotFalse($posConsent, 'Il Consent Mode di default deve essere presente.');
        $this->assertNotFalse($posGtm);
        $this->assertLessThan($posGtm, $posConsent, 'Il Consent Mode deve precedere GTM.');
        $this->assertStringContainsString("analytics_storage: 'denied'", $html);
    }

    public function test_senza_id_non_si_emette_gtm_nemmeno_in_produzione(): void
    {
        config([
            'services.tracking.enabled' => true,
            'services.tracking.gtm_id' => null,
        ]);

        $this->assertStringNotContainsString('googletagmanager.com/gtm.js', $this->trackingHtml());
    }

    public function test_la_home_in_locale_non_contiene_il_noscript_gtm(): void
    {
        config([
            'services.tracking.enabled' => false,
            'services.tracking.gtm_id' => 'GTM-MKQLNHZ7',
        ]);

        // La home redirige sul prefisso di lingua: seguiamo il redirect.
        $html = $this->followingRedirects()->get('/')->assertOk()->getContent();

        // Né lo script in <head> né l'iframe noscript in <body>.
        $this->assertStringNotContainsString('GTM-MKQLNHZ7', $html);
        $this->assertStringNotContainsString('googletagmanager.com/ns.html', $html);
    }

    public function test_la_home_in_produzione_contiene_script_e_noscript(): void
    {
        config([
            'services.tracking.enabled' => true,
            'services.tracking.gtm_id' => 'GTM-MKQLNHZ7',
        ]);

        // La home redirige sul prefisso di lingua: seguiamo il redirect.
        $html = $this->followingRedirects()->get('/')->assertOk()->getContent();

        // Richiesta originale: tag in <head> E noscript in <body>.
        $this->assertStringContainsString('googletagmanager.com/gtm.js', $html);
        $this->assertStringContainsString('googletagmanager.com/ns.html?id=GTM-MKQLNHZ7', $html);
    }

    /**
     * Il widget è incorporato via iframe sui siti delle agenzie: non deve
     * portarsi dietro il nostro tracciamento.
     */
    public function test_il_widget_non_include_il_tracciamento(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/widget.blade.php'));

        $this->assertStringNotContainsString('googletagmanager', $layout);
        $this->assertStringNotContainsString('partials.public.tracking', $layout);
    }
}
