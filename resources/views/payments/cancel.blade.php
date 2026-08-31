@extends('layouts.public')

@section('title', __('account.cancel.title') . ' — ' . $booking->booking_number)

@section('content')

    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <div class="mb-3 wow fadeInUp" style="font-size:4rem">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <h1 class="mb-2 wow fadeInUp">{{ __('account.cancel.title') }}</h1>
                    <p class="lead mb-0">{{ __('account.cancel.intro') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="tg-tour-about-area pt-50 pb-70">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8">

                    <div class="text-center mb-4 p-4 rounded-4" style="background:#fff7ed;border:1px solid #fed7aa">
                        <h3 class="mb-2" style="color:#0E1B33">{{ __('account.common.booking') }} <strong>#{{ $booking->booking_number }}</strong></h3>
                        <p class="mb-0 text-muted">{{ __('account.cancel.no_charge') }}</p>
                    </div>

                    <div class="tg-tour-about-wrap">
                        <div class="tg-tour-about-content">

                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-water text-primary me-2"></i>{{ $booking->tour->name }}</h4>
                                @if($booking->departure)
                                    <p class="lh-28 mb-2">
                                        <i class="fa-regular fa-calendar me-1 text-primary"></i>
                                        {{ \Carbon\Carbon::parse($booking->departure->departure_date)->locale(app()->getLocale())->isoFormat('dddd D MMMM Y') }}
                                        alle {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}
                                    </p>
                                @endif
                                <p class="lh-28 mb-0">
                                    <strong>{{ __('account.cancel.total_due') }}</strong>
                                    <span class="text-primary fw-bold">€{{ number_format($booking->total_amount, 2, ',', '.') }}</span>
                                </p>
                            </div>

                            <div class="text-center mt-4 d-flex flex-wrap gap-2 justify-content-center">
                                <a href="{{ route('payment.show', $booking->uuid) }}" class="tg-btn tg-btn-switch-animation">
                                    <i class="fa-solid fa-arrow-rotate-right me-2"></i>Riprova il pagamento
                                </a>
                                <a href="{{ route('tours.show', $booking->tour->slug) }}" class="tg-btn tg-btn-transparent">
                                    <i class="fa-solid fa-arrow-left me-2"></i>Torna al tour
                                </a>
                            </div>

                            @if($booking->payment_deadline)
                                <p class="text-center text-muted small mt-3 mb-0">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    {{ __('account.common.deadline') }}: {{ \Carbon\Carbon::parse($booking->payment_deadline)->locale(app()->getLocale())->isoFormat('D MMM YYYY · HH:mm') }}
                                </p>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('head')
<style>
    .tg-btn-switch-animation { background: var(--tg-theme-primary); color: #fff; padding: 14px 26px; font-weight: 600; }
    .tg-btn-switch-animation:hover { filter: brightness(.88); color: #fff; }
    .tg-btn-transparent { padding: 14px 26px; font-weight: 600; border: 1.5px solid var(--tg-theme-primary); color: var(--tg-theme-primary); }
    .tg-btn-transparent:hover { background: var(--tg-theme-primary); color: #fff; }
</style>
@endpush
