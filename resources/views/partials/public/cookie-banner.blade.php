{{--
    Banner cookie + pannello preferenze (GDPR / linee guida Garante Privacy).
    - Categorie: Necessari (sempre attivi), Statistiche, Marketing.
    - Il consenso è salvato in localStorage 'solarya_cookie_consent'.
    - Applica il consenso a Google (Consent Mode v2) e Meta Pixel in tempo reale.
    - Riapribile in qualsiasi momento via link "Preferenze cookie" o
      con un elemento avente data-cookie-settings / classe .open-cookie-settings.
--}}
<div id="cookie-consent" class="cc-wrap" hidden>
    {{-- Backdrop (solo quando è aperto il pannello preferenze) --}}
    <div class="cc-backdrop" data-cc-close-prefs hidden></div>

    {{-- Banner base --}}
    <div class="cc-banner" role="dialog" aria-live="polite" aria-label="Informativa cookie" data-cc-banner>
        <div class="cc-banner-body">
            <h3 class="cc-title">🍪 Rispettiamo la tua privacy</h3>
            <p class="cc-text">
                Utilizziamo cookie tecnici necessari al funzionamento del sito e, previo tuo consenso,
                cookie di statistica e marketing (Google Analytics, Meta Pixel). Puoi accettare, rifiutare
                o personalizzare le tue scelte. Maggiori dettagli nella
                <a href="{{ route('cookies') }}">Cookie Policy</a>.
            </p>
        </div>
        <div class="cc-banner-actions">
            <button type="button" class="cc-btn cc-btn-ghost" data-cc-open-prefs>Personalizza</button>
            <button type="button" class="cc-btn cc-btn-ghost" data-cc-reject>Rifiuta</button>
            <button type="button" class="cc-btn cc-btn-primary" data-cc-accept-all>Accetta tutto</button>
        </div>
    </div>

    {{-- Pannello preferenze --}}
    <div class="cc-prefs" role="dialog" aria-modal="true" aria-label="Preferenze cookie" data-cc-prefs hidden>
        <div class="cc-prefs-header">
            <h3 class="cc-title mb-0">Preferenze cookie</h3>
            <button type="button" class="cc-close" data-cc-close-prefs aria-label="Chiudi">&times;</button>
        </div>
        <div class="cc-prefs-body">
            <div class="cc-cat">
                <div class="cc-cat-head">
                    <span class="cc-cat-name">Cookie necessari</span>
                    <span class="cc-cat-always">Sempre attivi</span>
                </div>
                <p class="cc-cat-desc">Indispensabili per la navigazione, l'autenticazione e la gestione delle prenotazioni. Non possono essere disattivati.</p>
            </div>

            <div class="cc-cat">
                <div class="cc-cat-head">
                    <span class="cc-cat-name">Cookie statistici</span>
                    <label class="cc-switch">
                        <input type="checkbox" data-cc-toggle="statistics">
                        <span class="cc-slider"></span>
                    </label>
                </div>
                <p class="cc-cat-desc">Ci aiutano a capire come viene usato il sito in forma aggregata (Google Analytics 4 con IP anonimizzato).</p>
            </div>

            <div class="cc-cat">
                <div class="cc-cat-head">
                    <span class="cc-cat-name">Cookie di marketing</span>
                    <label class="cc-switch">
                        <input type="checkbox" data-cc-toggle="marketing">
                        <span class="cc-slider"></span>
                    </label>
                </div>
                <p class="cc-cat-desc">Utilizzati per misurare le campagne pubblicitarie e mostrare annunci pertinenti (Meta Pixel, Google Ads).</p>
            </div>
        </div>
        <div class="cc-prefs-actions">
            <button type="button" class="cc-btn cc-btn-ghost" data-cc-reject>Rifiuta tutto</button>
            <button type="button" class="cc-btn cc-btn-primary" data-cc-save>Salva preferenze</button>
            <button type="button" class="cc-btn cc-btn-primary" data-cc-accept-all>Accetta tutto</button>
        </div>
    </div>
</div>

