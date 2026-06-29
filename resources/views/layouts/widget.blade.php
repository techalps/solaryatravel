<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Prenota — Solarya Travel')</title>
    <meta name="robots" content="noindex">

    <link rel="icon" type="image/png" href="{{ asset('images/logo_black.svg') }}">

    {{-- Stessi font del sito --}}
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/HelveticaNowDisplay-Medium.woff2') }}" crossorigin>

    {{-- Template CSS (stesso look del sito) --}}
    <link rel="stylesheet" href="{{ asset('assets/template/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/flatpicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/default.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/template/css/main.css') }}">

    <style>
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Light.woff2') }}') format('woff2');
            font-weight: 300; font-style: normal; font-display: swap;
        }
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Medium.woff2') }}') format('woff2');
            font-weight: 400 500; font-style: normal; font-display: swap;
        }
        @font-face {
            font-family: 'HelveticaNowDisplay';
            src: url('{{ asset('fonts/HelveticaNowDisplay-Bold.woff2') }}') format('woff2');
            font-weight: 600 700; font-style: normal; font-display: swap;
        }

        :root {
            --tg-ff-body: 'HelveticaNowDisplay', system-ui, -apple-system, Segoe UI, sans-serif !important;
            --tg-ff-outfit: 'HelveticaNowDisplay', system-ui, -apple-system, Segoe UI, sans-serif !important;
            --bs-primary: var(--tg-theme-primary);
            --bs-primary-rgb: 0, 69, 96;
            --bs-secondary: var(--tg-theme-secondary);
            --bs-secondary-rgb: 255, 125, 44;
            --bs-link-color: var(--tg-theme-primary);
            --bs-link-hover-color: var(--tg-theme-primary);
        }

        /* Sfondo trasparente: il widget eredita lo sfondo del sito ospite. */
        html, body { background: transparent; }
        body { font-family: var(--tg-ff-body); margin: 0; padding: 16px; }

        .text-primary   { color: var(--tg-theme-primary)   !important; }
        .text-secondary { color: var(--tg-theme-secondary) !important; }

        .btn-primary {
            --bs-btn-bg: var(--tg-theme-primary);
            --bs-btn-border-color: var(--tg-theme-primary);
            --bs-btn-hover-bg: var(--tg-theme-primary);
            --bs-btn-hover-border-color: var(--tg-theme-primary);
            --bs-btn-active-bg: var(--tg-theme-primary);
            --bs-btn-active-border-color: var(--tg-theme-primary);
            --bs-btn-disabled-bg: var(--tg-theme-primary);
            --bs-btn-disabled-border-color: var(--tg-theme-primary);
            --bs-btn-color: #fff; --bs-btn-hover-color: #fff;
        }
        .btn-primary:hover, .btn-primary:active, .btn-primary:focus-visible { filter: brightness(.88); }
        .btn-outline-primary {
            --bs-btn-color: var(--tg-theme-primary);
            --bs-btn-border-color: var(--tg-theme-primary);
            --bs-btn-hover-bg: var(--tg-theme-primary);
            --bs-btn-hover-border-color: var(--tg-theme-primary);
            --bs-btn-hover-color: #fff;
        }
        .btn, .tg-btn { border-radius: 50px !important; }
        .form-check-input:checked { background-color: var(--tg-theme-primary); border-color: var(--tg-theme-primary); }
        .form-check-input:focus { border-color: var(--tg-theme-primary); box-shadow: 0 0 0 .25rem rgba(0,69,96,.25); }

        /* Footer attribuzione discreto */
        .widget-credit { font-size: 11px; color: #9aa3ad; text-align: center; margin-top: 18px; }
        .widget-credit a { color: #9aa3ad; text-decoration: none; }
    </style>

    @livewireStyles
    @stack('head')
</head>
<body data-widget>
    <div class="container-fluid px-0" id="widget-root">
        @yield('content')

        <div class="widget-credit">
            Prenotazioni gestite da <a href="{{ rtrim(config('app.url'), '/') }}" target="_blank" rel="noopener">Solarya Travel</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js"></script>

    {{-- Auto-resize: comunica l'altezza al sito ospite per evitare scrollbar interne. --}}
    <script>
        (function () {
            function postHeight() {
                var h = document.getElementById('widget-root');
                if (!h) return;
                var height = Math.ceil(h.getBoundingClientRect().height) + 32;
                parent.postMessage({ type: 'solarya-widget:height', height: height }, '*');
            }
            // Iniziale + a ogni modifica del DOM (Livewire aggiorna spesso).
            window.addEventListener('load', postHeight);
            if (window.ResizeObserver) {
                new ResizeObserver(postHeight).observe(document.getElementById('widget-root'));
            } else {
                setInterval(postHeight, 500);
            }
            document.addEventListener('livewire:navigated', postHeight);
            window.addEventListener('resize', postHeight);
        })();
    </script>

    @livewireScripts
    @stack('scripts')
</body>
</html>
