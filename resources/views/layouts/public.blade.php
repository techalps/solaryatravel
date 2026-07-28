<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('home.meta.title'))</title>
    <meta name="description" content="@yield('meta_description', __('home.meta.description'))">

    {{-- ============= SEO multilingua ============= --}}
    @php
        // La pagina corrente ha una controparte nell'altra lingua solo se sta
        // dentro il perimetro bilingue (home, listing, dettaglio tour, prenota,
        // pagine legali). Altrove (pagamenti, area utente) non emettiamo
        // hreflang: quelle pagine esistono in una sola versione.
        $i18nLocalized = locale_route_is_localized(locale_base_route_name());
        $i18nLocales = (array) config('locales.supported', ['it']);
    @endphp

    @if($i18nLocalized)
        <link rel="canonical" href="{{ locale_current_url(app()->getLocale()) }}">
        @foreach($i18nLocales as $altLocale)
            <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ locale_current_url($altLocale) }}">
        @endforeach
        {{-- x-default → inglese: il target primario del sito è la clientela
             turistica straniera. Configurabile in config/locales.php. --}}
        <link rel="alternate" hreflang="x-default" href="{{ locale_current_url(config('locales.x_default', 'en')) }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    <meta property="og:locale" content="{{ config('locales.og.'.app()->getLocale(), 'it_IT') }}">
    @if($i18nLocalized)
        @foreach($i18nLocales as $altLocale)
            @if($altLocale !== app()->getLocale())
                <meta property="og:locale:alternate" content="{{ config('locales.og.'.$altLocale) }}">
            @endif
        @endforeach
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Solarya Travel">
    <meta property="og:title" content="@yield('title', __('home.meta.title'))">
    <meta property="og:description" content="@yield('meta_description', __('home.meta.description'))">
    <meta property="og:url" content="{{ url()->current() }}">

    @if(config('services.tracking.search_console'))
        <meta name="google-site-verification" content="{{ config('services.tracking.search_console') }}">
    @endif

    {{-- Tracciamento (Consent Mode v2): deve stare il più in alto possibile nell'head --}}
    @include('partials.public.tracking')

    <link rel="icon" type="image/png" href="{{ asset('images/logo_black.svg') }}">

    {{-- Font del sito: HelveticaNowDisplay (locale, sostituisce Poppins + Outfit) --}}
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/HelveticaNowDisplay-Medium.woff2') }}" crossorigin>

    {{-- Template CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/template/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/flatpicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/odometer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/default.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/main.css') }}">

    {{-- Font locali: HelveticaNowDisplay (sostituisce Poppins+Outfit) + Frezbie (decorativo) --}}
    <style>
        /* === HelveticaNowDisplay — Light / Medium / Bold / Black === */
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Light.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/HelveticaNowDisplay-Light.woff') }}') format('woff');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Medium.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/HelveticaNowDisplay-Medium.woff') }}') format('woff');
            /* Range 400-500: copre sia "normal"/400 sia "medium"/500 → body usa Medium come default */
            font-weight: 400 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Bold.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/HelveticaNowDisplay-Bold.woff') }}') format('woff');
            /* Range 600-700: copre semibold e bold */
            font-weight: 600 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Black.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/HelveticaNowDisplay-Black.woff') }}') format('woff');
            /* Range 800-900: copre extrabold e black */
            font-weight: 800 900;
            font-style: normal;
            font-display: swap;
        }

        /* === Frezbie (font decorativo, invariato) === */
        @font-face {
            font-family: 'Frezbie';
            src: url('{{ asset('fonts/Frezbie.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/Frezbie.woff') }}') format('woff'),
                 url('{{ asset('fonts/Frezbie.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            /* Sostituisce Poppins (body) e Outfit (heading/UI) */
            --tg-ff-body: 'HelveticaNowDisplay', system-ui, -apple-system, Segoe UI, sans-serif !important;
            --tg-ff-outfit: 'HelveticaNowDisplay', system-ui, -apple-system, Segoe UI, sans-serif !important;
            /* Frezbie sui sottotitoli/heading decorativi (invariato) */
            --tg-ff-segoepr: 'Frezbie', cursive !important;
            --tg-ff-chillax: 'Frezbie', sans-serif !important;
        }
        /* Forza Frezbie ovunque venga usato segoepr o chillax e rimuove bold/italic/corsivo */
        .tg-section-subtitle,
        [class*="tg-section-subtitle"],
        .tg-hero-subtitle,
        .tg-banner-subtitle,
        .tg-cta-subtitle,
        .tg-about-subtitle,
        .tg-chose-big-text h2,
        .tg-banner-2-big-title h2,
        [style*="--tg-ff-segoepr"],
        [style*="--tg-ff-chillax"] {
            font-family: 'Frezbie', cursive !important;
            font-weight: normal !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-transform: uppercase !important;
        }

        /* Header solido (pagine interne): voci di menu scure */
        .tg-header__area:not(.tg-transparent) .tgmenu__navbar-wrap > ul > li > a,
        .header-sticky .tgmenu__navbar-wrap > ul > li > a {
            color: var(--tg-common-black, #0E1B33);
        }
        .tg-header__area:not(.tg-transparent) .tgmenu__navbar-wrap > ul > li.active > a,
        .tg-header__area:not(.tg-transparent) .tgmenu__navbar-wrap > ul > li:hover > a {
            color: var(--tg-theme-primary);
        }
        .tg-header__area:not(.tg-transparent) .tg-header-contact-number span,
        .tg-header__area:not(.tg-transparent) .tg-header-contact-number a {
            color: var(--tg-common-black, #0E1B33);
        }
        .tg-header__area:not(.tg-transparent) .tg-btn-header {
            background: var(--tg-theme-primary);
            color: #fff;
        }

        /* Bottoni arrotondati a pillola in tutto il sito */
        .tg-btn,
        .tg-btn-2,
        .tg-btn-header,
        .bk-search-button,
        .tg-footer-form-btn,
        button.tg-btn,
        a.tg-btn,
        .btn {
            border-radius: 50px !important;
        }

        /* Testi bianchi in tutte le hero/breadcrumb */
        .tg-breadcrumb-area { color: #fff; }
        .tg-breadcrumb-area h1, .tg-breadcrumb-area h2, .tg-breadcrumb-area h3,
        .tg-breadcrumb-area h4, .tg-breadcrumb-area h5, .tg-breadcrumb-area h6,
        .tg-breadcrumb-area p, .tg-breadcrumb-area span, .tg-breadcrumb-area small,
        .tg-breadcrumb-area a:not(.btn):not(.tg-btn),
        .tg-breadcrumb-area .lead { color: #fff !important; }
        .tg-breadcrumb-area .breadcrumb-item::before { color: rgba(255,255,255,.55) !important; }

        /* Sovrascrive Bootstrap primary/secondary con le variabili del tema */
        :root {
            --bs-primary:           var(--tg-theme-primary);
            --bs-primary-rgb:       0, 69, 96;   /* #004560 */
            --bs-secondary:         var(--tg-theme-secondary);
            --bs-secondary-rgb:     255, 125, 44; /* #ff7d2c */
            --bs-link-color:        var(--tg-theme-primary);
            --bs-link-hover-color:  var(--tg-theme-primary);
        }
        .text-primary   { color: var(--tg-theme-primary)   !important; }
        .text-secondary { color: var(--tg-theme-secondary) !important; }

        /* btn-primary */
        .btn-primary {
            --bs-btn-bg:               var(--tg-theme-primary);
            --bs-btn-border-color:     var(--tg-theme-primary);
            --bs-btn-hover-bg:         var(--tg-theme-primary);
            --bs-btn-hover-border-color: var(--tg-theme-primary);
            --bs-btn-active-bg:        var(--tg-theme-primary);
            --bs-btn-active-border-color: var(--tg-theme-primary);
            --bs-btn-disabled-bg:      var(--tg-theme-primary);
            --bs-btn-disabled-border-color: var(--tg-theme-primary);
            --bs-btn-color: #fff;
            --bs-btn-hover-color: #fff;
            filter: none;
        }
        .btn-primary:hover,
        .btn-primary:active,
        .btn-primary:focus-visible { filter: brightness(.88); }

        /* btn-outline-primary */
        .btn-outline-primary {
            --bs-btn-color:              var(--tg-theme-primary);
            --bs-btn-border-color:       var(--tg-theme-primary);
            --bs-btn-hover-bg:           var(--tg-theme-primary);
            --bs-btn-hover-border-color: var(--tg-theme-primary);
            --bs-btn-active-bg:          var(--tg-theme-primary);
            --bs-btn-active-border-color: var(--tg-theme-primary);
            --bs-btn-hover-color: #fff;
            --bs-btn-active-color: #fff;
        }

        /* form-check focus ring */
        .form-check-input:checked          { background-color: var(--tg-theme-primary); border-color: var(--tg-theme-primary); }
        .form-check-input:focus            { border-color: var(--tg-theme-primary); box-shadow: 0 0 0 .25rem rgba(0,69,96,.25); }

        /* border-primary / bg-primary */
        .border-primary { border-color: var(--tg-theme-primary) !important; }
        .bg-primary     { background-color: var(--tg-theme-primary) !important; }
        .link-primary   { color: var(--tg-theme-primary) !important; }

        /* Tour card — link "Scopri" al posto delle recensioni */
        .tg-card-tour-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            font-weight: 600;
            color: var(--tg-theme-primary);
            text-decoration: none;
            transition: color .2s, gap .2s;
        }
        .tg-card-tour-link:hover {
            color: var(--tg-theme-secondary);
            gap: 8px;
        }

        /* Credito di realizzazione nel footer: sulla stessa riga del
           copyright, separato da un punto medio e un tono più tenue del
           testo accanto (#7b7e88 del template → #63666f qui).
           Su mobile il separatore sparisce e va a capo da solo. */
        .tg-footer-copyright .tg-footer-credit {
            color: #63666f;
            font-size: 13px;
        }
        .tg-footer-copyright .tg-footer-credit::before {
            content: '·';
            margin: 0 10px;
            /* Il separatore è decorativo: non va letto dagli screen reader
               come contenuto, e su schermi stretti scompare col wrap. */
            color: #4a4d55;
        }
        .tg-footer-copyright .tg-footer-credit a {
            color: #8b8f99;
            text-decoration: none;
            transition: color .18s cubic-bezier(.22, 1, .36, 1);
        }
        .tg-footer-copyright .tg-footer-credit a:hover,
        .tg-footer-copyright .tg-footer-credit a:focus-visible {
            color: var(--tg-common-white, #fff);
            text-decoration: underline;
        }

        /* Logo del vendor. Il PNG è un off-white fisso (rgb 244,242,238):
           a piena opacità brillerebbe più del testo del copyright accanto,
           quindi lo teniamo smorzato e lo portiamo a piena resa sull'hover.
           Larghezza a 74px su un file da 150px: ~2x di riserva, così resta
           nitido anche sui display retina. */
        .tg-footer-copyright .tg-footer-credit-logo {
            display: inline-block;
            /* Nessuna sottolineatura sotto un'immagine */
            text-decoration: none !important;
            vertical-align: middle;
            /* Compensa il baseline del testo accanto */
            margin-top: -2px;
        }
        .tg-footer-copyright .tg-footer-credit-logo img {
            display: block;
            /* 66px su un file da 150px: oltre 2x di riserva per i display
               retina. A questa misura l'altezza-x del wordmark combacia con
               "powered by" accanto, invece di sovrastarlo. */
            width: 66px;
            height: auto;
            opacity: .62;
            transition: opacity .18s cubic-bezier(.22, 1, .36, 1);
        }
        .tg-footer-copyright .tg-footer-credit-logo:hover img,
        .tg-footer-copyright .tg-footer-credit-logo:focus-visible img {
            opacity: 1;
        }
        .tg-footer-copyright .tg-footer-credit-logo:focus-visible {
            outline: 2px solid var(--tg-theme-secondary);
            outline-offset: 3px;
            border-radius: 2px;
        }

        /* Sotto i 576px copyright e credito stanno su due righe: il punto
           medio a inizio riga sarebbe un residuo grafico. */
        @media (max-width: 575.98px) {
            .tg-footer-copyright .tg-footer-credit { display: block; margin-top: 8px; }
            .tg-footer-copyright .tg-footer-credit::before { content: none; margin: 0; }
        }

        /* =========================================================
           Selettore lingua — segmented toggle
           Un solo contenitore, lingua attiva evidenziata da un "cursore"
           pieno. Misure e raggio ripresi da .tg-btn-header di questa
           variante di header (fill rgba bianco, radius 6px) così il
           controllo sta accanto alla CTA senza competerle.
           ========================================================= */
        .tg-lang {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 2px;
            border-radius: 8px;
        }

        /* Nell'header il selettore è l'ultimo elemento della riga: gli serve
           un respiro dal bordo destro, che il .container-fluid (12px di gutter)
           non gli dà. Non tocchiamo il padding del container per non spostare
           logo e menu; sopra 1400px il container è già centrato con margini
           propri e il compenso non serve. */
        .tg-lang--inline { margin-right: 10px; }

        @media (min-width: 1400px) {
            .tg-lang--inline { margin-right: 0; }
        }

        .tg-lang__item {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-width: 32px;
            height: 26px;
            padding: 0 8px;
            border-radius: 6px;
            font-family: var(--tg-ff-outfit, inherit);
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            /* Le sigle sono acronimi: il maiuscolo è tipografico, non urlato */
            letter-spacing: .04em;
            text-decoration: none;
            white-space: nowrap;
            transition: color .18s cubic-bezier(.22, 1, .36, 1),
                        background-color .18s cubic-bezier(.22, 1, .36, 1);
        }

        /* Target touch ≥ 32px in altezza reale, senza gonfiare il layout */
        .tg-lang__item::after {
            content: '';
            position: absolute;
            inset: -4px -2px;
        }

        .tg-lang__code { display: block; }

        /* Bandiera: rapporto 2:1, angoli appena stondati e hairline scura che
           impedisce al bianco della bandiera di fondersi col fondo chiaro
           dell'header solido (l'Union Jack e il tricolore hanno entrambi il
           bianco sul bordo). */
        .tg-lang__flag {
            display: block;
            width: 17px;
            height: auto;
            aspect-ratio: 2 / 1;
            border-radius: 2px;
            box-shadow: 0 0 0 .5px rgba(0, 0, 0, .28);
            /* La bandiera non si sgrana quando il testo accanto è in grassetto */
            flex: 0 0 auto;
        }

        /* Su fondo scuro la hairline nera sparisce: usa un velo bianco. */
        .tg-header__area.tg-transparent .tg-lang__flag,
        .tg-lang--stacked .tg-lang__flag {
            box-shadow: 0 0 0 .5px rgba(255, 255, 255, .45);
        }
        /* …ma quando l'elemento attivo ha il fondo bianco, torna la hairline scura. */
        .tg-header__area.tg-transparent .tg-lang__item.is-active .tg-lang__flag,
        .tg-lang--stacked .tg-lang__item.is-active .tg-lang__flag {
            box-shadow: 0 0 0 .5px rgba(0, 0, 0, .28);
        }

        /* La lingua non attiva è secondaria: la bandiera resta leggibile ma
           non compete con quella dello stato corrente. */
        .tg-lang a.tg-lang__item .tg-lang__flag {
            opacity: .75;
            transition: opacity .18s cubic-bezier(.22, 1, .36, 1);
        }
        .tg-lang a.tg-lang__item:hover .tg-lang__flag { opacity: 1; }

        .tg-lang__item:focus-visible {
            outline: 2px solid var(--tg-theme-secondary);
            outline-offset: 2px;
        }

        /* ---------- Header trasparente (hero della home) ----------
           Fondo scuro: il contenitore è un velo bianco tenue, l'attivo
           è bianco pieno con testo scuro — massimo contrasto, zero bordi. */
        .tg-header__area.tg-transparent .tg-lang {
            background: rgba(255, 255, 255, .14);
        }
        .tg-header__area.tg-transparent .tg-lang__item {
            color: rgba(255, 255, 255, .78);
        }
        .tg-header__area.tg-transparent a.tg-lang__item:hover {
            color: #fff;
            background: rgba(255, 255, 255, .16);
        }
        .tg-header__area.tg-transparent .tg-lang__item.is-active {
            color: var(--tg-common-black, #020615);
            background: #fff;
        }

        /* ---------- Header solido (pagine interne) e sticky ----------
           Fondo bianco: contenitore grigio chiarissimo, attivo nel blu
           del brand. #5a6570 su #f1f3f5 supera 4.5:1 per il testo inattivo. */
        .tg-header__area:not(.tg-transparent) .tg-lang,
        .header-sticky .tg-lang {
            background: #f1f3f5;
        }
        .tg-header__area:not(.tg-transparent) .tg-lang__item,
        .header-sticky .tg-lang__item {
            color: #5a6570;
        }
        .tg-header__area:not(.tg-transparent) a.tg-lang__item:hover,
        .header-sticky a.tg-lang__item:hover {
            color: var(--tg-theme-primary);
            background: rgba(0, 69, 96, .08);
        }
        .tg-header__area:not(.tg-transparent) .tg-lang__item.is-active,
        .header-sticky .tg-lang__item.is-active {
            color: #fff;
            background: var(--tg-theme-primary);
        }

        /* La home diventa sticky scrollando: il velo trasparente non deve
           sopravvivere sul fondo bianco. Vince la regola sticky. */
        .header-sticky.tg-transparent .tg-lang { background: #f1f3f5; }
        .header-sticky.tg-transparent .tg-lang__item { color: #5a6570; }
        .header-sticky.tg-transparent .tg-lang__item.is-active {
            color: #fff;
            background: var(--tg-theme-primary);
        }

        /* ---------- Variante offcanvas mobile / footer ----------
           Fondo scuro in entrambi i casi, larghezza piena per il pollice. */
        .tg-lang--stacked {
            gap: 4px;
            padding: 3px;
            background: rgba(255, 255, 255, .10);
            border-radius: 10px;
            /* Nel footer sta in una colonna larga: senza tetto si stirerebbe
               per tutta la colonna e non leggerebbe più come un controllo.
               Nell'offcanvas mobile la utility .w-100 lo porta a piena
               larghezza (target comodo per il pollice). */
            max-width: 200px;
        }
        .tg-lang--stacked.w-100 { max-width: none; }
        .tg-lang--stacked .tg-lang__flag { width: 19px; }

        .tg-lang--stacked .tg-lang__item {
            flex: 1 1 0;
            min-width: 74px;
            height: 38px;
            font-size: 14px;
            border-radius: 7px;
            color: rgba(255, 255, 255, .8);
        }
        .tg-lang--stacked a.tg-lang__item:hover {
            color: #fff;
            background: rgba(255, 255, 255, .14);
        }
        .tg-lang--stacked .tg-lang__item.is-active {
            color: var(--tg-common-black, #020615);
            background: #fff;
        }

        @media (prefers-reduced-motion: reduce) {
            .tg-lang__item { transition: none; }
        }
    </style>

    @livewireStyles
    @stack('head')
</head>
<body>
    @if(config('services.tracking.gtm_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('services.tracking.gtm_id') }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    @include('partials.public.header')

    <main>
        @yield('content')
    </main>

    @include('partials.public.footer')

    {{-- Banner consenso cookie --}}
    @include('partials.public.cookie-banner')

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/wow.js@1.2.2/dist/wow.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js"></script>

    {{-- Stringhe per il JavaScript del frontend: niente italiano hardcoded
         negli script (label ospiti, placeholder, messaggi). --}}
    <script>
        window.i18n = @json(__('common.js'));
        window.appLocale = @json(app()->getLocale());
    </script>

    {{-- Locale di flatpickr: nomi di mesi/giorni nella lingua attiva.
         In italiano usiamo il locale definito qui sotto (il bundle CDN "it"
         richiederebbe uno script extra); in inglese vale il default della
         libreria, già inglese, con la settimana che inizia di lunedì. --}}
    <script>
        (function () {
            if (typeof flatpickr === 'undefined') return;

            var locales = {
                it: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
                        longhand: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
                    },
                    months: {
                        shorthand: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                        longhand: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
                    },
                    rangeSeparator: ' al ',
                    weekAbbreviation: 'Sett',
                    scrollTitle: 'Scorri per aumentare',
                    toggleTitle: 'Clicca per cambiare',
                    time_24hr: true,
                },
                en: {
                    firstDayOfWeek: 1,
                    time_24hr: true,
                },
            };

            // Locale globale: vale per OGNI flatpickr istanziato nel sito
            // (barra di ricerca, widget di prenotazione).
            flatpickr.localize(locales[window.appLocale] || locales.en);

            // Formato della data mostrata all'utente (altFormat):
            // IT 01/06/2026 — EN 1 June 2026.
            window.i18nDateAltFormat = window.appLocale === 'it' ? 'd/m/Y' : 'j F Y';
        })();
    </script>

    <script>
        // WOW
        if (typeof WOW !== 'undefined') {
            new WOW({ live: false }).init();
        }
        // Scroll-up button
        const scrollUp = document.getElementById('scrollUp');
        if (scrollUp) {
            window.addEventListener('scroll', () => {
                scrollUp.classList.toggle('show', window.scrollY > 400);
            });
            scrollUp.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        // Sticky header
        const header = document.getElementById('header-sticky');
        if (header) {
            window.addEventListener('scroll', () => {
                header.classList.toggle('header-sticky', window.scrollY > 80);
            });
        }
    </script>

    <style>
        #scrollUp {
            position: fixed; right: 24px; bottom: 24px; width: 44px; height: 44px;
            border-radius: 50%; border: 0; background: var(--tg-theme-primary); color: #fff;
            display: none; align-items: center; justify-content: center;
            box-shadow: 0 6px 20px rgba(124,55,255,.35); cursor: pointer; z-index: 1040;
            transition: all .25s ease;
        }
        #scrollUp.show { display: inline-flex; }
        #scrollUp:hover { transform: translateY(-3px); }

        /* Avatar iniziale nel dropdown header */
        .tg-user-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,.25);
            color: #fff;
            font-size: .8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .tg-header__area:not(.tg-transparent) .tg-user-avatar {
            background: var(--tg-theme-primary);
        }
        /* Rimuove la freccia Bootstrap di default dal dropdown */
        .tg-btn-header.dropdown-toggle::after { display: none; }

        /* Header: container-fluid limitato a 1860px su desktop large */
        @media (min-width: 1400px) {
            #header-sticky > .container-fluid {
                max-width: 1860px;
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>

    @livewireScripts
    @stack('scripts')
</body>
</html>
