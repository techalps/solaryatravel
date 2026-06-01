{{-- Header One (template tu/su) --}}
@php $isHome = request()->routeIs('home'); @endphp
<header class="tg-header-height">
    <div class="tg-header__area tg-header-tu-menu tg-header-lg-space z-index-999 {{ $isHome ? 'tg-transparent' : '' }}" id="header-sticky">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-xxl-9 col-xl-8 col-lg-8 col-5">
                    <div class="tgmenu__wrap d-flex align-items-center">
                        <div class="logo mr-25">
                            <a class="logo-1 {{ $isHome ? '' : 'd-none' }}" href="{{ route('home') }}">
                                <img src="{{ asset('images/logo_white.svg') }}" alt="Solarya Travel" style="height:27px;width:auto">
                            </a>
                            <a class="logo-2 {{ $isHome ? 'd-none' : '' }}" href="{{ route('home') }}">
                                <img src="{{ asset('images/logo_black.svg') }}" alt="Solarya Travel" style="height:27px;width:auto">
                            </a>
                        </div>
                        <nav class="tgmenu__nav tgmenu-1-space ml-190">
                            <div class="tgmenu__navbar-wrap tgmenu__main-menu d-none d-xl-flex">
                                <ul class="navigation">
                                    <li>
                                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('tours.index') }}" class="{{ request()->routeIs('catamarans.*') ? 'active' : '' }}">Catamarani</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('experiences') }}" class="{{ request()->routeIs('experiences') ? 'active' : '' }}">Esperienze</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">Chi Siamo</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contatti</a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-4 col-7">
                    <div class="tg-menu-right-action d-flex align-items-center justify-content-end">
                        <div class="tg-header-contact-info d-flex align-items-center">
                            <span class="tg-header-contact-icon mr-10 d-none d-xl-block">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="40" height="40" rx="20" fill="{{ $isHome ? 'white' : 'currentColor' }}" fill-opacity="0.12" />
                                    <path d="M27.5 23.5l-3.06-1.31a1 1 0 00-1.16.29l-1.36 1.66a15.07 15.07 0 01-6.1-6.1l1.66-1.36a1 1 0 00.29-1.16L16.5 12.5a1 1 0 00-1.16-.58l-2.84.65A1 1 0 0011.75 13.6 16 16 0 0026.4 28.25a1 1 0 001.04-.75l.65-2.84a1 1 0 00-.59-1.16z" stroke="{{ $isHome ? '#fff' : 'currentColor' }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                                </svg>
                            </span>
                            <div class="tg-header-contact-number d-none d-xl-block">
                                <span>Chiamaci:</span>
                                <a href="tel:+391234567890">+39 123 456 7890</a>
                            </div>
                        </div>
                        <div class="tg-header-btn ml-20 d-none d-sm-block">
                            @auth
                                <a class="tg-btn-header" href="{{ route('bookings.my') }}">
                                    <span>
                                        <svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6.5 7C8.16 7 9.5 5.66 9.5 4S8.16 1 6.5 1 3.5 2.34 3.5 4s1.34 3 3 3zm0 1.5c-2 0-6 1-6 3v1h12v-1c0-2-4-3-6-3z" fill="currentColor"/>
                                        </svg>
                                    </span>
                                    Area Personale
                                </a>
                            @else
                                <a class="tg-btn-header" href="{{ route('login') }}">
                                    <span>
                                        <svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6.5 7C8.16 7 9.5 5.66 9.5 4S8.16 1 6.5 1 3.5 2.34 3.5 4s1.34 3 3 3zm0 1.5c-2 0-6 1-6 3v1h12v-1c0-2-4-3-6-3z" fill="currentColor"/>
                                        </svg>
                                    </span>
                                    Accedi
                                </a>
                            @endauth
                        </div>
                        <div class="tg-header-menu-bar p-relative">
                            <button type="button" class="tgmenu-offcanvas-open-btn mobile-nav-toggler d-block d-xl-none ml-10"
                                    data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                                <span></span>
                                <span></span>
                                <span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- Mobile offcanvas menu --}}
<div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="background:#0f172a">
    <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
        <a href="{{ route('home') }}" class="d-inline-block">
            <img src="{{ asset('images/logo_white.svg') }}" alt="Solarya Travel" style="height:32px">
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Chiudi"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="list-unstyled fs-5 d-flex flex-column gap-3">
            <li><a href="{{ route('home') }}" class="text-white text-decoration-none">Home</a></li>
            <li><a href="{{ route('tours.index') }}" class="text-white text-decoration-none">Catamarani</a></li>
            <li><a href="{{ route('experiences') }}" class="text-white text-decoration-none">Esperienze</a></li>
            <li><a href="{{ route('about') }}" class="text-white text-decoration-none">Chi Siamo</a></li>
            <li><a href="{{ route('contact') }}" class="text-white text-decoration-none">Contatti</a></li>
        </ul>
        <hr class="border-secondary border-opacity-25 my-4">
        <div class="d-flex flex-column gap-2">
            @auth
                <a href="{{ route('bookings.my') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-user me-2"></i>Le mie prenotazioni
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-user me-2"></i>Accedi
                </a>
            @endauth
            <a href="{{ route('booking.start') }}" class="btn rounded-pill" style="background:var(--tg-theme-primary);color:#fff">
                <i class="fa-solid fa-calendar-check me-2"></i>Prenota Ora
            </a>
        </div>
        <hr class="border-secondary border-opacity-25 my-4">
        <div class="small text-white-50">
            <div class="mb-2"><i class="fa-solid fa-phone me-2"></i><a href="tel:+391234567890" class="text-white-50 text-decoration-none">+39 123 456 7890</a></div>
            <div><i class="fa-solid fa-envelope me-2"></i><a href="mailto:info@solaryatravel.com" class="text-white-50 text-decoration-none">info@solaryatravel.com</a></div>
        </div>
    </div>
</div>
