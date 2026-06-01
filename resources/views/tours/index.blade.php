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
