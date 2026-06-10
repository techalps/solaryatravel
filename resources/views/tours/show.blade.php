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
                    @if($tour->booking_on_request)
                        <a href="mailto:info@solaryatravel.com" class="tg-btn tg-btn-hero-cta wow fadeInUp">
                            <i class="fa-regular fa-envelope me-2"></i>Contattaci per prenotare
                        </a>
                    @else
                        <button onclick="openBookingDrawer()" class="tg-btn tg-btn-hero-cta wow fadeInUp">
                            <i class="fa-regular fa-calendar-check me-2"></i>Prenota ora
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============= TOUR CONTENT (single column, centered) ============= --}}
    <div class="tg-tour-details-area pt-50 pb-25">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-12 col-lg-12">

                    {{-- Quick meta strip (nascosto per i tour su richiesta) --}}
                    @if($tour->departure_point && ! $tour->booking_on_request)
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

                    {{-- Feature list strip (durata, prezzo, partenza) — nascosto per i tour su richiesta --}}
                    @unless($tour->booking_on_request)
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
                                @php $adultBasePrice = $tour->price_from; @endphp
                                <li>
                                    <span class="icon"><i class="fa-solid fa-tag"></i></span>
                                    <div>
                                        <span class="title">A partire da</span>
                                        <span class="duration"><x-tour-price :price="$adultBasePrice" suffix="/pers" /></span>
                                    </div>
                                </li>
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
                    @endunless

                    {{-- Avviso minimo partecipanti (solo tour prenotabili online) --}}
                    @unless($tour->booking_on_request)
                        <div class="tg-min-participants-notice mb-25">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            {{ \App\Support\Settings::minParticipantsNotice() }}
                        </div>
                    @endunless

                    {{-- About --}}
                    @if($tour->description)
                        <div class="tg-tour-about-inner mb-25">
                            <h4 class="tg-tour-about-title mb-15">Informazioni sul tour</h4>
                            <p class="lh-28 mb-0">{!! nl2br(e($tour->description)) !!}</p>
                        </div>
                    @endif

                    {{-- Itinerario, incluso/escluso e tariffe — nascosti per i tour su richiesta --}}
                    @unless($tour->booking_on_request)

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
                    @php $periodsWithPrices = $tour->periods->filter(fn($p) => $p->ageBrackets->count()); @endphp
                    @if($periodsWithPrices->count())
                        <div class="tg-tour-about-border mb-40"></div>
                        <div class="tg-tour-about-inner mb-40">
                            <h4 class="tg-tour-about-title mb-20"><i class="fa-solid fa-tags text-primary me-2"></i>Tariffe per fascia d'età</h4>
                            <div class="accordion" id="acc-tariffe">
                                @foreach($periodsWithPrices as $loop_period => $period)
                                    @php $accId = 'acc-period-' . $period->id; @endphp
                                    <div class="accordion-item" style="border:1px solid #e4e4e4;border-radius:10px;margin-bottom:.5rem;overflow:hidden">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button {{ $loop_period > 0 ? 'collapsed' : '' }}"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $accId }}"
                                                    aria-expanded="{{ $loop_period === 0 ? 'true' : 'false' }}"
                                                    aria-controls="{{ $accId }}"
                                                    style="font-weight:600;background:#fff">
                                                <span>{{ $period->label ?: 'Periodo' }}</span>
                                                <span class="ms-auto me-3 text-muted fw-normal small">
                                                    {{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}
                                                </span>
                                            </button>
                                        </h2>
                                        <div id="{{ $accId }}"
                                             class="accordion-collapse collapse {{ $loop_period === 0 ? 'show' : '' }}"
                                             data-bs-parent="#acc-tariffe">
                                            <div class="accordion-body pt-0">
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
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @endunless {{-- /booking_on_request --}}

                </div>
            </div>
        </div>
    </div>

    @if($tour->booking_on_request)

    {{-- ============= STICKY BAR — SU RICHIESTA ============= --}}
    <div style="position:fixed;bottom:0;left:0;right:0;z-index:1039;background:#fff;border-top:1px solid #e8e8e8;box-shadow:0 -4px 20px rgba(0,0,0,.08)">
        <div class="container py-2">
            <div class="row align-items-center g-2">
                <div class="col">
                    <span style="font-size:.76rem;color:#888;display:block;line-height:1.3">Prenotazione su richiesta</span>
                    <strong style="font-size:.95rem;color:#0E1B33">Contattaci per disponibilità e tariffe</strong>
                </div>
                <div class="col-auto d-flex gap-2">
                    <a href="https://wa.me/393450884743?text={{ rawurlencode('Ciao Solarya Travel, vorrei informazioni sulla crociera "' . $tour->name . '".') }}" target="_blank" rel="noopener"
                       style="background:#25D366;color:#fff;border:none;border-radius:50px;padding:10px 18px;font-weight:700;font-size:.88rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <i class="fa-brands fa-whatsapp"></i><span class="d-none d-sm-inline">WhatsApp</span>
                    </a>
                    <a href="mailto:info@solaryatravel.com"
                       style="background:var(--tg-theme-secondary);color:#fff;border:none;border-radius:50px;padding:10px 18px;font-weight:700;font-size:.88rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <i class="fa-regular fa-envelope"></i><span class="d-none d-sm-inline">Email</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @else

    {{-- ============= BOOKING DRAWER ============= --}}
    {{-- Overlay --}}
    <div id="bk-overlay" onclick="closeBookingDrawer()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1040;opacity:0;transition:opacity .3s ease"></div>

    {{-- Drawer panel --}}
    <div id="bk-drawer"
         style="position:fixed;bottom:0;left:0;right:0;max-height:92dvh;background:#fff;border-radius:24px 24px 0 0;z-index:1041;transform:translateY(100%);transition:transform .38s cubic-bezier(.32,.72,0,1);display:flex;flex-direction:column;overflow:hidden">

        {{-- Drawer header --}}
        <div style="border-bottom:1px solid #f0f0f0;flex-shrink:0">
            <div class="container py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <span style="font-size:.75rem;color:#888;display:block;margin-bottom:2px">Stai prenotando</span>
                        <h6 style="margin:0;font-weight:700;color:#0E1B33">{{ $tour->name }}</h6>
                    </div>
                    <div class="col-auto">
                        <button onclick="closeBookingDrawer()"
                                style="background:#f4f4f4;border:none;border-radius:50%;width:36px;height:36px;font-size:1.1rem;color:#555;cursor:pointer;display:flex;align-items:center;justify-content:center"
                                aria-label="Chiudi">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Drawer body --}}
        <div style="overflow-y:auto;flex:1;padding-bottom:40px">
            <div class="container" style="padding-top:20px">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-7 col-md-9">
                        <livewire:public.booking-form :tour="$tour" :available-dates="$departuresByDate ?? []" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= STICKY BOOKING BAR ============= --}}
    <div id="bk-sticky-bar"
         style="position:fixed;bottom:0;left:0;right:0;z-index:1039;background:#fff;border-top:1px solid #e8e8e8;box-shadow:0 -4px 20px rgba(0,0,0,.08)">
        <div class="container py-2">
            <div class="row align-items-center">
                <div class="col">
                    <span style="font-size:.72rem;color:#888;display:block;line-height:1.2">A partire da</span>
                    @php $barPrice = $tour->price_from; @endphp
                    @if($barPrice)
                        <strong style="font-size:1.15rem;color:#0E1B33">€{{ number_format($barPrice, 0, ',', '.') }}<span style="font-size:.75rem;font-weight:400;color:#888"> /pers</span></strong>
                    @else
                        <strong style="font-size:1rem;color:#0E1B33">Su richiesta</strong>
                    @endif
                </div>
                <div class="col-auto">
                    <button onclick="openBookingDrawer()"
                            style="background:var(--tg-theme-secondary);color:#fff;border:none;border-radius:50px;padding:12px 28px;font-weight:700;font-size:.95rem;cursor:pointer;transition:background .2s">
                        <i class="fa-regular fa-calendar-check me-2"></i>Prenota ora
                    </button>
                </div>
            </div>
        </div>
    </div>

    @endif

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
                                            <strong class="text-primary">@if($st->price_from && ! $st->booking_on_request)da €{{ number_format($st->price_from, 0, ',', '.') }}@else Su richiesta @endif</strong>
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
        color: var(--tg-theme-secondary);
        font-weight: 700;
        border-radius: 50px;
        text-decoration: none;
        box-shadow: 0 10px 28px rgba(0,0,0,.25);
        transition: transform .2s, box-shadow .2s, background .2s, color .2s;
    }
    .tg-btn-hero-cta:hover {
        transform: translateY(-3px);
        background: var(--tg-theme-secondary);
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

    /* Avviso minimo partecipanti */
    .tg-min-participants-notice {
        background: #fff8e6;
        border: 1px solid #ffe39a;
        border-radius: 14px;
        padding: 14px 18px;
        color: #6b4e00;
        font-size: .92rem;
        line-height: 1.5;
    }
    .tg-min-participants-notice i { color: #d39e00; }

    /* Smooth scroll */
    html { scroll-behavior: smooth; }

    /* Drawer body scrollbar */
    #bk-drawer > div:last-child::-webkit-scrollbar { width: 4px; }
    #bk-drawer > div:last-child::-webkit-scrollbar-track { background: transparent; }
    #bk-drawer > div:last-child::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

    /* Body padding when sticky bar is visible */
    body.bk-bar-visible { padding-bottom: 72px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // --- Drawer ---
    function openBookingDrawer() {
        var drawer  = document.getElementById('bk-drawer');
        var overlay = document.getElementById('bk-overlay');
        overlay.style.display = 'block';
        requestAnimationFrame(function () {
            overlay.style.opacity = '1';
            drawer.style.transform = 'translateY(0)';
        });
        document.body.style.overflow = 'hidden';
    }

    function closeBookingDrawer() {
        var drawer  = document.getElementById('bk-drawer');
        var overlay = document.getElementById('bk-overlay');
        drawer.style.transform = 'translateY(100%)';
        overlay.style.opacity = '0';
        setTimeout(function () { overlay.style.display = 'none'; }, 380);
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeBookingDrawer();
    });

    window.openBookingDrawer  = openBookingDrawer;
    window.closeBookingDrawer = closeBookingDrawer;

    // Padding bottom per la sticky bar sempre visibile
    document.body.classList.add('bk-bar-visible');
})();
</script>
@endpush
