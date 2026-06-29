/**
 * Solarya Travel — widget di prenotazione incorporabile.
 *
 * Uso (snippet fornito alle agenzie nel portale b2b):
 *
 *   <div data-solarya-widget data-ref="TOKEN_AGENZIA"></div>
 *   <script src="https://solaryatravel.com/embed.js" async></script>
 *
 * Lo script trova ogni <div data-solarya-widget>, vi crea dentro un <iframe>
 * verso /widget?ref=TOKEN e ne adatta l'altezza ai contenuti ascoltando il
 * messaggio postMessage 'solarya-widget:height' emesso dalla pagina widget.
 *
 * Parametri opzionali sul div:
 *   data-ref="..."    (obbligatorio) codice agenzia per l'attribuzione
 *   data-tour="slug"  apri direttamente una crociera specifica
 *   data-height="700" altezza iniziale in px (default 600) prima dell'auto-resize
 *
 * Self-contained, nessuna dipendenza. L'origin del widget è quello da cui
 * è stato caricato questo stesso script.
 */
(function () {
    'use strict';

    // Origin da cui è servito embed.js → base del widget. Ricavato dallo
    // <script> corrente (document.currentScript non è affidabile con async).
    function widgetOrigin() {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            var src = scripts[i].src || '';
            if (src.indexOf('/embed.js') !== -1) {
                try { return new URL(src).origin; } catch (e) { /* continua */ }
            }
        }
        return null;
    }

    var ORIGIN = widgetOrigin();
    if (!ORIGIN) {
        if (window.console) console.error('[Solarya] embed.js: impossibile determinare l\'origin del widget.');
        return;
    }

    // Mappa iframe → contenitore, per indirizzare i messaggi di resize al frame giusto.
    var frames = [];

    function buildUrl(ref, tour) {
        var url = ORIGIN + '/widget';
        var params = [];
        if (tour) params.push('tour=' + encodeURIComponent(tour));
        if (ref) params.push('ref=' + encodeURIComponent(ref));
        if (params.length) url += '?' + params.join('&');
        return url;
    }

    function mount(el) {
        if (el.getAttribute('data-solarya-mounted') === '1') return;
        el.setAttribute('data-solarya-mounted', '1');

        var ref = el.getAttribute('data-ref') || '';
        var tour = el.getAttribute('data-tour') || '';
        var initialHeight = parseInt(el.getAttribute('data-height') || '600', 10);

        if (!ref && window.console) {
            console.warn('[Solarya] embed.js: manca data-ref (codice agenzia): le prenotazioni non ti verranno attribuite.');
        }

        var iframe = document.createElement('iframe');
        iframe.src = buildUrl(ref, tour);
        iframe.title = 'Prenota la tua crociera — Solarya Travel';
        iframe.setAttribute('loading', 'lazy');
        iframe.setAttribute('allow', 'payment');
        iframe.style.width = '100%';
        iframe.style.border = '0';
        iframe.style.height = initialHeight + 'px';
        iframe.style.transition = 'height .15s ease';
        // overflow gestito dal contenuto; l'auto-resize evita la scrollbar interna.
        iframe.scrolling = 'no';

        el.appendChild(iframe);
        frames.push(iframe);
    }

    function mountAll() {
        var els = document.querySelectorAll('[data-solarya-widget]');
        for (var i = 0; i < els.length; i++) mount(els[i]);
    }

    // Auto-resize: la pagina widget invia { type:'solarya-widget:height', height }.
    window.addEventListener('message', function (event) {
        if (event.origin !== ORIGIN) return;
        var data = event.data;
        if (!data || data.type !== 'solarya-widget:height' || !data.height) return;

        // Indirizza al frame che ha inviato il messaggio.
        for (var i = 0; i < frames.length; i++) {
            if (frames[i].contentWindow === event.source) {
                frames[i].style.height = data.height + 'px';
                break;
            }
        }
    }, false);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountAll);
    } else {
        mountAll();
    }
})();
