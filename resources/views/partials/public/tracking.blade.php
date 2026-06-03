{{--
    Script di tracciamento con Google Consent Mode v2.
    Gli strumenti partono in stato "denied": nessun cookie di profilazione
    viene scritto finché l'utente non acconsente tramite il banner.
    Il consenso è salvato in localStorage ('solarya_cookie_consent') e
    riapplicato a ogni caricamento pagina dal partial cookie-banner.
--}}
@php
    $gtmId       = config('services.tracking.gtm_id');
    $ga4Id       = config('services.tracking.ga4_id');
    $metaPixelId = config('services.tracking.meta_pixel_id');
@endphp

{{-- Consent Mode: stato di default = negato. Va PRIMA di qualsiasi tag Google. --}}
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }

    // Stato di default: tutto negato finché l'utente non sceglie
    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
        functionality_storage: 'granted',
        security_storage: 'granted',
        wait_for_update: 500
    });

    // Riapplica un eventuale consenso già salvato (visite successive)
    (function () {
        try {
            var saved = JSON.parse(localStorage.getItem('solarya_cookie_consent') || 'null');
            if (saved) {
                gtag('consent', 'update', {
                    analytics_storage: saved.statistics ? 'granted' : 'denied',
                    ad_storage: saved.marketing ? 'granted' : 'denied',
                    ad_user_data: saved.marketing ? 'granted' : 'denied',
                    ad_personalization: saved.marketing ? 'granted' : 'denied'
                });
                window.dataLayer.push({
                    event: 'consent_loaded',
                    consent_statistics: !!saved.statistics,
                    consent_marketing: !!saved.marketing
                });
            }
        } catch (e) {}
    })();
</script>

@if($gtmId)
    {{-- Google Tag Manager — rispetta automaticamente il Consent Mode impostato sopra --}}
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
@endif

@if($ga4Id)
    {{-- Google Analytics 4 diretto (usare se NON gestito via GTM) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
        gtag('js', new Date());
        gtag('config', '{{ $ga4Id }}', { anonymize_ip: true });
    </script>
@endif

@if($metaPixelId)
    {{-- Meta Pixel — caricato disattivato, attivato solo col consenso marketing --}}
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');

        fbq('consent', 'revoke'); // parte revocato: nessun tracciamento finché non si acconsente
        fbq('init', '{{ $metaPixelId }}');

        (function () {
            try {
                var saved = JSON.parse(localStorage.getItem('solarya_cookie_consent') || 'null');
                if (saved && saved.marketing) {
                    fbq('consent', 'grant');
                    fbq('track', 'PageView');
                }
            } catch (e) {}
        })();
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"/></noscript>
@endif
