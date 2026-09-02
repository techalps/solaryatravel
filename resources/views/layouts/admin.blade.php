<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Solarya Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Google+Sans+Display:wght@400;500;700&family=Google+Sans+Text:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles

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
                @php
                    // Lo skipper non può vedere la dashboard: il logo porta alla
                    // sua unica sezione.
                    $isSkipper = auth()->user()->isSkipper();
                    $homeRoute = $isSkipper ? route('admin.boarding.index') : route('admin.dashboard');
                @endphp
                <a href="{{ $homeRoute }}" class="text-decoration-none d-flex flex-column align-items-start gap-2 flex-grow-1">
                    <img src="{{ asset('images/logo_white.svg') }}" alt="Solarya Travel" style="height:25px;width:auto;max-width:60%">
                    <small class="text-warning text-uppercase fw-semibold" style="letter-spacing:.18em;font-size:.65rem">{{ $isSkipper ? 'Imbarco' : 'Admin Panel' }}</small>
                </a>
                <button class="btn btn-sm btn-link text-white d-lg-none p-0 ms-2" type="button" id="adminSidebarClose" aria-label="Chiudi menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="flex-grow-1 overflow-auto p-3">
                <ul class="nav nav-pills flex-column gap-1">
                @if($isSkipper)
                    {{-- Ruolo skipper: unica voce accessibile (le altre sono
                         comunque bloccate lato server da SkipperAreaMiddleware). --}}
                    <li class="nav-item">
                        <a href="{{ route('admin.boarding.index') }}" class="nav-link {{ request()->routeIs('admin.boarding.*') ? 'active' : '' }}">
                            <i class="bi bi-qr-code-scan me-2"></i>Imbarco
                        </a>
                    </li>
                @else

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-1x2-fill me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.bookings.index') }}" class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-check me-2"></i>Prenotazioni
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.notifications.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                            <i class="bi bi-bell me-2"></i>Notifiche
                            {{-- Contatore lato server: la campanella in alto lo
                                 aggiorna via polling, qui vale al caricamento. --}}
                            @php($nonLetteMenu = auth()->user()?->hasFullAdminAccess()
                                ? app(\App\Services\AdminNotificationService::class)->unreadCount(auth()->user())
                                : 0)
                            @if($nonLetteMenu > 0)
                                <span class="badge rounded-pill bg-danger ms-auto">{{ $nonLetteMenu > 99 ? '99+' : $nonLetteMenu }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.schedule') }}" class="nav-link {{ request()->routeIs('admin.schedule') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-event me-2"></i>Programma Oggi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.boarding.index') }}" class="nav-link {{ request()->routeIs('admin.boarding.*') ? 'active' : '' }}">
                            <i class="bi bi-qr-code-scan me-2"></i>Imbarco
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.assignments.index') }}" class="nav-link {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-left-right me-2"></i>Assegnazione
                        </a>
                    </li>

                    <li class="px-2 pt-3 pb-1"><div class="section-title text-uppercase fw-bold">Gestione</div></li>

                    <li class="nav-item">
                        <a href="{{ route('admin.tours.index') }}" class="nav-link {{ request()->routeIs('admin.tours.*') ? 'active' : '' }}">
                            <i class="bi bi-compass me-2"></i>Tour
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.catamarans.index') }}" class="nav-link {{ request()->routeIs('admin.catamarans.*') ? 'active' : '' }}">
                            <i class="bi bi-water me-2"></i>Flotta
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.addons.index') }}" class="nav-link {{ request()->routeIs('admin.addons.*') ? 'active' : '' }}">
                            <i class="bi bi-plus-square me-2"></i>Extra & Servizi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.discounts.index') }}" class="nav-link {{ request()->routeIs('admin.discounts.*') ? 'active' : '' }}">
                            <i class="bi bi-tag-fill me-2"></i>Codici Sconto
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill me-2"></i>Utenti & Ruoli
                        </a>
                    </li>

                    <li class="px-2 pt-3 pb-1"><div class="section-title text-uppercase fw-bold">Analisi</div></li>

                    <li class="nav-item">
                        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-fill me-2"></i>Report & Statistiche
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                            <i class="bi bi-credit-card-fill me-2"></i>Pagamenti
                        </a>
                    </li>
                    @if(auth()->user()->hasSuperAdminPowers())
                        <li class="nav-item">
                            <a href="{{ route('admin.commissions.index') }}" class="nav-link {{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}">
                                <i class="bi bi-percent me-2"></i>Commissioni Agenzie
                            </a>
                        </li>
                    @endif

                    <li class="px-2 pt-3 pb-1"><div class="section-title text-uppercase fw-bold">Sistema</div></li>

                    <li class="nav-item">
                        <a href="{{ route('admin.guide.index') }}" class="nav-link {{ request()->routeIs('admin.guide.*') ? 'active' : '' }}">
                            <i class="bi bi-life-preserver me-2"></i>Guida
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill me-2"></i>Impostazioni
                        </a>
                    </li>

                    @if(auth()->user()->isSystemAdmin())
                        {{-- Sezione tecnica: visibile solo al ruolo system_admin --}}
                        <li class="px-2 pt-3 pb-1"><div class="section-title text-uppercase fw-bold">Tecnico</div></li>
                        <li class="nav-item">
                            <a href="{{ route('admin.system.logs') }}" class="nav-link {{ request()->routeIs('admin.system.logs') ? 'active' : '' }}">
                                <i class="bi bi-activity me-2"></i>Log &amp; Diagnostica
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.deploy.index') }}" class="nav-link {{ request()->routeIs('admin.deploy*') ? 'active' : '' }}">
                                <i class="bi bi-rocket-takeoff-fill me-2"></i>Deploy &amp; Migrazioni
                            </a>
                        </li>
                    @endif
                @endif
                </ul>
            </nav>

            {{-- User Info --}}
            <div class="p-3 border-top border-secondary border-opacity-25 bg-black bg-opacity-25">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="d-inline-flex align-items-center justify-content-center bg-gold rounded-3 fw-bold text-navy flex-shrink-0" style="width:40px;height:40px">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </span>
                        <div class="lh-sm text-truncate">
                            <div class="text-white fw-semibold small text-truncate">{{ auth()->user()->name ?? 'Admin' }}</div>
                            <div class="text-white-50 small text-truncate">{{ auth()->user()->email ?? '' }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-white-50 p-2" title="Logout" data-bs-toggle="tooltip">
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
                        <div class="position-relative d-none d-lg-block">
                            <input type="text" class="form-control form-control-sm bg-light border-0 ps-5" placeholder="Cerca..." style="width:240px">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted small"></i>
                        </div>
                        <a href="{{ route('booking.start') }}" target="_blank" class="btn btn-sm btn-primary d-none d-sm-inline-flex align-items-center">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Vedi Sito
                        </a>
                        {{-- Campanella notifiche: badge e menu si aggiornano da
                             soli via polling (vedi lo script in fondo). --}}
                        <div class="dropdown" id="notifBell">
                            <button class="btn btn-link text-secondary p-2 position-relative" type="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-label="Notifiche" title="Notifiche">
                                <i class="bi bi-bell fs-5"></i>
                                <span class="badge rounded-pill bg-danger position-absolute d-none"
                                      id="notifBadge"
                                      style="top:2px;right:0;font-size:.65rem;min-width:1.15rem">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-0"
                                 style="width:360px;max-width:92vw">
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                    <strong class="small">Notifiche</strong>
                                    <form method="POST" action="{{ route('admin.notifications.read-all') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none small">
                                            Segna tutte lette
                                        </button>
                                    </form>
                                </div>
                                <div id="notifList" style="max-height:min(60vh,420px);overflow-y:auto">
                                    <div class="text-muted small text-center py-4">Caricamento…</div>
                                </div>
                                <a href="{{ route('admin.notifications.index') }}"
                                   class="d-block text-center small py-2 border-top text-decoration-none">
                                    Vedi tutte
                                </a>
                            </div>
                        </div>

                        <span class="d-none d-xl-inline-flex align-items-center gap-2 px-3 py-2 bg-light rounded-3 small text-muted">
                            <i class="bi bi-calendar3"></i>
                            {{ now()->locale('it')->isoFormat('D MMM YYYY') }}
                        </span>
                    </div>
                </div>
            </header>

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
                    <p class="m-0">&copy; {{ date('Y') }} Solarya Travel. Tutti i diritti riservati.</p>
                    <p class="m-0 d-flex align-items-center gap-1">
                        Made with <i class="bi bi-heart-fill text-danger"></i> in Italia
                    </p>
                </div>
            </footer>
        </div>
    </div>

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
    {{-- Toast notifiche, in alto a destra --}}
    <div id="notifToasts" class="position-fixed d-flex flex-column gap-2"
         style="top:76px;right:1rem;z-index:1085;max-width:360px;width:calc(100vw - 2rem)"></div>

    <script>
        // ===== Notifiche: badge, menu e toast =====
        //
        // Polling e non WebSocket: su hosting condiviso OVH non si possono
        // tenere processi persistenti, quindi si interroga il feed ogni 30s.
        // Il ritardo massimo percepito è mezzo minuto.
        (function () {
            const badge = document.getElementById('notifBadge');
            const lista = document.getElementById('notifList');
            const toasts = document.getElementById('notifToasts');
            if (!badge || !lista) return;

            const feedUrl = @json(route('admin.notifications.feed'));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const readUrlTpl = @json(route('admin.notifications.read-ajax', ['notification' => '__ID__']));
            const mostrati = new Set();   // toast già comparsi in questa pagina

            function escapeHtml(v) {
                const d = document.createElement('div');
                d.textContent = v == null ? '' : String(v);
                return d.innerHTML;
            }

            function renderBadge(n) {
                badge.textContent = n > 99 ? '99+' : n;
                badge.classList.toggle('d-none', n === 0);
            }

            function renderLista(items) {
                if (!items.length) {
                    lista.innerHTML = '<div class="text-muted small text-center py-4">Nessuna notifica.</div>';
                    return;
                }
                lista.innerHTML = items.map(function (i) {
                    return '<a href="' + i.url + '" class="d-flex gap-2 px-3 py-2 text-decoration-none border-bottom'
                        + (i.read ? '' : ' bg-light') + '">'
                        + '<i class="bi ' + escapeHtml(i.icon) + ' text-' + escapeHtml(i.color) + ' mt-1"></i>'
                        + '<span class="flex-grow-1 small">'
                        + '<span class="d-block fw-semibold text-dark">' + escapeHtml(i.title) + '</span>'
                        + (i.body ? '<span class="d-block text-muted">' + escapeHtml(i.body) + '</span>' : '')
                        + '<span class="d-block text-muted" style="font-size:.72rem">' + escapeHtml(i.ago) + '</span>'
                        + '</span>'
                        + (i.read ? '' : '<span class="badge bg-primary align-self-start" style="font-size:.6rem">nuova</span>')
                        + '</a>';
                }).join('');
            }

            function mostraToast(t) {
                if (mostrati.has(t.id)) return;   // non ripetere a ogni ciclo
                mostrati.add(t.id);

                const el = document.createElement('div');
                el.className = 'bg-white shadow rounded-4 border-0 overflow-hidden';
                el.innerHTML = '<div class="d-flex gap-2 p-3">'
                    + '<i class="bi ' + escapeHtml(t.icon) + ' text-' + escapeHtml(t.color) + ' fs-5"></i>'
                    + '<div class="flex-grow-1 small">'
                    + '<a href="' + t.url + '" class="fw-semibold text-dark d-block text-decoration-none">' + escapeHtml(t.title) + '</a>'
                    + (t.body ? '<span class="text-muted">' + escapeHtml(t.body) + '</span>' : '')
                    + '</div>'
                    + '<button type="button" class="btn-close" aria-label="Chiudi"></button>'
                    + '</div>';

                function chiudi() {
                    el.remove();
                    // Segnala letta: il toast è stato visto, non deve ricomparire.
                    if (csrf) {
                        fetch(readUrlTpl.replace('__ID__', t.id), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        }).then(function (r) { return r.json(); })
                          .then(function (d) { if (d && typeof d.unread === 'number') renderBadge(d.unread); })
                          .catch(function () {});
                    }
                }

                el.querySelector('.btn-close').addEventListener('click', chiudi);
                toasts.appendChild(el);
                setTimeout(chiudi, 12000);   // si chiude da sé
            }

            function aggiorna() {
                // Con la scheda in background non serve interrogare il server.
                if (document.hidden) return;

                fetch(feedUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) {
                        if (!d) return;
                        renderBadge(d.unread);
                        renderLista(d.items || []);
                        (d.toasts || []).forEach(mostraToast);
                    })
                    .catch(function () { /* rete assente: riprova al ciclo dopo */ });
            }

            aggiorna();
            setInterval(aggiorna, 30000);
            // Tornando sulla scheda si aggiorna subito, senza aspettare il ciclo.
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) aggiorna();
            });
        })();
    </script>

    @stack('end-of-body')
</body>
</html>
