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

    {{-- Flatpickr: necessario per il calendario del form di prenotazione,
         identico al frontend cliente (vedi layouts/public). --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">

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
                    <form method="POST" action="{{ route('b2b.logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-white-50 p-2" title="Esci" data-bs-toggle="tooltip">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </button>
                    </form>
                </div>
            </div>
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
