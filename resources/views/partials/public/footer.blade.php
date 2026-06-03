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
                            <p class="mb-20">Esperienze esclusive in catamarano lungo
                                la Costiera. Comfort, eleganza e servizio
                                impeccabile per momenti indimenticabili in mare.</p>
                            <div class="tg-footer-social">
                                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                <a href="#" aria-label="X"><i class="fa-brands fa-twitter"></i></a>
                                <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                                <a href="https://wa.me/393450884743" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>
                            </div>
                        </div>
                    </div>

                    {{-- Quick links --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget tg-footer-link ml-80 mb-40">
                            <h3 class="tg-footer-widget-title mb-25">Link Rapidi</h3>
                            <ul>
                                <li><a href="{{ route('home') }}">Home</a></li>
                                <li><a href="{{ route('tours.index') }}">Tour</a></li>
                            </ul>
                        </div>
                    </div>

                    {{-- Legal --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget tg-footer-link mb-40">
                            <h3 class="tg-footer-widget-title mb-25">Informazioni</h3>
                            <ul>
                                <li><a href="{{ route('booking.start') }}">Prenota Online</a></li>
                                <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                                <li><a href="{{ route('terms') }}">Termini e Condizioni</a></li>
                                <li><a href="{{ route('cookies') }}">Cookie Policy</a></li>
                                <li><a href="#" class="open-cookie-settings">Preferenze cookie</a></li>
                                @auth
                                    <li><a href="{{ route('bookings.my') }}">Le mie prenotazioni</a></li>
                                @else
                                    <li><a href="{{ route('login') }}">Accedi</a></li>
                                @endauth
                            </ul>
                        </div>
                    </div>

                    {{-- Contacts --}}
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                        <div class="tg-footer-widget tg-footer-info mb-40">
                            <h3 class="tg-footer-widget-title mb-25">Contatti</h3>
                            <ul>
                                <li>
                                    <a class="d-flex" href="https://www.google.com/maps/search/?api=1&query=Via+Toscanini+9%2FC+07026+Olbia+SS" target="_blank" rel="noopener">
                                        <span class="mr-15">
                                            <svg width="20" height="24" viewBox="0 0 20 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M19.0013 10.0608C19.0013 16.8486 10.3346 22.6668 10.3346 22.6668C10.3346 22.6668 1.66797 16.8486 1.66797 10.0608C1.66797 7.74615 2.58106 5.52634 4.20638 3.88965C5.83169 2.25297 8.03609 1.3335 10.3346 1.3335C12.6332 1.3335 14.8376 2.25297 16.4629 3.88965C18.0882 5.52634 19.0013 7.74615 19.0013 10.0608Z" stroke="white" stroke-width="1.73333" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10.3346 12.9699C11.9301 12.9699 13.2235 11.6674 13.2235 10.0608C13.2235 8.45412 11.9301 7.15168 10.3346 7.15168C8.73915 7.15168 7.44575 8.45412 7.44575 10.0608C7.44575 11.6674 8.73915 12.9699 10.3346 12.9699Z" stroke="white" stroke-width="1.73333" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        Via Toscanini 9/C<br>07026 Olbia (SS)
                                    </a>
                                </li>
                                <li>
                                    <a class="d-flex" href="mailto:info@solaryatravel.com">
                                        <span class="mr-15"><i class="fa-sharp text-white fa-solid fa-envelope"></i></span>
                                        info@solaryatravel.com
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
                Copyright © {{ date('Y') }} <a href="{{ route('home') }}">Solarya Travel</a> | Tutti i diritti riservati
            </span>
        </div>
    </div>
</footer>
