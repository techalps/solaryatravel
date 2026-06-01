<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Solarya Travel') – Escursioni in Catamarano</title>
    <meta name="description" content="@yield('meta_description', 'Vivi esperienze esclusive in catamarano lungo la Costiera. Solarya Travel: lusso, comfort ed eleganza in mare.')">

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
    </style>

    @livewireStyles
    @stack('head')
</head>
<body>
    <main>
        @yield('content')
    </main>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

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
