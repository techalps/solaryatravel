@extends('layouts.public')

@section('title', 'Solarya Travel — Escursioni in Catamarano')
@section('meta_description', 'Solarya Travel: vivi esperienze esclusive in catamarano lungo la Costiera Amalfitana. Comfort, eleganza e mare cristallino in ogni viaggio.')

@section('content')

    {{-- ============= BANNER (slideshow + svg shapes laterali) ============= --}}
    <div class="tg-hero-area fix p-relative">
        <div class="tg-hero-top-shadow"></div>
        <div class="shop-slider-wrapper">
            <div class="swiper swiper-container tg-hero-slider-active" id="heroSlider">
                <div class="swiper-wrapper">
                    @foreach(['hero-1.jpg','hero-2.jpg','hero-3.jpg','hero-4.jpg','hero-5.jpg'] as $heroImg)
                        <div class="swiper-slide">
                            <div class="tg-hero-bg">
                                <div class="tg-hero-thumb" style="background-image: url('{{ asset('assets/template/img/hero/'.$heroImg) }}')"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="tg-hero-content-area">
            <div class="container">
                <div class="p-relative">
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="tg-hero-content text-center">
                                <div class="tg-hero-title-box mb-10">
                                    <h5 class="tg-hero-subtitle mb-5 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".7s">* Vivi la magia della Costiera Amalfitana</h5>
                                    <h2 class="tg-hero-title wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Solarya Travel</h2>
                                    <p class="tg-hero-para mb-0 wow fadeInUp" data-wow-delay=".6s" data-wow-duration="1.1s">
                                        Escursioni esclusive in catamarano fra Capri, Positano,<br>
                                        Amalfi e le perle più belle del Mediterraneo.
                                    </p>
                                </div>
                                <div class="tg-hero-price-wrap mb-35 d-flex align-items-center justify-content-center wow fadeInUp" data-wow-delay=".7s" data-wow-duration="1.3s">
                                    <p class="mr-15">A partire da</p>
                                    <div class="tg-hero-price d-flex">
                                        <span class="hero-dolar">€</span>
                                        <span class="hero-price">{{ $minPrice ? number_format($minPrice, 0, ',', '.') : '40' }}</span>
                                        <span class="night">/persona</span>
                                    </div>
                                </div>
                                <div class="tg-hero-btn-box wow fadeInUp" data-wow-delay=".8s" data-wow-duration="1.5s">
                                    <a href="{{ route('tours.index') }}" class="tg-btn tg-btn-switch-animation">
                                        <span class="tg-btn-text">Scopri i Tour</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tg-hero-arrow-box d-none d-sm-block">
                        <button class="tg-hero-next" aria-label="Successivo">
                            <svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18.0274 7.5H0.972625M0.972625 7.5L7.25 1.22263M0.972625 7.5L7.25 13.7774" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button class="tg-hero-prev" aria-label="Precedente">
                            <svg width="20" height="15" viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.47263 7.5H18.5274M18.5274 7.5L12.25 1.22263M18.5274 7.5L12.25 13.7774" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="tg-hero-bottom-shape d-none d-md-block">
            <span>
                <svg width="432" height="298" viewBox="0 0 432 298" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="line-1" opacity="0.4" d="M39.6062 428.345C4.4143 355.065 -24.2999 203.867 142.379 185.309C350.726 162.111 488.895 393.541 289.171 313.515C129.391 249.494 458.204 85.4772 642.582 11.4713" stroke="white" stroke-width="24"/>
                </svg>
            </span>
        </div>
        <div class="tg-hero-bottom-shape-2 d-none d-md-block">
            <span>
                <svg width="154" height="321" viewBox="0 0 154 321" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="line-1" opacity="0.4" d="M144.616 328.905C116.117 300.508 62.5986 230.961 76.5162 179.949C93.9132 116.184 275.231 7.44493 -65.0181 12.8762" stroke="white" stroke-width="24"/>
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
                <div class="col-lg-3">
                    <div class="tg-about-thumb-wrap mb-30">
                        <img class="w-100 tg-round-15 mb-85 wow fadeInLeft" src="{{ asset('assets/template/img/about/about.jpg') }}" alt="about">
                        <img class="tg-about-thumb-2 tg-round-15 wow fadeInLeft" src="{{ asset('assets/template/img/about/about-2.jpg') }}" alt="about">
                    </div>
                </div>
                <div class="col-lg-6 mb-30">
                    <div class="tg-about-content text-center">
                        <div class="tg-about-logo mb-30 wow fadeInUp">
                            <img src="{{ asset('assets/template/img/about/logo.png') }}" alt="logo">
                        </div>
                        <div class="tg-about-section-title mb-25">
                            <h5 class="tg-section-subtitle wow fadeInUp">Chi Siamo</h5>
                            <h2 class="mb-15 wow fadeInUp">Scopri il mare con noi, vivi un'esperienza unica</h2>
                            <p class="text-capitalize wow fadeInUp">
                                Solarya Travel è il tuo partner per escursioni in catamarano lungo la Costiera Amalfitana.
                                Equipaggi qualificati, comfort di bordo e itinerari curati nel dettaglio per
                                regalarti emozioni autentiche e memorie indelebili.
                            </p>
                        </div>
                        <div class="tp-about-btn-wrap wow fadeInUp">
                            <a href="{{ route('about') }}" class="tg-btn tg-btn-transparent tg-btn-switch-animation">
                                <span class="tg-btn-text">Scopri di più</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="tg-about-thumb-wrap mb-30">
                        <img class="w-100 tg-round-15 mb-85 wow fadeInRight" src="{{ asset('assets/template/img/about/about-3.jpg') }}" alt="about">
                        <img class="tg-about-thumb-4 tg-round-15 wow fadeInRight" src="{{ asset('assets/template/img/about/about-4.jpg') }}" alt="about">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= LISTING (tours) ============= --}}
    <div class="tg-listing-area tg-grey-bg pt-140 pb-110 p-relative z-index-9">
        <img class="tg-listing-shape d-none d-lg-block" src="{{ asset('assets/template/img/listing/about-shape.png') }}" alt="">
        <img class="tg-listing-shape-2 d-none d-xl-block" src="{{ asset('assets/template/img/listing/about-shape-2.png') }}" alt="">
        <img class="tg-listing-shape-3 d-none d-lg-block" src="{{ asset('assets/template/img/listing/about-shape-3.png') }}" alt="">
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
                    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6">
                        <div class="tg-listing-card-item mb-30">
                            <div class="tg-listing-card-thumb fix mb-15 p-relative">
                                <a href="{{ route('tours.show', $tour->slug) }}">
                                    @if($tour->primaryImage)
                                        <img class="tg-card-border w-100" src="{{ \Illuminate\Support\Facades\Storage::url($tour->primaryImage->path) }}" alt="{{ $tour->name }}">
                                    @else
                                        <img class="tg-card-border w-100" src="{{ asset('assets/template/img/hero/hero-'.(($i % 5) + 1).'.jpg') }}" alt="{{ $tour->name }}">
                                    @endif
                                    @if($i === 0)
                                        <span class="tg-listing-item-price-discount shape">Top</span>
                                    @endif
                                </a>
                                <div class="tg-listing-item-wishlist">
                                    <a href="#" aria-label="Preferiti" style="cursor:pointer">
                                        <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10.5167 16.3416C10.2334 16.4416 9.76675 16.4416 9.48341 16.3416C7.06675 15.5166 1.66675 12.075 1.66675 6.24165C1.66675 3.66665 3.74175 1.58331 6.30008 1.58331C7.81675 1.58331 9.15841 2.31665 10.0001 3.44998C10.8417 2.31665 12.1917 1.58331 13.7001 1.58331C16.2584 1.58331 18.3334 3.66665 18.3334 6.24165C18.3334 12.075 12.9334 15.5166 10.5167 16.3416Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="tg-listing-card-content">
                                <h4 class="tg-listing-card-title"><a href="{{ route('tours.show', $tour->slug) }}">{{ $tour->name }}</a></h4>
                                <div class="tg-listing-card-duration-tour">
                                    <span class="tg-listing-card-duration-map mb-5">
                                        <i class="fa-solid fa-location-dot me-1"></i> {{ $tour->departure_point ?? 'Costiera Amalfitana' }}
                                    </span>
                                    @if($tour->duration_hours)
                                        <span class="tg-listing-card-duration-time">
                                            <i class="fa-regular fa-clock me-1"></i> {{ $tour->duration_hours }}h
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="tg-listing-card-price d-flex align-items-end justify-content-between">
                                <div class="tg-listing-card-price-wrap price-bg d-flex align-items-center">
                                    <span class="tg-listing-card-currency-amount mr-5">
                                        <span class="currency-symbol">€</span>{{ number_format($tour->price_from ?? 0, 0, ',', '.') }}
                                    </span>
                                    <span class="tg-listing-card-activity-person">/Persona</span>
                                </div>
                                <div class="tg-listing-card-review space">
                                    <span class="tg-listing-rating-icon"><i class="fa-sharp fa-solid fa-star"></i></span>
                                    <span class="tg-listing-rating-percent">(120 Recensioni)</span>
                                </div>
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
                            <h2 class="mb-15 text-capitalize wow fadeInUp">Scopri quando vuoi <br>partire con noi</h2>
                            <p class="text-capitalize wow fadeInUp">
                                Sei stanco delle solite mete affollate? Con Solarya Travel
                                vivi il mare in modo diverso: relax, comfort e itinerari su
                                misura per te e i tuoi cari.
                            </p>
                        </div>
                        <div class="tg-chose-list-wrap">
                            <div class="tg-chose-list d-flex mb-15 wow fadeInUp">
                                <span class="tg-chose-list-icon mr-20">
                                    <span style="display:inline-flex;width:64px;height:64px;align-items:center;justify-content:center;border-radius:50%;background:rgba(124,55,255,.08);color:#560CE3;font-size:26px">
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
                                    <span style="display:inline-flex;width:64px;height:64px;align-items:center;justify-content:center;border-radius:50%;background:rgba(124,55,255,.08);color:#560CE3;font-size:26px">
                                        <i class="fa-solid fa-shield-halved"></i>
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
    <div class="tg-banner-area tg-banner-space">
        <div class="container">
            <div class="row gx-0">
                <div class="col-lg-7">
                    <div class="tg-banner-video-wrap include-bg" style="background-image: url('{{ asset('assets/template/img/banner/thumb.jpg') }}')">
                        <div class="tg-banner-video-inner text-center">
                            <a href="https://www.youtube.com/watch?v=eEzD-Y97ges" target="_blank" rel="noopener" class="tg-video-play popup-video tg-pulse-border" style="cursor:pointer">
                                <span class="p-relative z-index-11">
                                    <svg width="19" height="21" viewBox="0 0 19 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.3616 8.34455C19.0412 9.31425 19.0412 11.7385 17.3616 12.7082L4.13504 20.3445C2.45548 21.3142 0.356021 20.1021 0.356021 18.1627L0.356022 2.89C0.356022 0.950609 2.45548 -0.261512 4.13504 0.708185L17.3616 8.34455Z" fill="currentColor"/>
                                    </svg>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="tg-banner-content p-relative z-index-1 text-center">
                        <img class="tg-banner-shape" src="{{ asset('assets/template/img/banner/shape.png') }}" alt="shape">
                        <h4 class="tg-banner-subtitle mb-10">Promo estate</h4>
                        <h2 class="tg-banner-title mb-25">Fino al 40% di sconto!</h2>
                        <div class="tg-banner-btn">
                            <a href="{{ route('tours.index') }}" class="tg-btn tg-btn-switch-animation">
                                <span class="tg-btn-text">Scopri le offerte</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <span class="tg-banner-transparent-bg"></span>

    {{-- ============= LOCATION (4 destinazioni) ============= --}}
    @php
        $locations = [
            ['title' => 'Capri',     'tours' => '08', 'img' => 'location.jpg'],
            ['title' => 'Positano',  'tours' => '06', 'img' => 'location-2.jpg'],
            ['title' => 'Amalfi',    'tours' => '07', 'img' => 'location-3.jpg'],
            ['title' => 'Ischia',    'tours' => '05', 'img' => 'location-4.jpg'],
        ];
    @endphp
    <div class="tg-location-area p-relative pb-40 tg-grey-bg pt-140">
        <img class="tg-location-shape d-none d-lg-block" src="{{ asset('assets/template/img/location/shape-2.png') }}" alt="shape">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tg-location-section-title text-center mb-30">
                        <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Le nostre mete</h5>
                        <h2 class="mb-15 text-capitalize wow fadeInUp">Le destinazioni più amate <br>della Costiera</h2>
                        <p class="text-capitalize wow fadeInUp">
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
    </div>

    {{-- ============= CTA TWO (banner full-width) ============= --}}
    <div class="tg-banner-area include-bg" style="background-image: url('{{ asset('assets/template/img/banner/banner.png') }}')">
        <div class="container">
            <div class="col-lg-12">
                <div class="tg-banner-2-content text-center">
                    <div class="tg-about-section-title mb-25">
                        <h5 class="tg-section-subtitle mb-10 wow fadeInUp">La tua prossima avventura</h5>
                        <h2 class="tg-section-title-white mb-25 wow fadeInUp">Itinerari esclusivi <br>nel Mediterraneo</h2>
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
                                <h2>Solarya Travel</h2>
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
    <div class="tg-testimonial-area pt-105 pb-100">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tg-location-section-title text-center mb-30">
                        <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Cosa dicono di noi</h5>
                        <h2 class="mb-15 text-capitalize wow fadeInUp">Le parole dei nostri ospiti</h2>
                        <p class="text-capitalize wow fadeInUp">
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
    </div>

    {{-- ============= BLOG ============= --}}
    @php
        $blogMain = ['img' => 'blog-1.jpg', 'tag' => 'Costiera', 'title' => 'Le 7 baie più belle da scoprire in catamarano', 'date' => '12 Set 2024', 'time' => '5 min'];
        $blogSide = [
            ['img' => 'blog-2.jpg', 'tag' => 'Esperienze', 'title' => 'Tramonti in mare: i nostri itinerari preferiti', 'date' => '03 Ago 2024', 'time' => '4 min'],
            ['img' => 'blog-3.jpg', 'tag' => 'Consigli',   'title' => 'Cosa portare a bordo: la guida definitiva',   'date' => '21 Lug 2024', 'time' => '6 min'],
        ];
    @endphp
    <div class="tg-blog-area tg-blog-space tg-grey-bg pt-135 p-relative z-index-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tg-location-section-title text-center mb-30">
                        <h5 class="tg-section-subtitle mb-15 wow fadeInUp">Blog &amp; Consigli</h5>
                        <h2 class="mb-15 text-capitalize wow fadeInUp">Ultime news dal blog</h2>
                        <p class="text-capitalize wow fadeInUp">
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
    </div>

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
                                    <a class="mb-20 d-inline-flex align-items-center px-4 py-3 rounded-3 text-white text-decoration-none fw-semibold border border-white" href="{{ route('contact') }}">
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

    if (typeof Swiper === 'undefined') return;

    // Hero slideshow (fade)
    if (document.getElementById('heroSlider')) {
        new Swiper('#heroSlider', {
            slidesPerView: 1,
            loop: true,
            spaceBetween: 0,
            speed: 2000,
            effect: 'fade',
            fadeEffect: { crossFade: true },
            autoplay: { delay: 3500, disableOnInteraction: false },
            navigation: {
                prevEl: '.tg-hero-prev',
                nextEl: '.tg-hero-next',
            },
        });
    }

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
