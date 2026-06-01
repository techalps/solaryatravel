@extends('layouts.public')

@section('title', 'Solarya Travel — Escursioni in Catamarano')
@section('meta_description', 'Solarya Travel: escursioni esclusive in catamarano in Sardegna. Comfort, eleganza e mare cristallino in ogni viaggio.')

@push('head')
<style>
    .tg-hero-area > .tg-hero-bg {
        position: relative;
        width: 100%;
        height: 740px;
        overflow: hidden;
    }
    @media (max-width: 575px) {
        .tg-hero-area > .tg-hero-bg { height: 650px; }
    }
    .tg-hero-video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    /* About images: smooth parallax transition (desktop) */
    [data-about-img] {
        transition: transform 0.12s ease-out;
        will-change: transform;
    }
</style>
@endpush

@section('content')

    {{-- ============= BANNER (video di sfondo in loop + svg shapes laterali) ============= --}}
    <div class="tg-hero-area fix p-relative">
        <div class="tg-hero-top-shadow"></div>
        <div class="tg-hero-bg">
            <video class="tg-hero-video" autoplay muted loop playsinline preload="auto"
                   poster="{{ asset('assets/template/img/hero/hero-1.jpg') }}">
                <source src="{{ asset('assets/template/video/hero.mp4') }}" type="video/mp4">
            </video>
        </div>

        <div class="tg-hero-content-area">
            <div class="container">
                <div class="p-relative">
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="tg-hero-content text-center">
                                <div class="tg-hero-title-box mb-10">
                                    <h5 class="tg-hero-subtitle mb-5 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".7s">Solarya</h5>
                                    <h1 class="tg-hero-title wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Your Ticket to the Next Experience<br><span style="font-size: 80%;">Escursioni in Catamarano in Sardegna</span></h1>
                                    <p class="tg-hero-para mb-0 wow fadeInUp" data-wow-delay=".6s" data-wow-duration="1.1s">
                                        Solarya è il tuo biglietto per il mare della Sardegna. <br>Escursioni in catamarano lungo la costa nord-est:<br>Daily Cruise, Sunset Cruise, calette nascoste e momenti indimenticabili.
                                    </p>
                                </div>
                                <div class="tg-hero-btn-box wow fadeInUp" data-wow-delay=".8s" data-wow-duration="1.5s">
                                    <a href="{{ route('tours.index') }}" class="tg-btn tg-btn-switch-animation">
                                        <span class="tg-btn-text">Scopri i Tour</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tg-hero-bottom-shape d-none d-md-block">
            <span>
                <svg width="432" height="298" viewBox="0 0 432 298" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="line-1" opacity="1" d="M39.6062 428.345C4.4143 355.065 -24.2999 203.867 142.379 185.309C350.726 162.111 488.895 393.541 289.171 313.515C129.391 249.494 458.204 85.4772 642.582 11.4713" stroke="#ff7d2c" stroke-width="24"/>
                </svg>
            </span>
        </div>
        <div class="tg-hero-bottom-shape-2 d-none d-md-block">
            <span>
                <svg width="154" height="321" viewBox="0 0 154 321" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="line-1" opacity="1" d="M144.616 328.905C116.117 300.508 62.5986 230.961 76.5162 179.949C93.9132 116.184 275.231 7.44493 -65.0181 12.8762" stroke="#ff7d2c" stroke-width="24"/>
                </svg>
            </span>
        </div>
    </div>

    {{-- ============= BANNER FORM (search box stile template) ============= --}}
    <div class="tg-booking-form-space pb-105">
        @include('partials.public.tour-search')
    </div>
    {{-- ============= ABOUT (3 col: thumb sx, content centro, thumb dx) ============= --}}
    <div class="tg-about-area pb-100">
        <div class="container">
            <div class="row">
                {{-- Colonna sinistra: visibile solo desktop --}}
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="tg-about-thumb-wrap mb-30">
                        <div class="tg-round-15 mb-0 wow fadeInLeft" data-about-img="1" style="background: url({{ asset('assets/template/img/about/about-1.jpg') }}) no-repeat; background-size: cover; width: 250px; height: 250px; border-radius: 50%; border: 9px solid #ff7d2c;"></div>
                        <div class="tg-about-thumb-2 tg-round-15 wow fadeInLeft" data-about-img="2" style="background: url({{ asset('assets/template/img/about/about-2.jpg') }}) no-repeat; background-size: cover; width: 220px; height: 220px; border-radius: 50%; border: 9px solid #ff7d2c;"></div>
                    </div>
                </div>
                <div class="col-lg-6 mb-30 mt-lg-50">
                    {{-- Immagine sopra il testo: solo mobile --}}
                    <div class="d-block d-lg-none mb-3" style="background: url({{ asset('assets/template/img/about/about-1.jpg') }}) center/cover no-repeat; height: 250px; border-radius: 12px;"></div>
                    <div class="tg-about-content text-center">
                        <div class="tg-about-section-title mb-25">
                            <h4 class="tg-section-subtitle wow fadeInUp">Solarya</h4>
                            <h2 class="mb-15 wow fadeInUp">Sei stanco delle solite mete affollate? Con Solarya Travel vivi il mare in modo diverso: relax, comfort e itinerari su misura.</h2>
                            <p class="wow fadeInUp">
                                Solarya Travel è il tuo partner per escursioni in catamarano in Sardegna.
                                Equipaggi qualificati, comfort di bordo e itinerari curati nel dettaglio per
                                regalarti emozioni autentiche e memorie indelebili.
                            </p>
                        </div>
                        <div class="tp-about-btn-wrap wow fadeInUp">
                            <a href="{{ route('tours.index') }}" class="tg-btn tg-btn-transparent tg-btn-switch-animation">
                                <span class="tg-btn-text">Scopri i tour</span>
                            </a>
                        </div>
                    </div>
                    {{-- Immagine sotto il testo: solo mobile --}}
                    <div class="d-block d-lg-none mt-3" style="background: url({{ asset('assets/template/img/about/about-3.jpg') }}) center/cover no-repeat; height: 250px; border-radius: 12px;"></div>
                </div>
                {{-- Colonna destra: visibile solo desktop --}}
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="tg-about-thumb-wrap mb-30">
                        <div class="w-100 mb-20 wow fadeInRight" data-about-img="3" style="background: url({{ asset('assets/template/img/about/about-3.jpg') }}) no-repeat; background-size: cover; width: 290px; height: 290px; border-radius: 50%; border: 9px solid #ff7d2c;"></div>
                        <div class="tg-about-thumb-4 tg-round-15 wow fadeInRight" data-about-img="4" style="background: url({{ asset('assets/template/img/about/about-4.jpg') }}) no-repeat; background-size: cover; width: 220px; height: 220px; border-radius: 50%; border: 9px solid #ff7d2c;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= LISTING (tours) ============= --}}
    <div class="tg-listing-area tg-grey-bg pt-140 pb-110 p-relative z-index-9">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="tg-listing-section-title text-center mb-35">
                        <h5 class="tg-section-subtitle wow fadeInUp">I nostri tour</h5>
                        <h2 class="mb-15 wow fadeInUp">Esperienze indimenticabili ti aspettano</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                @forelse($tours as $i => $tour)
                    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6">
                        <div class="tg-listing-card-item mb-30">
                            <div class="tg-listing-card-thumb fix mb-15 p-relative">
                                <a href="{{ route('tours.show', $tour->slug) }}">
                                    @if($tour->primaryImage)
                                        <img class="tg-card-border w-100" src="{{ $tour->primaryImage->url }}" alt="{{ $tour->name }}">
                                    @else
                                        <img class="tg-card-border w-100" src="{{ asset('assets/template/img/hero/hero-'.(($i % 5) + 1).'.jpg') }}" alt="{{ $tour->name }}">
                                    @endif
                                </a>
                            </div>
                            <div class="tg-listing-card-content">
                                <h4 class="tg-listing-card-title"><a href="{{ route('tours.show', $tour->slug) }}">{{ $tour->name }}</a></h4>
                                <div class="tg-listing-card-duration-tour">
                                    <span class="tg-listing-card-duration-map mb-5">
                                        <i class="fa-solid fa-location-dot me-1"></i> {{ $tour->departure_point ?? '' }}
                                    </span>
                                    @if($tour->duration_hours)
                                        <span class="tg-listing-card-duration-time">
                                            <i class="fa-regular fa-clock me-1"></i> {{ $tour->duration_hours }}h
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="tg-listing-card-price d-flex align-items-center justify-content-between">
                                <div class="tg-listing-card-price-wrap price-bg d-flex align-items-center">
                                    <span class="tg-listing-card-currency-amount mr-5">
                                        <span class="currency-symbol">€</span>{{ number_format($tour->price_from ?? 0, 0, ',', '.') }}
                                    </span>
                                    <span class="tg-listing-card-activity-person">/Persona</span>
                                </div>
                                <a href="{{ route('tours.show', $tour->slug) }}" class="tg-card-tour-link" style="margin-right:24px">
                                    Scopri <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Nessun tour disponibile al momento.</p>
                    </div>
                @endforelse
                <div class="col-12 text-center mt-15">
                    <a href="{{ route('tours.index') }}" class="tg-btn tg-btn-transparent tg-btn-switch-animation">
                        <span class="tg-btn-text">Vedi tutti i tour</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= CHOOSE ============= --}}
    <div class="tg-chose-area p-relative pt-135 pb-100">
        <img class="tg-chose-shape p-absolute" src="{{ asset('assets/template/img/chose/chose-shape-2.png') }}" alt="shape">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="tg-chose-content mb-25">
                        <div class="tg-chose-section-title mb-30">
                            <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Pianifica la tua avventura</h5>
                            <h2 class="mb-15 wow fadeInUp">Scopri quando vuoi <br>partire con noi</h2>
                            <p class="wow fadeInUp">
                                Sei stanco delle solite mete affollate? Con Solarya Travel vivi il mare in modo diverso: relax, comfort e itinerari su misura.
                            </p>
                        </div>
                        <div class="tg-chose-list-wrap">
                            <div class="tg-chose-list d-flex mb-15 wow fadeInUp">
                                <span class="tg-chose-list-icon mr-20">
                                    <span style="display:inline-flex;width:64px;height:64px;align-items:center;justify-content:center;border-radius:50%;background:color-mix(in srgb, var(--tg-theme-primary) 10%, #fff);color:var(--tg-theme-primary);font-size:26px">
                                        <i class="fa-solid fa-sailboat"></i>
                                    </span>
                                </span>
                                <div class="tg-chose-list-content">
                                    <h4 class="tg-chose-list-title mb-5">Equipaggio esperto</h4>
                                    <p>Skipper professionali con anni di esperienza in mare.</p>
                                </div>
                            </div>
                            <div class="tg-chose-list d-flex mb-40 wow fadeInUp">
                                <span class="tg-chose-list-icon mr-20">
                                    <span style="display:inline-flex;width:64px;height:64px;align-items:center;justify-content:center;border-radius:50%;background:color-mix(in srgb, var(--tg-theme-primary) 10%, #fff);color:var(--tg-theme-primary);font-size:26px">
                                        <i class="fa-solid fa-shield-alt"></i>
                                    </span>
                                </span>
                                <div class="tg-chose-list-content">
                                    <h4 class="tg-chose-list-title mb-5">Prenotazione sicura</h4>
                                    <p>Pagamento online protetto e cancellazione gratuita fino a 24h prima.</p>
                                </div>
                            </div>
                            <div class="tg-chose-btn wow fadeInUp">
                                <a href="{{ route('booking.start') }}" class="tg-btn tg-btn-switch-animation">
                                    <span class="tg-btn-text">Prenota ora</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="tg-chose-right mb-25">
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <div class="tg-chose-thumb">
                                    <img class="tg-chose-shape-2 mb-30 ml-15 d-none d-lg-block" src="{{ asset('assets/template/img/chose/chose-shape.png') }}" alt="shape">
                                    <img class="w-100 wow fadeInRight" src="{{ asset('assets/template/img/chose/chose.png') }}" alt="chose">
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-6">
                                <div class="tg-chose-thumb-inner p-relative">
                                    <div class="tg-chose-thumb-2 wow fadeInRight">
                                        <img class="w-100 tg-round-15" src="{{ asset('assets/template/img/chose/chose-2.jpg') }}" alt="chose">
                                    </div>
                                    <div class="tg-chose-big-text d-none d-xl-block">
                                        <h2 data-text="MARE">MARE</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= CTA video + discount ============= --}}
    <!-- <div class="tg-banner-area tg-banner-space">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="tg-cta-wrap include-bg" style="background-image: url('{{ asset('assets/template/img/cta/banner.jpg') }}')">
                        <div class="row align-items-end">
                            <div class="col-lg-3 d-none d-lg-block">
                                <div class="tg-cta-thumb pt-50 ml-60">
                                    <img src="{{ asset('assets/template/img/cta/phone.png') }}" alt="">
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-6">
                                <div class="tg-cta-content">
                                    <h5 class="tg-section-subtitle text-white mb-10">Pronto a salpare?</h5>
                                    <h2 class="mb-15 tg-cta-title text-white text-none">
                                        Prenota oggi<br>la tua escursione di lusso!
                                    </h2>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="tg-cta-apps d-flex flex-wrap gap-3">
                                    <a class="mb-20 d-inline-flex align-items-center px-4 py-3 rounded-3 text-white text-decoration-none fw-semibold" href="{{ route('booking.start') }}" style="background:var(--tg-theme-primary)">
                                        <i class="fa-solid fa-calendar-check me-2"></i> Prenota ora
                                    </a>
                                    <a class="mb-20 d-inline-flex align-items-center px-4 py-3 rounded-3 text-white text-decoration-none fw-semibold border border-white" href="tel:+391234567890">
                                        <i class="fa-solid fa-phone me-2"></i> Contattaci
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <span class="tg-banner-transparent-bg"></span> -->

    {{-- ============= CTA finale ============= --}}
    

    {{-- ============= LOCATION (4 destinazioni) ============= --}}
    @php
        $locations = [
            ['title' => 'Capri',     'tours' => '08', 'img' => 'location.jpg'],
            ['title' => 'Positano',  'tours' => '06', 'img' => 'location-2.jpg'],
            ['title' => 'Amalfi',    'tours' => '07', 'img' => 'location-3.jpg'],
            ['title' => 'Ischia',    'tours' => '05', 'img' => 'location-4.jpg'],
        ];
    @endphp
   <!-- <div class="tg-location-area p-relative pb-40 tg-grey-bg pt-140">
        
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tg-location-section-title text-center mb-30">
                        <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Le nostre mete</h5>
                        <h2 class="mb-15 text-none wow fadeInUp">Le destinazioni più amate <br>della Costiera</h2>
                        <p class="text-none wow fadeInUp">
                            Lasciati guidare dalle bellezze del Mediterraneo:<br>
                            ogni meta è un'esperienza unica da vivere.
                        </p>
                    </div>
                </div>
                @foreach($locations as $loc)
                    <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp">
                        <div class="bg-white tg-round-25 p-relative z-index-1">
                            <div class="tg-location-wrap p-relative mb-30">
                                <div class="tg-location-thumb">
                                    <img class="w-100" src="{{ asset('assets/template/img/location/'.$loc['img']) }}" alt="{{ $loc['title'] }}">
                                </div>
                                <div class="tg-location-content text-center">
                                    <span class="tg-location-time">{{ $loc['tours'] }} Tour</span>
                                    <h3 class="tg-location-title mb-0"><a href="{{ route('tours.index') }}">{{ $loc['title'] }}</a></h3>
                                </div>
                                <div class="tg-location-border one"></div>
                                <div class="tg-location-border two"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div> -->

    {{-- ============= CTA TWO (banner full-width) ============= --}}
    <div class="tg-banner-area include-bg" style="background-image: linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.38)), url('{{ asset('assets/template/img/banner/banner.jpg') }}')">
        <div class="container">
            <div class="col-lg-12">
                <div class="tg-banner-2-content text-center">
                    <div class="tg-about-section-title mb-25">
                        <h5 class="tg-section-subtitle mb-10 wow fadeInUp">La tua prossima avventura</h5>
                        <h3 class="tg-section-title-white mb-25 wow fadeInUp">Il tuo biglietto ti sta aspettando, vivi con noi la tua prossima indimenticabile avventura!</h3>
                    </div>
                    <div class="tp-banner-btn-wrap wow fadeInUp">
                        <a href="{{ route('booking.start') }}" class="tg-btn tg-btn-transparent tg-btn-switch-animation">
                            <span class="tg-btn-text">Prenota ora</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="tg-banner-bottom">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="tg-banner-2-big-title text-center wow fadeInUp">
                                <h2>Solarya</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= TESTIMONIAL (swiper card stile home-three) ============= --}}
    @php
        $testiList = collect($testimonials ?? [])->take(6);
        if ($testiList->isEmpty()) {
            $testiList = collect([
                ['name' => 'Sofia Bianchi', 'designation' => 'Milano',  'rating' => 5, 'text' => 'Esperienza indimenticabile lungo la Costiera. Equipaggio impeccabile e catamarano spettacolare!'],
                ['name' => 'Luca Romano',   'designation' => 'Roma',    'rating' => 5, 'text' => 'Il giro per Capri è stato magico. Solarya ha curato ogni dettaglio, lo consiglio a tutti.'],
                ['name' => 'Anna Greco',    'designation' => 'Napoli',  'rating' => 5, 'text' => 'Servizio top, prezzo onesto, mare stupendo. Torneremo sicuramente la prossima estate.'],
                ['name' => 'Marco Russo',   'designation' => 'Salerno', 'rating' => 5, 'text' => 'Tramonto in catamarano da sogno. Cibo eccellente e crew super attento.'],
            ]);
        }
    @endphp
    <!-- <div class="tg-testimonial-area pt-105 pb-100">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tg-location-section-title text-center mb-30">
                        <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Cosa dicono di noi</h5>
                        <h2 class="mb-15 text-none wow fadeInUp">Le parole dei nostri ospiti</h2>
                        <p class="text-none wow fadeInUp">
                            Recensioni autentiche di chi ha già scelto Solarya<br>
                            per vivere il mare in modo speciale.
                        </p>
                    </div>
                </div>
                <div class="swiper swiper-container tg-testimonial-slider fix" id="testiSwiper">
                    <div class="swiper-wrapper">
                        @foreach($testiList as $i => $t)
                            <div class="swiper-slide">
                                <div class="tg-testimonial-item mb-30">
                                    <div class="tg-testimonial-avatar-top d-flex align-items-start justify-content-between">
                                        <div class="tg-testimonial-avatar-inner d-flex align-items-center mr-20 mb-20">
                                            <div class="tg-testimonial-avatar-thumb mr-15">
                                                <img class="rounded-circle" src="{{ asset('assets/template/img/testimonial/tes-4/tes-'.((($i % 4) + 1)).'.png') }}" alt="avatar" width="60" height="60">
                                            </div>
                                            <div class="tg-testimonial-avatar-content">
                                                <h5>{{ $t['name'] }}</h5>
                                                <span>{{ $t['designation'] ?? ($t['location'] ?? '') }}</span>
                                            </div>
                                        </div>
                                        <div class="tg-testimonial-avatar-qoute">
                                            <span>
                                                <svg width="44" height="34" viewBox="0 0 44 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1.23438 33.5V28.4177L2.53846 28.1579L2.53874 28.1578C5.54288 27.5574 7.72911 26.3475 8.91074 24.4843L8.91078 24.4843L8.91406 24.479C9.51081 23.5115 9.85009 22.4074 9.89965 21.2718L9.92242 20.75H9.40013H2.85938C2.4284 20.75 2.01507 20.5788 1.71033 20.274C1.40558 19.9693 1.23438 19.556 1.23438 19.125V4.25C1.23438 2.18227 2.91664 0.5 4.98438 0.5H17.7344C18.1654 0.5 18.5787 0.671205 18.8834 0.975951C19.1882 1.2807 19.3594 1.69402 19.3594 2.125V19.125V19.1745L19.364 19.1976C19.3646 19.2056 19.3653 19.2163 19.3661 19.2296C19.3684 19.2694 19.3713 19.3294 19.3734 19.4081C19.3776 19.5653 19.3788 19.795 19.3678 20.0841C19.3458 20.6626 19.275 21.4756 19.0821 22.419C18.696 24.3079 17.8253 26.7003 15.8985 28.7905L1.23438 33.5ZM24.6243 33.5V28.4177L25.9283 28.1579L25.9286 28.1578C28.9328 27.5574 31.119 26.3475 32.3006 24.4843L32.3007 24.4843L32.3039 24.479C32.9007 23.5115 33.24 22.4074 33.2895 21.2718L33.3123 20.75H32.79H26.2493C25.8183 20.75 25.4049 20.5788 25.1002 20.274C24.7955 19.9693 24.6243 19.556 24.6243 19.125V4.25C24.6243 2.18227 26.3065 0.5 28.3743 0.5H41.1242C41.5552 0.5 41.9686 0.671206 42.2733 0.975951C42.578 1.2807 42.7492 1.69402 42.7492 2.125V19.125V19.1745L42.7538 19.1975C42.7544 19.2056 42.7551 19.2162 42.7559 19.2294C42.7583 19.2692 42.7611 19.3292 42.7631 19.4079C42.7673 19.5651 42.7685 19.7949 42.7574 20.084C42.7353 20.6625 42.6642 21.4755 42.4712 22.419C42.0848 24.3079 41.2141 26.7003 39.2884 28.7905C36.4311 31.8881 32.0738 33.5 26.2493 33.5H24.6243ZM19.3623 19.1774C19.3622 19.176 19.3623 19.1773 19.3628 19.1823C19.3625 19.1799 19.3623 19.1782 19.3623 19.1774Z" stroke="#D1D1D1"/>
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="tg-testimonial-avatar-para mb-10">"{{ $t['text'] }}"</p>
                                    <div class="tg-testimonial-ratings">
                                        @for($s = 0; $s < ($t['rating'] ?? 5); $s++)
                                            <span><i class="fa-sharp fa-solid fa-star"></i></span>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    {{-- ============= BLOG ============= --}}
    @php
        $blogMain = ['img' => 'blog-1.jpg', 'tag' => 'Costiera', 'title' => 'Le 7 baie più belle da scoprire in catamarano', 'date' => '12 Set 2024', 'time' => '5 min'];
        $blogSide = [
            ['img' => 'blog-2.jpg', 'tag' => 'Esperienze', 'title' => 'Tramonti in mare: i nostri itinerari preferiti', 'date' => '03 Ago 2024', 'time' => '4 min'],
            ['img' => 'blog-3.jpg', 'tag' => 'Consigli',   'title' => 'Cosa portare a bordo: la guida definitiva',   'date' => '21 Lug 2024', 'time' => '6 min'],
        ];
    @endphp
    <!-- <div class="tg-blog-area tg-blog-space tg-grey-bg pt-135 p-relative z-index-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tg-location-section-title text-center mb-30">
                        <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Blog &amp; Consigli</h5>
                        <h2 class="mb-15 text-none wow fadeInUp">Ultime news dal blog</h2>
                        <p class="text-none wow fadeInUp">
                            Itinerari, consigli e curiosità per vivere il mare<br>
                            come un vero esperto della Costiera.
                        </p>
                    </div>
                </div>
                <div class="col-lg-5 wow fadeInLeft">
                    <div class="tg-blog-item mb-25">
                        <div class="tg-blog-thumb fix">
                            <a href="#"><img class="w-100" src="{{ asset('assets/template/img/blog/'.$blogMain['img']) }}" alt="blog"></a>
                        </div>
                        <div class="tg-blog-content p-relative">
                            <span class="tg-blog-tag p-absolute">{{ $blogMain['tag'] }}</span>
                            <h3 class="tg-blog-title"><a href="#">{{ $blogMain['title'] }}</a></h3>
                            <div class="tg-blog-date">
                                <span class="mr-20"><i class="fa-light fa-calendar"></i> {{ $blogMain['date'] }}</span>
                                <span><i class="fa-regular fa-clock"></i> {{ $blogMain['time'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="row">
                        @foreach($blogSide as $b)
                            <div class="col-12 wow fadeInRight">
                                <div class="tg-blog-item mb-20">
                                    <div class="row align-items-center">
                                        <div class="col-lg-5">
                                            <div class="tg-blog-thumb fix">
                                                <a href="#"><img class="w-100" src="{{ asset('assets/template/img/blog/'.$b['img']) }}" alt="blog"></a>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <div class="tg-blog-contents">
                                                <span class="tg-blog-tag d-inline-block mb-10">{{ $b['tag'] }}</span>
                                                <h3 class="tg-blog-title title-2 mb-0"><a href="#">{{ $b['title'] }}</a></h3>
                                                <div class="tg-blog-date">
                                                    <span class="mr-20"><i class="fa-light fa-calendar"></i> {{ $b['date'] }}</span>
                                                    <span><i class="fa-regular fa-clock"></i> {{ $b['time'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 wow fadeInUp">
                    <div class="tg-blog-bottom text-center pt-25">
                        <p>Vuoi scoprire altri articoli? <a href="#">Visita il blog</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    {{-- ============= CTA finale ============= --}}
    <div class="tg-cta-area tg-cta-su-wrapper tg-cta-space z-index-9 p-relative">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="tg-cta-wrap include-bg" style="background-image: url('{{ asset('assets/template/img/cta/banner.jpg') }}')">
                        <div class="row align-items-end">
                            <div class="col-lg-3 d-none d-lg-block">
                                <div class="tg-cta-thumb pt-50 ml-60">
                                    <img src="{{ asset('assets/template/img/cta/phone.png') }}" alt="">
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-6">
                                <div class="tg-cta-content">
                                    <h5 class="tg-section-subtitle text-white mb-10">Pronto a salpare?</h5>
                                    <h2 class="mb-15 tg-cta-title text-white text-capitalize">
                                        Prenota oggi<br>la tua escursione di lusso!
                                    </h2>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="tg-cta-apps d-flex flex-wrap gap-3">
                                    <a class="mb-20 d-inline-flex align-items-center px-4 py-3 rounded-3 text-white text-decoration-none fw-semibold" href="{{ route('booking.start') }}" style="background:#7C37FF">
                                        <i class="fa-solid fa-calendar-check me-2"></i> Prenota ora
                                    </a>
                                    <a class="mb-20 d-inline-flex align-items-center px-4 py-3 rounded-3 text-white text-decoration-none fw-semibold border border-white" href="tel:+391234567890">
                                        <i class="fa-solid fa-phone me-2"></i> Contattaci
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // About images parallax on mouse move (desktop only)
    (function () {
        const section = document.querySelector('.tg-about-area');
        if (!section) return;
        const imgs = section.querySelectorAll('[data-about-img]');
        if (!imgs.length) return;

        // [x factor, y factor] — segni opposti per creare profondità
        const factors = { '1': [-0.018, -0.014], '2': [0.024, 0.018], '3': [0.018, -0.014], '4': [-0.024, 0.018] };

        let rafId = null;
        let mx = 0, my = 0;

        window.addEventListener('mousemove', function (e) {
            if (window.innerWidth < 992) return;
            const rect = section.getBoundingClientRect();
            mx = e.clientX - (rect.left + rect.width / 2);
            my = e.clientY - (rect.top + rect.height / 2);
            if (!rafId) {
                rafId = requestAnimationFrame(function () {
                    imgs.forEach(function (img) {
                        const f = factors[img.dataset.aboutImg];
                        if (!f) return;
                        img.style.transform = 'translate(' + (mx * f[0]).toFixed(2) + 'px, ' + (my * f[1]).toFixed(2) + 'px)';
                    });
                    rafId = null;
                });
            }
        });

        section.addEventListener('mouseleave', function () {
            imgs.forEach(function (img) { img.style.transform = 'translate(0,0)'; });
        });
    })();

    if (typeof Swiper === 'undefined') return;

    // Testimonial slider
    if (document.getElementById('testiSwiper')) {
        new Swiper('#testiSwiper', {
            spaceBetween: 25,
            loop: true,
            speed: 500,
            autoplay: { delay: 4000, disableOnInteraction: false },
            breakpoints: {
                1200: { slidesPerView: 3 },
                992:  { slidesPerView: 2 },
                768:  { slidesPerView: 2 },
                576:  { slidesPerView: 1 },
                0:    { slidesPerView: 1 },
            },
        });
    }
});
</script>
@endpush
