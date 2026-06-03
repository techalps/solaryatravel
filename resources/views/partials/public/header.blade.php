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
                                        <a href="{{ route('tours.index') }}" class="{{ request()->routeIs('tours.*') ? 'active' : '' }}">Tour</a>
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
                                    <rect width="40" height="40" rx="20" fill="{{ $isHome ? '#fff' : 'var(--tg-theme-primary)' }}" fill-opacity="0.12" />
                                    <path d="M26.6663 23.36V25.36C26.6671 25.5457 26.6291 25.7294 26.5546 25.8995C26.4801 26.0696 26.3709 26.2223 26.2338 26.3478C26.0968 26.4733 25.9349 26.5689 25.7588 26.6283C25.5826 26.6878 25.396 26.7099 25.2108 26.6933C23.1592 26.4704 21.1884 25.7693 19.4575 24.6467C17.8471 23.6231 16.4819 22.2579 15.4583 20.6475C14.3319 18.9087 13.6307 16.9285 13.4117 14.8675C13.395 14.6829 13.417 14.4968 13.476 14.3211C13.5351 14.1453 13.6301 13.9838 13.755 13.8468C13.8798 13.7098 14.0319 13.6006 14.2014 13.5258C14.3709 13.4511 14.5541 13.4124 14.7392 13.4133H16.7392C17.0628 13.4101 17.3765 13.5247 17.6218 13.7357C17.8672 13.9467 18.0275 14.2399 18.0728 14.5603C18.1573 15.2003 18.314 15.8287 18.5398 16.4333C18.6296 16.6722 18.649 16.9319 18.5958 17.1815C18.5426 17.4312 18.419 17.6603 18.2398 17.8417L17.3932 18.6883C18.3423 20.3578 19.7252 21.7407 21.3947 22.6898L22.2413 21.8432C22.4227 21.664 22.6518 21.5404 22.9015 21.4872C23.1511 21.434 23.4108 21.4534 23.6497 21.5432C24.2543 21.769 24.8827 21.9257 25.5227 22.0102C25.8466 22.0559 26.1425 22.219 26.354 22.4686C26.5654 22.7181 26.6779 23.037 26.6663 23.36Z" stroke="{{ $isHome ? '#fff' : 'var(--tg-theme-primary)' }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                                </svg>
                            </span>
                            <div class="tg-header-contact-number d-none d-xl-block">
                                <span>Chiamaci:</span>
                                <a href="tel:+393450884743">+39 345 088 4743</a>
                            </div>
                        </div>
                        <div class="tg-header-btn ml-20 d-none d-sm-block">
                            @auth
                                <div class="dropdown">
                                    <button class="tg-btn-header dropdown-toggle d-flex align-items-center gap-2"
                                            type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                            style="border:0;cursor:pointer">
                                        <span class="tg-user-avatar">
                                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                        </span>
                                        <span class="d-none d-lg-inline">{{ explode(' ', auth()->user()->name ?? '')[0] }}</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;border-radius:12px;padding:6px">
                                        <li>
                                            <div style="padding:10px 14px 6px">
                                                <div style="font-weight:700;font-size:.88rem;color:#0E1B33">{{ auth()->user()->name }}</div>
                                                <div style="font-size:.76rem;color:#64748b">{{ auth()->user()->email }}</div>
                                            </div>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2" href="{{ route('profile') }}">
                                                <i class="fa-regular fa-user" style="width:16px"></i>Il mio profilo
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2" href="{{ route('bookings.my') }}">
                                                <i class="fa-regular fa-calendar-check" style="width:16px"></i>Le mie prenotazioni
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2 text-danger">
                                                    <i class="fa-solid fa-arrow-right-from-bracket" style="width:16px"></i>Esci
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
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
            <li><a href="{{ route('tours.index') }}" class="text-white text-decoration-none">Tour</a></li>
        </ul>
        <hr class="border-secondary border-opacity-25 my-4">
        <div class="d-flex flex-column gap-2">
            @auth
                <a href="{{ route('profile') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-regular fa-user me-2"></i>Il mio profilo
                </a>
                <a href="{{ route('bookings.my') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-regular fa-calendar-check me-2"></i>Le mie prenotazioni
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-pill w-100">
                        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Esci
                    </button>
                </form>
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
            <div class="mb-2"><i class="fa-solid fa-phone me-2"></i><a href="tel:+393450884743" class="text-white-50 text-decoration-none">+39 345 088 4743</a></div>
            <div><i class="fa-solid fa-envelope me-2"></i><a href="mailto:info@solaryatravel.com" class="text-white-50 text-decoration-none">info@solaryatravel.com</a></div>
        </div>
    </div>
</div>
