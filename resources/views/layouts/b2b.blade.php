<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Solarya Agenzie</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Google+Sans+Display:wght@400;500;700&family=Google+Sans+Text:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- Stessi asset dell'admin (via symlink b2b/build → public/build): un restyle
         dell'admin si propaga al portale agenzie senza duplicazioni. --}}
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles

    {{-- Flatpickr + Font Awesome: il form di prenotazione (componente condiviso
         col frontend) usa il datepicker e le icone fa-*. Li carichiamo qui senza
         importare l'intero tema pubblico, per non confliggere con Bootstrap. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/template/css/fontawesome-all.min.css') }}">

    <style>
        .admin-sidebar { width: 280px; flex-shrink: 0; min-height: 100vh; }
        .admin-sidebar .nav-link { color: rgba(255,255,255,.7); border-radius: .75rem; padding: .65rem 1rem; transition: all .15s ease; }
        .admin-sidebar .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .admin-sidebar .nav-link.active { background: linear-gradient(90deg, #facc15 0%, #eab308 100%); color: #0f172a; }
        .admin-sidebar .nav-link i { width: 20px; }
        .admin-sidebar .section-title { color: rgba(255,255,255,.4); font-size: .7rem; letter-spacing: .12em; }
        .admin-content-wrapper { min-width: 0; flex: 1; }
        @media (max-width: 991.98px) {
            .admin-sidebar { position: fixed; left: -280px; top: 0; bottom: 0; z-index: 1045; transition: left .3s ease; }
            .admin-sidebar.show { left: 0; }
        }

        /* ===== Form di prenotazione (componente Livewire condiviso col frontend) =====
           Il componente porta GIÀ i suoi stili .booking-widget .bk-* completi.
           Qui forniamo solo: (1) le variabili del tema mappate al colore del
           portale, così il widget si tinge del blu Bootstrap invece del viola
           del frontend; (2) lo stile del solo campo data (.input), che il
           componente non copre. Tutto il resto lo fa il componente stesso. */
        .booking-widget {
            --tg-theme-primary: var(--bs-primary, #0d6efd);
            --tg-theme-secondary: var(--bs-primary, #0d6efd);
            --tg-theme-secondary-rgb: 13, 110, 253;
            min-width: 0;
        }
        /* Il widget nasce per una sidebar stretta: nella card del portale
           evitiamo che input/righe sforino il bordo. */
        .booking-widget, .booking-widget * { box-sizing: border-box; }
        .booking-widget img { max-width: 100%; height: auto; }
        .booking-widget .row { --bs-gutter-x: .5rem; margin-left: 0; margin-right: 0; }
        .booking-widget .tg-tour-about-title,
        .booking-widget h4 { font-size: 1.15rem; font-weight: 700; color: #1e293b; }
        .booking-widget .tg-tour-about-sidebar-title { font-size: .82rem; font-weight: 700; color: #334155; }

        /* Il wrapper del datepicker (tema pubblico) riserva un'altezza enorme
           pensata per le label flottanti della barra di ricerca: azzeriamola,
           qui ci serve solo contenere l'input. */
        .booking-widget .tg-booking-form-parent-inner {
            min-height: 0; height: auto; padding: 0; margin-bottom: .9rem;
        }

        /* Spaziatura più compatta: i margini mb-15/mb-10 del tema sono troppo
           larghi per la card del portale. */
        .booking-widget .mb-15 { margin-bottom: .9rem !important; }
        .booking-widget .mb-10 { margin-bottom: .6rem !important; }
        .booking-widget .mb-5  { margin-bottom: .35rem !important; }

        /* Campo data (usa la classe .input del tema, non .bk-*) */
        .booking-widget .tg-tour-about-date { position: relative; }
        .booking-widget .tg-tour-about-date .input {
            width: 100%; padding: .55rem 2.25rem .55rem 2.25rem;
            border: 1.5px solid #eef0f3; border-radius: 50px; background: #fff;
            font-size: .9rem; cursor: pointer;
        }
        .booking-widget .tg-tour-about-date .input:focus {
            outline: none; border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(13,110,253,.12);
        }
        .booking-widget .tg-tour-about-date .calender,
        .booking-widget .tg-tour-about-date .angle {
            position: absolute; top: 50%; transform: translateY(-50%); pointer-events: none;
        }
        .booking-widget .tg-tour-about-date .calender { left: .85rem; }
        .booking-widget .tg-tour-about-date .angle { right: .95rem; color: #94a3b8; }

        .booking-widget .tg-tour-about-border-doted { border-top: 1px dashed #e2e8f0; }
        .booking-widget .tg-tour-about-tickets { display: flex; align-items: center; justify-content: space-between; }
        .booking-widget .tg-tour-about-coast .total-price { color: var(--bs-primary); font-weight: 800; }

        /* Pulsante "Prenota ora": a pillola e blu pieno, coerente con gli altri
           controlli del widget (input, stepper, "Applica" sono tutti arrotondati). */
        .booking-widget .tg-btn {
            border-radius: 50px;
            background: var(--bs-primary);
            border: none;
            color: #fff;
            font-weight: 600;
        }
        .booking-widget .tg-btn:hover { background: var(--bs-primary); filter: brightness(.92); color: #fff; }
    </style>

    @stack('styles')
</head>
<body class="admin-body bg-light">
    <div class="d-flex min-vh-100">

        {{-- Sidebar overlay (mobile) --}}
        <div class="offcanvas-backdrop fade d-lg-none" id="adminSidebarBackdrop" style="display:none"></div>

        {{-- Sidebar --}}
        <aside class="admin-sidebar sidebar-bg d-flex flex-column shadow-lg" id="adminSidebar">
            <div class="d-flex align-items-start justify-content-between px-4 py-4 border-bottom border-secondary border-opacity-25">
                <a href="{{ route('b2b.dashboard') }}" class="text-decoration-none d-flex flex-column align-items-start gap-2 flex-grow-1">
                    <img src="{{ asset('images/logo_white.svg') }}" alt="Solarya Travel" style="height:25px;width:auto;max-width:60%">
                    <small class="text-warning text-uppercase fw-semibold" style="letter-spacing:.18em;font-size:.65rem">Portale Agenzie</small>
                </a>
                <button class="btn btn-sm btn-link text-white d-lg-none p-0 ms-2" type="button" id="adminSidebarClose" aria-label="Chiudi menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="flex-grow-1 overflow-auto p-3">
                <ul class="nav nav-pills flex-column gap-1">
                    <li class="nav-item">
                        <a href="{{ route('b2b.dashboard') }}" class="nav-link {{ request()->routeIs('b2b.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-1x2-fill me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b.bookings.create') }}" class="nav-link {{ request()->routeIs('b2b.bookings.create') ? 'active' : '' }}">
                            <i class="bi bi-plus-circle-fill me-2"></i>Nuova prenotazione
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b.bookings.index') }}" class="nav-link {{ request()->routeIs('b2b.bookings.index') || request()->routeIs('b2b.bookings.show') ? 'active' : '' }}">
                            <i class="bi bi-journal-check me-2"></i>Le mie prenotazioni
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b.referral') }}" class="nav-link {{ request()->routeIs('b2b.referral') ? 'active' : '' }}">
                            <i class="bi bi-qr-code me-2"></i>Link &amp; QR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b.widget') }}" class="nav-link {{ request()->routeIs('b2b.widget') ? 'active' : '' }}">
                            <i class="bi bi-code-square me-2"></i>Widget per il sito
                        </a>
                    </li>

                    <li class="nav-item mt-2 pt-2 border-top border-secondary border-opacity-25">
                        <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('b2bLogoutForm').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i>Esci
                        </a>
                    </li>
                </ul>
            </nav>

            {{-- User Info --}}
            <div class="p-3 border-top border-secondary border-opacity-25 bg-black bg-opacity-25">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="d-inline-flex align-items-center justify-content-center bg-gold rounded-3 fw-bold text-navy flex-shrink-0" style="width:40px;height:40px">
                            {{ strtoupper(substr(auth()->user()->agency_name ?? auth()->user()->name ?? 'A', 0, 1)) }}
                        </span>
                        <div class="lh-sm text-truncate">
                            <div class="text-white fw-semibold small text-truncate">{{ auth()->user()->agency_name ?? auth()->user()->name }}</div>
                            <div class="text-white-50 small text-truncate">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <button type="submit" form="b2bLogoutForm" class="btn btn-sm btn-link text-white-50 p-2" title="Esci" data-bs-toggle="tooltip">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </button>
                </div>
            </div>

            {{-- Form di logout condiviso (usato dalla voce di menu "Esci" e dall'icona). --}}
            <form method="POST" action="{{ route('b2b.logout') }}" id="b2bLogoutForm" class="d-none">
                @csrf
            </form>
        </aside>

        {{-- Main wrapper --}}
        <div class="d-flex flex-column admin-content-wrapper">
            {{-- Topbar --}}
            <header class="sticky-top bg-white border-bottom shadow-sm">
                <div class="d-flex align-items-center justify-content-between px-3 px-lg-4" style="height:64px">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-link text-secondary p-2 d-lg-none" id="adminSidebarToggle" aria-label="Apri menu">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        <h1 class="h5 mb-0 fw-bold text-dark">@yield('title', 'Dashboard')</h1>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('b2b.bookings.create') }}" class="btn btn-sm btn-primary d-none d-sm-inline-flex align-items-center">
                            <i class="bi bi-plus-circle me-2"></i>Nuova prenotazione
                        </a>
                        <span class="d-none d-xl-inline-flex align-items-center gap-2 px-3 py-2 bg-light rounded-3 small text-muted">
                            <i class="bi bi-calendar3"></i>
                            {{ now()->locale('it')->isoFormat('D MMM YYYY') }}
                        </span>
                    </div>
                </div>
            </header>

            {{-- Banner impersonificazione (admin che opera per conto di un'agenzia) --}}
            @if(\App\Support\B2bContext::isImpersonator() && ($acting = \App\Support\B2bContext::actingAgency()))
                <div class="bg-warning-subtle border-bottom border-warning-subtle px-3 px-lg-4 py-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                        <span class="text-dark">
                            <i class="bi bi-person-badge me-1"></i>
                            Stai operando come <strong>{{ $acting->agency_name ?: $acting->name }}</strong>
                            (commissione {{ rtrim(rtrim(number_format($acting->commission_rate, 2), '0'), '.') }}%) ·
                            <span class="text-muted">admin {{ auth()->user()->name }}</span>
                        </span>
                        <form method="POST" action="{{ route('b2b.impersonate.stop') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-dark py-0 px-2">
                                <i class="bi bi-arrow-repeat me-1"></i>Cambia agenzia
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Content --}}
            <main class="flex-grow-1 p-3 p-lg-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible alert-auto-dismiss d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <span class="fw-medium">{{ session('success') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <span class="fw-medium">{{ session('error') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                        <span class="fw-medium">{{ session('warning') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="border-top bg-white px-3 px-lg-4 py-3">
                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 small text-muted">
                    <p class="m-0">&copy; {{ date('Y') }} Solarya Travel — Portale Agenzie.</p>
                    <p class="m-0 d-flex align-items-center gap-1">
                        Made with <i class="bi bi-heart-fill text-danger"></i> in Italia
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js"></script>

    @livewireScripts
    @stack('scripts')

    <script>
        // Mobile sidebar toggle
        (function() {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('adminSidebarBackdrop');
            const toggle = document.getElementById('adminSidebarToggle');
            const close = document.getElementById('adminSidebarClose');
            const open = () => { sidebar.classList.add('show'); backdrop.style.display='block'; backdrop.classList.add('show'); };
            const hide = () => { sidebar.classList.remove('show'); backdrop.classList.remove('show'); setTimeout(()=>backdrop.style.display='none', 150); };
            toggle?.addEventListener('click', open);
            close?.addEventListener('click', hide);
            backdrop?.addEventListener('click', hide);
        })();
    </script>
    @stack('end-of-body')
</body>
</html>
