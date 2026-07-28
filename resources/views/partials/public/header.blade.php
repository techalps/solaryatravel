{{-- Header One (template tu/su) --}}
@php
    // locale_route_is() è indifferente alla lingua: vero sia su / sia su /en.
    $isHome = locale_route_is('home');
@endphp
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
                                        <a href="{{ route('home') }}" class="{{ locale_route_is('home') ? 'active' : '' }}">{{ __('common.nav.home') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('tours.index') }}" class="{{ locale_route_is('tours.*') ? 'active' : '' }}">{{ __('common.nav.tours') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-4 col-7">
                    <div class="tg-menu-right-action d-flex align-items-center justify-content-end">
                        <div class="tg-header-contact-info d-flex align-items-center">
                            <a href="https://wa.me/393450884743" target="_blank" rel="noopener"
                               class="tg-header-contact-number d-none d-xl-flex align-items-center text-decoration-none"
                               aria-label="{{ __('common.nav.whatsapp') }}">
                                <i class="fa-brands fa-whatsapp me-2" style="font-size:1.4rem;color:{{ $isHome ? '#fff' : '#25D366' }}"></i>
                                <span>
                                    {{-- "Scrivici su WhatsApp" su due righe: la parte
                                         variabile è tutto tranne il nome del servizio. --}}
                                    <span style="display:block;font-size:.72rem;opacity:.8">{{ trim(str_replace('WhatsApp', '', __('common.nav.whatsapp'))) }}</span>
                                    <strong>WhatsApp</strong>
                                </span>
                            </a>
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
                                                <i class="fa-regular fa-user" style="width:16px"></i>{{ __('common.account.my_profile') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2" href="{{ route('bookings.my') }}">
                                                <i class="fa-regular fa-calendar-check" style="width:16px"></i>{{ __('common.account.my_bookings') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2 text-danger">
                                                    <i class="fa-solid fa-arrow-right-from-bracket" style="width:16px"></i>{{ __('common.account.logout') }}
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
                                    {{ __('common.nav.login') }}
                                </a>
                            @endauth
                        </div>

                        {{-- Selettore lingua: DOPO il bottone Accedi, così la
                             CTA resta l'elemento più a destra dopo il menu. --}}
                        @include('partials.public.language-switcher', ['variant' => 'inline', 'class' => 'd-none d-sm-inline-flex ml-10'])

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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('common.a11y.close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="list-unstyled fs-5 d-flex flex-column gap-3">
            <li><a href="{{ route('home') }}" class="text-white text-decoration-none">{{ __('common.nav.home') }}</a></li>
            <li><a href="{{ route('tours.index') }}" class="text-white text-decoration-none">{{ __('common.nav.tours') }}</a></li>
        </ul>
        <hr class="border-secondary border-opacity-25 my-4">
        <div class="d-flex flex-column gap-2">
            @auth
                <a href="{{ route('profile') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-regular fa-user me-2"></i>{{ __('common.account.my_profile') }}
                </a>
                <a href="{{ route('bookings.my') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-regular fa-calendar-check me-2"></i>{{ __('common.account.my_bookings') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-pill w-100">
                        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>{{ __('common.account.logout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-user me-2"></i>{{ __('common.nav.login') }}
                </a>
            @endauth
            <a href="{{ route('booking.start') }}" class="btn rounded-pill" style="background:var(--tg-theme-primary);color:#fff">
                <i class="fa-solid fa-calendar-check me-2"></i>{{ __('common.nav.book_now') }}
            </a>
        </div>
        <hr class="border-secondary border-opacity-25 my-4">

        {{-- Switcher di lingua (mobile) --}}
        @include('partials.public.language-switcher', ['variant' => 'stacked', 'class' => 'w-100'])

        <hr class="border-secondary border-opacity-25 my-4">
        <div class="small text-white-50">
            <div class="mb-2"><i class="fa-brands fa-whatsapp me-2"></i><a href="https://wa.me/393450884743" target="_blank" rel="noopener" class="text-white-50 text-decoration-none">{{ __('common.nav.whatsapp') }}</a></div>
            <div><i class="fa-solid fa-envelope me-2"></i><a href="mailto:{{ config('mail.from.address') }}" class="text-white-50 text-decoration-none">{{ config('mail.from.address') }}</a></div>
        </div>
    </div>
</div>