<style>
    .cc-wrap { position: fixed; inset: 0; z-index: 2147483000; pointer-events: none; }
    .cc-wrap > * { pointer-events: auto; }
    .cc-backdrop { position: fixed; inset: 0; background: rgba(14,27,51,.55); }

    .cc-banner {
        position: fixed; left: 16px; right: 16px; bottom: 16px; margin: 0 auto;
        max-width: 1100px; background: #fff; border-radius: 16px;
        box-shadow: 0 20px 60px rgba(14,27,51,.25);
        padding: 22px 24px; display: flex; gap: 20px; align-items: center;
        flex-wrap: wrap;
    }
    .cc-banner-body { flex: 1 1 380px; }
    .cc-title { font-weight: 700; color: var(--tg-common-black, #0E1B33); font-size: 1.05rem; margin: 0 0 .4rem; }
    .cc-text { color: #475569; font-size: .9rem; line-height: 1.6; margin: 0; }
    .cc-text a { color: var(--tg-theme-primary); font-weight: 600; text-decoration: none; }
    .cc-text a:hover { text-decoration: underline; }
    .cc-banner-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

    .cc-btn {
        border: 0; border-radius: 50px; padding: 11px 22px; font-weight: 700; font-size: .85rem;
        cursor: pointer; white-space: nowrap; transition: transform .15s, opacity .15s, background .15s;
    }
    .cc-btn:hover { transform: translateY(-1px); }
    .cc-btn-primary { background: var(--tg-theme-primary, #7C37FF); color: #fff; }
    .cc-btn-ghost { background: transparent; color: var(--tg-common-black, #0E1B33); border: 1px solid #cbd5e1; }
    .cc-btn-ghost:hover { background: #f1f5f9; }

    .cc-prefs {
        position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: min(560px, calc(100% - 32px)); max-height: 85vh; overflow: auto;
        background: #fff; border-radius: 16px; box-shadow: 0 24px 70px rgba(14,27,51,.35);
    }
    .cc-prefs-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #eef2f7; }
    .cc-close { background: none; border: 0; font-size: 1.8rem; line-height: 1; color: #94a3b8; cursor: pointer; }
    .cc-close:hover { color: #475569; }
    .cc-prefs-body { padding: 8px 24px 4px; }
    .cc-cat { padding: 18px 0; border-bottom: 1px solid #f1f5f9; }
    .cc-cat:last-child { border-bottom: 0; }
    .cc-cat-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .35rem; }
    .cc-cat-name { font-weight: 700; color: var(--tg-common-black, #0E1B33); }
    .cc-cat-always { font-size: .78rem; font-weight: 700; color: var(--tg-theme-primary, #7C37FF); }
    .cc-cat-desc { color: #64748b; font-size: .85rem; line-height: 1.55; margin: 0; }
    .cc-prefs-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; padding: 18px 24px 22px; border-top: 1px solid #eef2f7; }

    /* Toggle */
    .cc-switch { position: relative; display: inline-block; width: 46px; height: 26px; flex-shrink: 0; }
    .cc-switch input { opacity: 0; width: 0; height: 0; }
    .cc-slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; border-radius: 26px; transition: .2s; }
    .cc-slider::before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .2s; }
    .cc-switch input:checked + .cc-slider { background: var(--tg-theme-primary, #7C37FF); }
    .cc-switch input:checked + .cc-slider::before { transform: translateX(20px); }

    @media (max-width: 575px) {
        .cc-banner { padding: 18px; }
        .cc-banner-actions { width: 100%; }
        .cc-banner-actions .cc-btn { flex: 1 1 auto; }
        .cc-prefs-actions .cc-btn { flex: 1 1 auto; }
    }
</style>

<script>
(function () {
    var KEY = 'solarya_cookie_consent';
    var root = document.getElementById('cookie-consent');
    if (!root) return;

    var banner   = root.querySelector('[data-cc-banner]');
    var prefs    = root.querySelector('[data-cc-prefs]');
    var backdrop = root.querySelector('.cc-backdrop');
    var toggles  = {
        statistics: root.querySelector('[data-cc-toggle="statistics"]'),
        marketing:  root.querySelector('[data-cc-toggle="marketing"]')
    };

    function read() {
        try { return JSON.parse(localStorage.getItem(KEY) || 'null'); }
        catch (e) { return null; }
    }

    function gtagSafe() {
        // gtag è definito nel partial tracking; se assente, no-op
        return (typeof window.gtag === 'function') ? window.gtag : function () {};
    }

    // Applica il consenso agli strumenti (Consent Mode + Meta Pixel) senza ricaricare
    function apply(consent) {
        var g = gtagSafe();
        g('consent', 'update', {
            analytics_storage: consent.statistics ? 'granted' : 'denied',
            ad_storage:        consent.marketing  ? 'granted' : 'denied',
            ad_user_data:      consent.marketing  ? 'granted' : 'denied',
            ad_personalization:consent.marketing  ? 'granted' : 'denied'
        });
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'consent_update',
            consent_statistics: !!consent.statistics,
            consent_marketing:  !!consent.marketing
        });
        if (typeof window.fbq === 'function') {
            window.fbq('consent', consent.marketing ? 'grant' : 'revoke');
            if (consent.marketing) window.fbq('track', 'PageView');
        }
    }

    function save(consent) {
        consent.necessary = true;
        consent.ts = new Date().toISOString();
        try { localStorage.setItem(KEY, JSON.stringify(consent)); } catch (e) {}
        apply(consent);
        hideAll();
    }

    function showBanner() { root.hidden = false; banner.hidden = false; prefs.hidden = true; backdrop.hidden = true; }
    function showPrefs()  {
        root.hidden = false; banner.hidden = true; prefs.hidden = false; backdrop.hidden = false;
        var saved = read() || {};
        toggles.statistics.checked = !!saved.statistics;
        toggles.marketing.checked  = !!saved.marketing;
    }
    function hideAll()    { root.hidden = true; }

    // Bottoni
    root.querySelectorAll('[data-cc-accept-all]').forEach(function (b) {
        b.addEventListener('click', function () { save({ statistics: true, marketing: true }); });
    });
    root.querySelectorAll('[data-cc-reject]').forEach(function (b) {
        b.addEventListener('click', function () { save({ statistics: false, marketing: false }); });
    });
    root.querySelector('[data-cc-save]').addEventListener('click', function () {
        save({ statistics: toggles.statistics.checked, marketing: toggles.marketing.checked });
    });
    root.querySelector('[data-cc-open-prefs]').addEventListener('click', showPrefs);
    root.querySelectorAll('[data-cc-close-prefs]').forEach(function (b) {
        b.addEventListener('click', function () { read() ? hideAll() : showBanner(); });
    });

    // Riapertura da link esterni (footer, cookie policy)
    document.querySelectorAll('[data-cookie-settings], .open-cookie-settings').forEach(function (el) {
        el.addEventListener('click', function (e) { e.preventDefault(); showPrefs(); });
    });

    // Mostra il banner solo se non c'è ancora una scelta salvata
    if (!read()) showBanner();
})();
</script>
