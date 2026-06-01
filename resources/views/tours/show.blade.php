@extends('layouts.public')

@section('title', $tour->meta_title ?: $tour->name)
@section('meta_description', $tour->meta_description ?: ($tour->description_short ?: ''))

@section('content')

    {{-- ============= HERO / BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="
        @if($tour->primaryImage)
            background: linear-gradient(rgba(0,0,0,.55),rgba(0,0,0,.55)), url('{{ $tour->primaryImage->url }}') center/cover;
        @else
            background: linear-gradient(135deg, #111 0%, #333 100%);
        @endif
    ">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tours.index') }}" class="text-white-50 text-decoration-none">Tour</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">{{ $tour->name }}</li>
                        </ol>
                    </nav>
                    <h1 class="mb-3 wow fadeInUp">{{ $tour->name }}</h1>
                    @if($tour->description_short)
                        <p class="lead mb-4 wow fadeInUp" style="max-width:700px;margin:0 auto;">{{ $tour->description_short }}</p>
                    @endif
                    <a href="#book" class="tg-btn tg-btn-hero-cta wow fadeInUp">
                        <i class="fa-regular fa-calendar-check me-2"></i>Prenota ora
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= TOUR CONTENT (single column, centered) ============= --}}
    <div class="tg-tour-details-area pt-50 pb-25">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9 col-lg-10">

                    {{-- Quick meta strip --}}
                    @if($tour->departure_point)
                    <div class="tg-tour-details-video-location d-flex flex-wrap align-items-center mb-25 justify-content-center">
                        <span class="mr-25"><i class="fa-regular fa-location-dot me-1"></i> {{ $tour->departure_point }}</span>
                    </div>
                    @endif

                    {{-- Gallery: 1 main + up to 3 thumbs --}}
                    @if($tour->images->count())
                        @php
                            $imgs = $tour->images->take(4);
                            $main = $imgs->first();
                            $rest = $imgs->slice(1);
                        @endphp
                        <div class="row gx-15 mb-25">
                            <div class="col-lg-7">
                                <div class="tg-tour-details-video-thumb mb-15">
                                    <img class="w-100" src="{{ $main->url }}" alt="{{ $tour->name }}" style="height:420px;object-fit:cover;border-radius:15px;">
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="row gx-15">
                                    @foreach($rest as $img)
                                        <div class="{{ $rest->count() === 1 ? 'col-12' : ($loop->iteration === 1 ? 'col-12' : 'col-md-6') }}">
                                            <div class="tg-tour-details-video-thumb mb-15">
                                                <img class="w-100" src="{{ $img->url }}" alt="" style="height:{{ $rest->count() === 1 ? '420' : ($loop->iteration === 1 ? '200' : '205') }}px;object-fit:cover;border-radius:15px;">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Feature list strip --}}
                    <div class="tg-tour-details-feature-list-wrap mb-35 pb-20" style="border-bottom:1px solid #e4e4e4">
                        <div class="tg-tour-details-video-feature-list">
                            <ul class="list-unstyled mb-0">
                                @if($tour->duration_hours)
                                    <li>
                                        <span class="icon"><i class="fa-regular fa-clock"></i></span>
                                        <div>
                                            <span class="title">Durata</span>
                                            <span class="duration">{{ $tour->duration_hours }} ore</span>
                                        </div>
                                    </li>
                                @endif
                                @php $adultBasePrice = $tour->periods->min('base_price'); @endphp
                                @if($adultBasePrice)
                                    <li>
                                        <span class="icon"><i class="fa-solid fa-tag"></i></span>
                                        <div>
                                            <span class="title">A partire da</span>
                                            <span class="duration">€{{ number_format($adultBasePrice, 0, ',', '.') }}/pers</span>
                                        </div>
                                    </li>
                                @endif
                                @if($tour->departure_point)
                                    <li>
                                        <span class="icon"><i class="fa-solid fa-location-dot"></i></span>
                                        <div>
                                            <span class="title">Partenza</span>
                                            <span class="duration">{{ $tour->departure_point }}</span>
                                        </div>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    {{-- About --}}
                    @if($tour->description)
                        <div class="tg-tour-about-inner mb-25">
                            <h4 class="tg-tour-about-title mb-15">Informazioni sul tour</h4>
                            <p class="lh-28 mb-0">{!! nl2br(e($tour->description)) !!}</p>
                        </div>
                    @endif

                    {{-- Itinerary --}}
                    @if($tour->itinerary)
                        <div class="tg-tour-about-inner mb-40">
                            <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-route text-primary me-2"></i>Itinerario</h4>
                            <p class="lh-28 mb-0">{!! nl2br(e($tour->itinerary)) !!}</p>
                        </div>
                    @endif

                    {{-- Included / Excluded --}}
                    @if(!empty($tour->included) || !empty($tour->excluded))
                        <div class="tg-tour-about-border mb-40"></div>
                        <div class="tg-tour-about-inner mb-40">
                            <h4 class="tg-tour-about-title mb-20">Cosa è incluso / escluso</h4>
                            <div class="row">
                                @if(!empty($tour->included))
                                    <div class="col-lg-6">
                                        <div class="tg-tour-about-list tg-tour-about-list-2">
                                            <ul class="list-unstyled mb-0">
                                                @foreach((array) $tour->included as $item)
                                                    <li>
                                                        <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                                        <span class="text">{{ $item }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                                @if(!empty($tour->excluded))
                                    <div class="col-lg-6">
                                        <div class="tg-tour-about-list tg-tour-about-list-2 disable">
                                            <ul class="list-unstyled mb-0">
                                                @foreach((array) $tour->excluded as $item)
                                                    <li>
                                                        <span class="icon mr-10"><i class="fa-sharp fa-solid fa-xmark"></i></span>
                                                        <span class="text">{{ $item }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Age brackets / prices per period --}}
                    @php $periodsWithPrices = $tour->periods->filter(fn($p) => $p->base_price > 0 || $p->ageBrackets->count()); @endphp
                    @if($periodsWithPrices->count())
                        <div class="tg-tour-about-border mb-40"></div>
                        <div class="tg-tour-about-inner mb-40">
                            <h4 class="tg-tour-about-title mb-20"><i class="fa-solid fa-tags text-primary me-2"></i>Tariffe per fascia d'età</h4>
                            @foreach($periodsWithPrices as $period)
                                <div class="mb-30">
                                    <div class="d-flex align-items-center justify-content-between mb-10 pb-10" style="border-bottom:2px solid #f0f0f0">
                                        <span class="fw-semibold text-dark">
                                            {{ $period->label ?: 'Periodo' }}
                                        </span>
                                        <span class="text-muted small">
                                            {{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-borderless align-middle mb-0 tg-price-table">
                                            <thead>
                                                <tr>
                                                    <th>Fascia</th>
                                                    <th>Età</th>
                                                    <th class="text-end">Prezzo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if($period->base_price > 0)
                                                    <tr>
                                                        <td><strong>Adulti</strong></td>
                                                        <td class="text-muted small">Prezzo base</td>
                                                        <td class="text-end fw-bold text-primary">€{{ number_format($period->base_price, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endif
                                                @foreach($period->ageBrackets as $b)
                                                    <tr>
                                                        <td><strong>{{ $b->label }}</strong></td>
                                                        <td class="text-muted small">
                                                            @if($b->max_age === null)
                                                                da {{ $b->min_age }} anni
                                                            @else
                                                                {{ $b->min_age }} – {{ $b->max_age }} anni
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-bold text-primary">€{{ number_format($b->price, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ============= BOOKING SECTION ============= --}}
    <div id="book" class="tg-tour-booking-section py-5" style="background: #f8f9fc; scroll-margin-top: 90px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-9">
                    <div class="text-center mb-4">
                        <h3 class="tg-tour-about-title mb-2"><i class="fa-regular fa-calendar-check text-primary me-2"></i>Prenota la tua esperienza</h3>
                        <p class="text-muted mb-0">Scegli la data, indica i partecipanti e completa la prenotazione in pochi passi.</p>
                    </div>
                    <livewire:public.booking-form :tour="$tour" :available-dates="$departuresByDate ?? []" />
                </div>
            </div>
        </div>
    </div>

    {{-- ============= SIMILAR TOURS ============= --}}
    @if($similar->count())
        <div class="py-5">
            <div class="container">
                <h3 class="tg-tour-about-title mb-4">Tour simili</h3>
                <div class="row g-3">
                    @foreach($similar as $i => $st)
                        <div class="col-lg-4 col-md-6">
                            <a href="{{ route('tours.show', $st->slug) }}" class="text-decoration-none">
                                <div class="bg-white border rounded-4 overflow-hidden h-100 shadow-sm tg-similar-card">
                                    @if($st->primaryImage)
                                        <img src="{{ $st->primaryImage->url }}" class="w-100" alt="{{ $st->name }}" style="height:200px;object-fit:cover">
                                    @else
                                        <img src="{{ asset('assets/template/img/hero/hero-'.(($i % 5) + 1).'.jpg') }}" class="w-100" alt="{{ $st->name }}" style="height:200px;object-fit:cover">
                                    @endif
                                    <div class="p-3">
                                        <h6 class="text-dark mb-1">{{ $st->name }}</h6>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>@if($st->duration_hours) <i class="fa-regular fa-clock me-1"></i>{{ $st->duration_hours }}h @endif</span>
                                            <strong class="text-primary">da €{{ number_format($st->price_from ?? 0, 0, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

@endsection

@push('head')
<style>
    /* Hero CTA */
    .tg-btn-hero-cta {
        display: inline-flex;
        align-items: center;
        padding: 14px 32px;
        background: #fff;
        color: var(--tg-theme-primary, #560CE3);
        font-weight: 700;
        border-radius: 50px;
        text-decoration: none;
        box-shadow: 0 10px 28px rgba(0,0,0,.25);
        transition: transform .2s, box-shadow .2s, background .2s, color .2s;
    }
    .tg-btn-hero-cta:hover {
        transform: translateY(-3px);
        background: var(--tg-theme-primary, #560CE3);
        color: #fff;
        box-shadow: 0 14px 34px rgba(0,0,0,.35);
    }

    /* Hero: testi bianchi */
    .tg-breadcrumb-area h1,
    .tg-breadcrumb-area p,
    .tg-breadcrumb-area .breadcrumb-item { color: #fff !important; }

    /* Centered feature list */
    .tg-tour-details-feature-list-wrap .tg-tour-details-video-feature-list ul {
        justify-content: center;
    }

    /* Quick meta strip */
    .tg-tour-details-video-location { gap: .5rem 0; }
    .tg-tour-details-video-location > span { padding: 0 .25rem; }

    /* Price table */
    .tg-price-table thead th {
        text-transform: uppercase; font-size: .75rem; letter-spacing: .04em;
        color: #898989; font-weight: 600;
        border-bottom: 1px solid #e4e4e4; padding-bottom: .75rem;
    }
    .tg-price-table tbody tr { border-bottom: 1px dotted #e4e4e4; }
    .tg-price-table tbody tr:last-child { border-bottom: 0; }
    .tg-price-table tbody td { padding: .85rem 0; }

    /* Booking section: align widget margins */
    .tg-tour-booking-section .tg-tour-about-sidebar {
        margin-left: 0; /* override default -30px */
    }

    /* Similar cards lift on hover */
    .tg-similar-card { transition: transform .2s, box-shadow .2s; }
    .tg-similar-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(14,27,51,.08) !important; }

    /* Breadcrumb on dark hero */
    .breadcrumb-item.active { color: #fff !important; }
    .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.6); }

    /* Smooth scroll */
    html { scroll-behavior: smooth; }
</style>
@endpush
