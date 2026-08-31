@extends('layouts.public')

@section('title', __('account.common.payment') . ' — ' . $booking->booking_number)

@section('content')

    {{-- ============= HERO / BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">{{ __('account.common.home') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tours.show', $booking->tour->slug) }}" class="text-white-50 text-decoration-none">{{ $booking->tour->name }}</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">{{ __('account.common.payment') }}</li>
                        </ol>
                    </nav>
                    <h1 class="mb-2 wow fadeInUp"><i class="fa-solid fa-lock me-2"></i>{{ __('account.pay.title') }}</h1>
                    <p class="lead mb-0">{{ __('account.common.booking') }} <strong>#{{ $booking->booking_number }}</strong> {{ __('account.pay.awaiting') }}</p>
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
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-water text-primary me-2"></i>{{ __('account.common.your_tour') }}</h4>
                                <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#fafafa;border:1px solid #eef0f3">
                                    @if($booking->tour->primaryImage)
                                        <img src="{{ $booking->tour->primaryImage->url }}" alt="" style="width:96px;height:96px;border-radius:12px;object-fit:cover;flex:0 0 auto">
                                    @endif
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 fw-bold" style="color:#0E1B33">{{ $booking->tour->name }}</h5>
                                        @if($booking->departure)
                                            <div class="text-muted small mb-1">
                                                <i class="fa-regular fa-calendar me-1 text-primary"></i>
                                                {{ \Carbon\Carbon::parse($booking->departure->departure_date)->locale(app()->getLocale())->isoFormat('dddd D MMMM Y') }}
                                                · <i class="fa-regular fa-clock ms-1 me-1 text-primary"></i>
                                                {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}
                                            </div>
                                        @endif
                                        @if($booking->tour->departure_point)
                                            <div class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-primary"></i>{{ $booking->tour->departure_point }}</div>
                                        @endif
                                        @if($booking->tour->duration_hours)
                                            <div class="text-muted small"><i class="fa-regular fa-clock me-1 text-primary"></i>{{ __('account.common.duration') }}: {{ __('account.common.hours', ['count' => $booking->tour->duration_hours]) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="tg-tour-about-border mb-30"></div>

                            {{-- Customer --}}
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-regular fa-user text-primary me-2"></i>{{ __('account.pay.lead_booker_title') }}</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="small text-muted">{{ __('account.common.name') }}</div>
                                        <div class="fw-semibold">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">{{ __('account.common.email') }}</div>
                                        <div class="fw-semibold">{{ $booking->customer_email }}</div>
                                    </div>
                                    @if($booking->customer_phone)
                                        <div class="col-md-6">
                                            <div class="small text-muted">{{ __('account.common.phone') }}</div>
                                            <div class="fw-semibold">{{ $booking->customer_phone }}</div>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="small text-muted">{{ __('account.common.participants') }}</div>
                                        <div class="fw-semibold">{{ trans_choice('account.common.person', $booking->seats, ['count' => $booking->seats]) }}</div>
                                    </div>
                                </div>
                                @if($booking->special_requests)
                                    <div class="mt-3 pt-3" style="border-top:1px dotted #e4e4e4">
                                        <div class="small text-muted mb-1">{{ __('account.common.special_requests') }}</div>
                                        <div class="small">{{ $booking->special_requests }}</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Payment info / How it works --}}
                            <div class="tg-tour-about-border mb-30"></div>
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-shield-alt text-primary me-2"></i>{{ __('account.pay.secure_payment') }}</h4>
                                <div class="tg-tour-about-list tg-tour-about-list-2">
                                    <ul class="list-unstyled mb-0">
                                        <li>
                                            <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                            <span class="text">{{ __('account.pay.stripe_note') }}</span>
                                        </li>
                                        <li>
                                            <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                            <span class="text">{{ __('account.pay.instant_confirm') }}</span>
                                        </li>
                                        <li>
                                            <span class="icon mr-10"><i class="fa-sharp fa-solid fa-check fa-fw"></i></span>
                                            <span class="text">{{ __('account.pay.tickets_after') }}</span>
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
                        <h4 class="tg-tour-about-title title-2 mb-15">{{ __('account.common.order_summary') }}</h4>

                        <div class="bk-summary-mini mb-15">
                            <div class="bk-summary-line">
                                <span>{{ __('account.common.subtotal') }}</span>
                                <span>€{{ number_format($booking->base_price, 2, ',', '.') }}</span>
                            </div>
                            @if($booking->addons_total > 0)
                                <div class="bk-summary-line">
                                    <span>{{ __('account.common.extras') }}</span>
                                    <span>€{{ number_format($booking->addons_total, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($booking->discount_amount > 0)
                                <div class="bk-summary-line discount">
                                    <span><i class="fa-solid fa-tag me-1"></i>{{ __('account.common.discount') }}</span>
                                    <span>− €{{ number_format($booking->discount_amount, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($booking->tax_amount > 0)
                                <div class="bk-summary-line text-muted">
                                    <span>{{ __('account.common.vat') }}</span>
                                    <span>€{{ number_format($booking->tax_amount, 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>

                        @if($booking->addons->count())
                            <div class="tg-tour-about-extra mb-15">
                                <span class="tg-tour-about-sidebar-title d-inline-block mb-10">{{ __('account.pay.extras_included') }}</span>
                                <div class="tg-filter-list">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($booking->addons as $ba)
                                            <li>
                                                <span class="adult ps-0">{{ $ba->addon?->name ?? __('account.detail.extra_fallback') }} @if($ba->quantity > 1) × {{ $ba->quantity }} @endif</span>
                                                <span class="quantity">€{{ number_format($ba->total_price, 2, ',', '.') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="tg-tour-about-border-doted mb-15"></div>

                        <div class="tg-tour-about-coast d-flex align-items-center flex-wrap justify-content-between mb-5">
                            <span class="tg-tour-about-sidebar-title d-inline-block">{{ __('account.common.total_colon') }}</span>
                            <h5 class="total-price mb-0">€{{ number_format($booking->total_amount, 2, ',', '.') }}</h5>
                        </div>
                        <div class="text-end text-muted small mb-20" style="font-size:.78rem">{{ __('account.common.vat_included') }}</div>

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
                            {{-- Conto alla rovescia invece dell'orario assoluto: la
                                 finestra è di pochi minuti, e "mancano 12:30" si
                                 legge senza dover confrontare col proprio orologio.
                                 A zero si ricarica: il server annulla il carrello e
                                 rimanda alla lista dei tour. --}}
                            <div class="text-center mt-3 pt-3" style="border-top:1px dotted #e4e4e4"
                                 data-checkout-countdown
                                 data-deadline="{{ $booking->payment_deadline->toIso8601String() }}">
                                <small class="text-muted d-block">{{ __('account.pay.seats_held_for') }}</small>
                                <small class="fw-semibold text-danger">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    <span data-countdown-value>--:--</span>
                                </small>
                                <small class="d-block text-muted mt-1" style="font-size:.72rem">
                                    {{ __('account.common.deadline') }} {{ $booking->payment_deadline->locale(app()->getLocale())->isoFormat('D MMM · HH:mm') }}
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

@push('scripts')
<script>
// Conto alla rovescia dei posti riservati. Lavora sull'ISO8601 stampato dal
// server (comprensivo di offset), quindi il fuso del browser non lo sposta.
// A zero ricarica la pagina: la scadenza vera la decide il server, che annulla
// il carrello e rimanda ai tour. Qui non si annulla nulla di propria iniziativa.
(function () {
    var box = document.querySelector('[data-checkout-countdown]');
    if (!box) return;

    var out = box.querySelector('[data-countdown-value]');
    var deadline = new Date(box.dataset.deadline).getTime();
    if (isNaN(deadline)) return;

    function tick() {
        var left = Math.floor((deadline - Date.now()) / 1000);

        if (left <= 0) {
            out.textContent = 'scaduto';
            window.location.reload();
            return;
        }

        var m = Math.floor(left / 60);
        var s = left % 60;
        out.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        setTimeout(tick, 1000);
    }

    tick();
})();
</script>
@endpush
