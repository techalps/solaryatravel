@extends('layouts.public')

@section('title', 'Pagamento — ' . $booking->booking_number)

@section('content')

    {{-- ============= HERO / BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tours.show', $booking->tour->slug) }}" class="text-white-50 text-decoration-none">{{ $booking->tour->name }}</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Pagamento</li>
                        </ol>
                    </nav>
                    <h1 class="mb-2 wow fadeInUp"><i class="fa-solid fa-lock me-2"></i>Conferma e paga</h1>
                    <p class="lead mb-0">Prenotazione <strong>#{{ $booking->booking_number }}</strong> in attesa di pagamento</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============= MAIN AREA ============= --}}
    <div class="tg-tour-about-area pt-50 pb-70">
        <div class="container">
            <div class="row">
                {{-- ========== LEFT: BOOKING DETAILS ========== --}}
                <div class="col-xl-9 col-lg-8">
                    <div class="tg-tour-about-wrap mr-55">
                        <div class="tg-tour-about-content">

                            {{-- Tour & Departure --}}
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-water text-primary me-2"></i>Il tuo tour</h4>
                                <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#fafafa;border:1px solid #eef0f3">
                                    @if($booking->tour->primaryImage)
                                        <img src="{{ $booking->tour->primaryImage->url }}" alt="" style="width:96px;height:96px;border-radius:12px;object-fit:cover;flex:0 0 auto">
                                    @endif
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 fw-bold" style="color:#0E1B33">{{ $booking->tour->name }}</h5>
                                        @if($booking->departure)
                                            <div class="text-muted small mb-1">
                                                <i class="fa-regular fa-calendar me-1 text-primary"></i>
                                                {{ \Carbon\Carbon::parse($booking->departure->departure_date)->locale('it')->isoFormat('dddd D MMMM Y') }}
                                                · <i class="fa-regular fa-clock ms-1 me-1 text-primary"></i>
                                                {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}
                                            </div>
                                        @endif
                                        @if($booking->tour->departure_point)
                                            <div class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-primary"></i>{{ $booking->tour->departure_point }}</div>
                                        @endif
                                        @if($booking->tour->duration_hours)
                                            <div class="text-muted small"><i class="fa-regular fa-clock me-1 text-primary"></i>Durata: {{ $booking->tour->duration_hours }} ore</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="tg-tour-about-border mb-30"></div>

                            {{-- Customer --}}
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-regular fa-user text-primary me-2"></i>Intestatario prenotazione</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="small text-muted">Nome</div>
                                        <div class="fw-semibold">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">Email</div>
                                        <div class="fw-semibold">{{ $booking->customer_email }}</div>
                                    </div>
                                    @if($booking->customer_phone)
                                        <div class="col-md-6">
                                            <div class="small text-muted">Telefono</div>
                                            <div class="fw-semibold">{{ $booking->customer_phone }}</div>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="small text-muted">Partecipanti</div>
                                        <div class="fw-semibold">{{ $booking->seats }} {{ $booking->seats === 1 ? 'persona' : 'persone' }}</div>
                                    </div>
                                </div>
                                @if($booking->special_requests)
                                    <div class="mt-3 pt-3" style="border-top:1px dotted #e4e4e4">
                                        <div class="small text-muted mb-1">Richieste speciali</div>
                                        <div class="small">{{ $booking->special_requests }}</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Payment info / How it works --}}
                            <div class="tg-tour-about-border mb-30"></div>
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-shield-alt text-primary me-2"></i>Pagamento sicuro</h4>
                                <div class="tg-tour-about-list tg-tour-about-list-2">
                                    <ul class="list-unstyled mb-0">
                                        <li>
                                            <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                            <span class="text">Pagamento gestito da Stripe — i tuoi dati non transitano sul nostro server.</span>
                                        </li>
                                        <li>
                                            <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                            <span class="text">Conferma immediata e ricevuta inviata via email.</span>
                                        </li>
                                        <li>
                                            <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                            <span class="text">Biglietti con QR code consegnati subito dopo il pagamento.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ========== RIGHT: ORDER SUMMARY ========== --}}
                <div class="col-xl-3 col-lg-4">
                    <div class="tg-tour-about-sidebar top-sticky mb-50">
                        <h4 class="tg-tour-about-title title-2 mb-15">Riepilogo ordine</h4>

                        <div class="bk-summary-mini mb-15">
                            <div class="bk-summary-line">
                                <span>Subtotale</span>
                                <span>€{{ number_format($booking->base_price, 2, ',', '.') }}</span>
                            </div>
                            @if($booking->addons_total > 0)
                                <div class="bk-summary-line">
                                    <span>Extra</span>
                                    <span>€{{ number_format($booking->addons_total, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($booking->discount_amount > 0)
                                <div class="bk-summary-line discount">
                                    <span><i class="fa-solid fa-tag me-1"></i>Sconto</span>
                                    <span>− €{{ number_format($booking->discount_amount, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($booking->tax_amount > 0)
                                <div class="bk-summary-line text-muted">
                                    <span>IVA</span>
                                    <span>€{{ number_format($booking->tax_amount, 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>

                        @if($booking->addons->count())
                            <div class="tg-tour-about-extra mb-15">
                                <span class="tg-tour-about-sidebar-title d-inline-block mb-10">Extra inclusi:</span>
                                <div class="tg-filter-list">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($booking->addons as $ba)
                                            <li>
                                                <span class="adult ps-0">{{ $ba->addon?->name ?? 'Extra' }} @if($ba->quantity > 1) × {{ $ba->quantity }} @endif</span>
                                                <span class="quantity">€{{ number_format($ba->total_price, 2, ',', '.') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="tg-tour-about-border-doted mb-15"></div>

                        <div class="tg-tour-about-coast d-flex align-items-center flex-wrap justify-content-between mb-5">
                            <span class="tg-tour-about-sidebar-title d-inline-block">Totale:</span>
                            <h5 class="total-price mb-0">€{{ number_format($booking->total_amount, 2, ',', '.') }}</h5>
                        </div>
                        <div class="text-end text-muted small mb-20" style="font-size:.78rem">IVA inclusa</div>

                        <form method="POST" action="{{ route('payment.process', $booking->uuid) }}">
                            @csrf
                            <button type="submit" class="tg-btn tg-btn-switch-animation w-100">
                                <i class="fa-solid fa-lock me-2"></i>Procedi al pagamento
                            </button>
                        </form>

                        <small class="d-block text-muted text-center mt-3" style="font-size:.78rem">
                            <i class="fa-brands fa-cc-stripe me-1"></i>Powered by Stripe · SSL secure
                        </small>

                        @if($booking->payment_deadline)
                            <div class="text-center mt-3 pt-3" style="border-top:1px dotted #e4e4e4">
                                <small class="text-muted d-block">Scadenza pagamento</small>
                                <small class="fw-semibold text-danger">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    {{ \Carbon\Carbon::parse($booking->payment_deadline)->locale('it')->isoFormat('D MMM YYYY · HH:mm') }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('head')
<style>
    .top-sticky { position: sticky; top: 100px; }
    @media (max-width: 991.98px) {
        .top-sticky { position: static; }
        .tg-tour-about-wrap.mr-55 { margin-right: 0; }
    }
    .bk-summary-mini { background: #fafafa; border-radius: 10px; padding: .75rem .9rem; }
    .bk-summary-line { display: flex; justify-content: space-between; padding: .25rem 0; font-size: .9rem; color: #0E1B33; }
    .bk-summary-line.discount { color: #198754; }

    .tg-tour-about-sidebar .tg-btn { background: var(--tg-theme-primary); color: #fff; padding: 14px 22px; font-weight: 600; }
    .tg-tour-about-sidebar .tg-btn:hover { background: #5b1fd8; color: #fff; }
    .tg-tour-about-sidebar .tg-btn:disabled { background: #c7b8e8; cursor: not-allowed; }
    .bk-register-box { background:#f8fafc; border:1px solid #e9eef5; border-radius:12px; padding:14px 16px; }
    .bk-register-box summary { list-style:none; }
    .bk-register-box summary::-webkit-details-marker { display:none; }
    .bk-register-box .form-control-sm { border-radius:8px; }

    .breadcrumb-item.active { color: #fff !important; }
    .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.6); }
</style>
@endpush
