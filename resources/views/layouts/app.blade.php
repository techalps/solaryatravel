<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Solarya Travel') }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Escursioni in catamarano di lusso lungo la Costiera Amalfitana. Prenota la tua esperienza indimenticabile.' }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,600,700|inter:300,400,500,600,700" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles

    @stack('head')
</head>
<body>
    <a href="#main-content" class="skip-link">Vai al contenuto principale</a>

    {{-- Header / Navbar --}}
    <header class="sticky-top header-blur shadow-sm border-bottom">
        <nav class="navbar navbar-expand-lg py-2">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                    <span class="d-inline-flex align-items-center justify-content-center bg-gradient-primary rounded-3 shadow-sm" style="width:42px;height:42px">
                        <i class="bi bi-water text-white fs-5"></i>
                    </span>
                    <span class="d-none d-sm-block lh-1">
                        <span class="d-block font-serif fs-4 fw-semibold text-navy">Solarya</span>
                        <small class="text-warning text-uppercase" style="letter-spacing:.2em;font-size:.65rem">Travel</small>
                    </span>
                </a>

                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav mx-lg-auto gap-lg-2">
                        <li class="nav-item">
                            <a class="nav-link fw-medium {{ request()->routeIs('home') ? 'active text-primary' : '' }}" href="{{ route('home') }}">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium {{ request()->routeIs('tours.*') ? 'active text-primary' : '' }}" href="{{ route('tours.index') }}">I nostri Tour</a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                        @auth
                            <a href="{{ route('bookings.my') }}" class="btn btn-link text-decoration-none text-navy d-none d-sm-inline-flex align-items-center">
                                <i class="bi bi-journal-text me-2"></i>Le mie prenotazioni
                            </a>
                        @endauth
                        <a href="{{ route('booking.start') }}" class="btn btn-gold rounded-pill px-4 shadow-sm">
                            <i class="bi bi-calendar2-check me-2"></i>
                            <span class="d-none d-sm-inline">Prenota Ora</span>
                            <span class="d-sm-none">Prenota</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080; margin-top:5rem">
            <div class="alert alert-success alert-dismissible alert-auto-dismiss shadow-lg" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080; margin-top:5rem">
            <div class="alert alert-danger alert-dismissible alert-auto-dismiss shadow-lg" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    {{-- Main --}}
    <main id="main-content">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-navy text-white pt-5 mt-auto">
        <div class="container py-4 py-lg-5">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center bg-gradient-gold rounded-3" style="width:48px;height:48px">
                            <i class="bi bi-water text-navy fs-4"></i>
                        </span>
                        <div class="lh-1">
                            <span class="d-block font-serif fs-3 fw-semibold">Solarya</span>
                            <small class="text-gold-400 text-uppercase" style="letter-spacing:.2em;font-size:.7rem">Travel</small>
                        </div>
                    </div>
                    <p class="text-white-50 mb-4">
                        Esperienze uniche in catamarano lungo la Costiera Amalfitana.
                        Scopri il mare come mai prima d'ora.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="text-warning fw-semibold mb-3">Link Rapidi</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="{{ route('tours.index') }}" class="text-white-50 text-decoration-none">I nostri Tour</a></li>
                        <li><a href="{{ route('booking.start') }}" class="text-white-50 text-decoration-none">Prenota Online</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <h6 class="text-warning fw-semibold mb-3">Contatti</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 text-white-50">
                        <li><i class="bi bi-geo-alt-fill text-warning me-2"></i>Porto Turistico di Salerno<br><span class="ms-4">Molo Manfredi, 84121 SA</span></li>
                        <li><i class="bi bi-telephone-fill text-warning me-2"></i><a href="tel:+391234567890" class="text-white-50 text-decoration-none">+39 123 456 7890</a></li>
                        <li><i class="bi bi-envelope-fill text-warning me-2"></i><a href="mailto:info@solaryatravel.com" class="text-white-50 text-decoration-none">info@solaryatravel.com</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="text-warning fw-semibold mb-3">Newsletter</h6>
                    <p class="text-white-50 small mb-3">Iscriviti per ricevere offerte esclusive e novità.</p>
                    <form action="#" method="POST">
                        @csrf
                        <div class="mb-2">
                            <input type="email" name="email" required class="form-control bg-navy-800 border-0 text-white" placeholder="La tua email">
                        </div>
                        <button type="submit" class="btn btn-gold w-100">Iscriviti</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="border-top border-secondary border-opacity-25">
            <div class="container py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <small class="text-white-50">© {{ date('Y') }} Solarya Travel. Tutti i diritti riservati.</small>
                    <div class="d-flex gap-3 small">
                        <a href="{{ route('privacy') }}" class="text-white-50 text-decoration-none">Privacy Policy</a>
                        <a href="{{ route('terms') }}" class="text-white-50 text-decoration-none">Termini e Condizioni</a>
                        <a href="{{ route('cookies') }}" class="text-white-50 text-decoration-none">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- WhatsApp float --}}
    <a href="https://wa.me/391234567890" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Contattaci su WhatsApp">
        <i class="bi bi-whatsapp fs-3"></i>
    </a>

    @livewireScripts
    @stack('scripts')
</body>
</html>
