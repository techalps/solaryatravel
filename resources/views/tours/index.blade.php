@extends('layouts.public')

@section('title', 'I nostri tour')
@section('meta_description', 'Scopri tutti i tour in catamarano disponibili: durata, prezzi e prossime partenze. Prenota online la tua escursione.')

@section('content')

    {{-- ============= BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="mb-3 wow fadeInUp">I nostri tour</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Tour</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Search box (stesso della home) --}}
    <div class="pt-30 pb-20">
        @include('partials.public.tour-search')
    </div>

    {{-- ============= LISTING ============= --}}
    <div class="tg-listing-area pt-30 pb-100">
        <div class="container">

            @if($search['isSearch'])
                <p class="text-muted mb-3">
                    <strong>{{ $search['results'] }}</strong> tour trovati
                    @if($search['date']) per il {{ \Carbon\Carbon::parse($search['date'])->locale('it')->isoFormat('D MMM YYYY') }}@endif
                </p>
            @endif

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
                                        <i class="fa-solid fa-location-dot me-1"></i> {{ $tour->departure_point ?? '' }}
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
                        <i class="fa-regular fa-face-frown fs-1 text-muted mb-3 d-block"></i>
                        <h4 class="text-muted">Nessun tour disponibile</h4>
                        <p class="text-muted">Prova a modificare i filtri di ricerca.</p>
                        <a href="{{ route('tours.index') }}" class="btn btn-outline-primary rounded-pill px-4">Reset filtri</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
