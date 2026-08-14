{{-- Footer Five (template) --}}
<footer>
    <div class="tg-footer-area pt-130 include-bg" style="background-image: url('{{ asset('assets/template/img/footer/footer.jpg') }}')">
        <div class="container">
            <div class="tg-footer-top pb-40">
                <div class="row">
                    {{-- Brand & newsletter --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget mb-40">
                            <div class="tg-footer-logo mb-20">
                                <a href="{{ route('home') }}">
                                    <img src="{{ asset('images/logo_white.svg') }}" alt="Solarya Travel" style="height:42px;width:auto">
                                </a>
                            </div>
                            <p class="mb-20">{{ __('common.footer.tagline') }}</p>
                            <div class="tg-footer-social">
                                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                <a href="#" aria-label="X"><i class="fa-brands fa-twitter"></i></a>
                                <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                                @if ($waFooterLink = \App\Support\WhatsApp::businessLink())
                                    <a href="{{ $waFooterLink }}" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Quick links --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget tg-footer-link ml-80 mb-40">
                            <h3 class="tg-footer-widget-title mb-25">{{ __('common.footer.quick_links') }}</h3>
                            <ul>
                                <li><a href="{{ route('home') }}">{{ __('common.nav.home') }}</a></li>
                                <li><a href="{{ route('tours.index') }}">{{ __('common.nav.tours') }}</a></li>
                            </ul>
                            {{-- Switcher di lingua anche nel footer --}}
                            <div class="mt-20">
                                @include('partials.public.language-switcher', ['variant' => 'stacked', 'class' => ''])
                            </div>
                        </div>
                    </div>

                    {{-- Legal --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget tg-footer-link mb-40">
                            <h3 class="tg-footer-widget-title mb-25">{{ __('common.footer.information') }}</h3>
                            <ul>
                                <li><a href="{{ route('booking.start') }}">{{ __('common.footer.book_online') }}</a></li>
                                <li><a href="{{ route('privacy') }}">{{ __('common.footer.privacy') }}</a></li>
                                <li><a href="{{ route('terms') }}">{{ __('common.footer.terms') }}</a></li>
                                <li><a href="{{ route('cookies') }}">{{ __('common.footer.cookie_policy') }}</a></li>
                                <li><a href="#" class="open-cookie-settings">{{ __('common.footer.cookie_prefs') }}</a></li>
                                @auth
                                    <li><a href="{{ route('bookings.my') }}">{{ __('common.account.my_bookings') }}</a></li>
                                @else
                                    <li><a href="{{ route('login') }}">{{ __('common.nav.login') }}</a></li>
                                @endauth
                            </ul>
                        </div>
                    </div>

                    {{-- Contacts --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget tg-footer-info mb-40">
                            <h3 class="tg-footer-widget-title mb-25">{{ __('common.footer.contacts') }}</h3>
                            <ul>
                                <li>
                                    <a class="d-flex" href="https://www.google.com/maps/search/?api=1&query=Via+Toscanini+9%2FC+07026+Olbia+SS" target="_blank" rel="noopener">
                                        <span class="mr-15">
                                            <svg width="20" height="24" viewBox="0 0 20 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M19.0013 10.0608C19.0013 16.8486 10.3346 22.6668 10.3346 22.6668C10.3346 22.6668 1.66797 16.8486 1.66797 10.0608C1.66797 7.74615 2.58106 5.52634 4.20638 3.88965C5.83169 2.25297 8.03609 1.3335 10.3346 1.3335C12.6332 1.3335 14.8376 2.25297 16.4629 3.88965C18.0882 5.52634 19.0013 7.74615 19.0013 10.0608Z" stroke="white" stroke-width="1.73333" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10.3346 12.9699C11.9301 12.9699 13.2235 11.6674 13.2235 10.0608C13.2235 8.45412 11.9301 7.15168 10.3346 7.15168C8.73915 7.15168 7.44575 8.45412 7.44575 10.0608C7.44575 11.6674 8.73915 12.9699 10.3346 12.9699Z" stroke="white" stroke-width="1.73333" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        {{-- L'indirizzo è una stringa unica nei file di lingua
                                             (in EN include ", Italy"): la mandiamo a capo dopo
                                             il civico per conservare il layout su due righe. --}}
                                        {!! nl2br(e(preg_replace('/,?\s+(?=\d{5}\b)/u', "\n", __('common.footer.address')))) !!}
                                    </a>
                                </li>
                                <li>
                                    {{-- L'email è invariante: viene da config, non dai file di lingua. --}}
                                    <a class="d-flex" href="mailto:{{ config('mail.from.address') }}">
                                        <span class="mr-15"><i class="fa-sharp text-white fa-solid fa-envelope"></i></span>
                                        {{ config('mail.from.address') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tg-footer-copyright text-center">
            <span>
                {{-- Il nome del brand nella stringa tradotta diventa link alla home. --}}
                {!! str_replace(
                    'Solarya Travel',
                    '<a href="'.e(route('home')).'">Solarya Travel</a>',
                    e(__('common.footer.copyright', ['year' => date('Y')]))
                ) !!}
            </span>

            {{-- Credito di realizzazione: stessa riga del copyright, tono più
                 tenue. È un credito, non deve competere col brand Solarya.
                 Nome, URL e logo da config/site.php; nome vuoto = nascosto. --}}
            @if(config('site.vendor.name'))
                @php
                    $vendorName = config('site.vendor.name');
                    $vendorLogo = config('site.vendor.logo');
                    // Il logo è opzionale: se il file non c'è resta il nome
                    // testuale, così il credito non sparisce mai.
                    $hasVendorLogo = $vendorLogo && is_file(public_path($vendorLogo));

                    // "powered by :vendor" arriva dai file di lingua: spezziamo
                    // sul segnaposto per inserire il logo come vero markup,
                    // invece di concatenare HTML dentro la traduzione.
                    [$poweredBefore, $poweredAfter] = array_pad(
                        explode(':vendor', __('common.footer.powered_by'), 2), 2, ''
                    );
                @endphp
                <span class="tg-footer-credit">
                    {{ $poweredBefore }}<a href="{{ config('site.vendor.url') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="{{ $hasVendorLogo ? 'tg-footer-credit-logo' : '' }}">@if($hasVendorLogo)<img
                            src="{{ asset($vendorLogo) }}"
                            alt="{{ $vendorName }}"
                            width="{{ (int) config('site.vendor.logo_width', 150) }}"
                            height="{{ (int) config('site.vendor.logo_height', 34) }}"
                            loading="lazy"
                            decoding="async">@else{{ $vendorName }}@endif</a>{{ $poweredAfter }}
                </span>
            @endif
        </div>
    </div>
</footer>
