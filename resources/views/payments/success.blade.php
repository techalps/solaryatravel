@extends('layouts.public')

@section('title', 'Prenotazione confermata — ' . $booking->booking_number)

@section('content')

    {{-- HERO --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(135deg, #0e9f6e 0%, #06b6d4 100%);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <div class="mb-3 wow fadeInUp" style="font-size:4rem">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h1 class="mb-2 wow fadeInUp">Prenotazione confermata!</h1>
                    <p class="lead mb-0">Grazie {{ $booking->customer_first_name }}, abbiamo ricevuto il tuo pagamento.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="tg-tour-about-area pt-50 pb-70">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-9">

                    {{-- Confirmation card --}}
                    @if($booking->hasBalanceDue())
                        <div class="text-center mb-4 p-4 rounded-4" style="background:#eff6ff;border:1px solid #bfdbfe">
                            <h3 class="mb-2" style="color:#0E1B33">Acconto ricevuto · <strong>#{{ $booking->booking_number }}</strong></h3>
                            <p class="mb-2 text-muted">Grazie! Abbiamo registrato l'acconto. Il tuo posto è confermato.</p>
                            <p class="mb-3 fw-semibold" style="color:#0E1B33">Saldo residuo: € {{ number_format((float) $booking->balance_amount, 2, ',', '.') }}@if($booking->balance_due_at) · entro il {{ \Carbon\Carbon::parse($booking->balance_due_at)->format('d/m/Y H:i') }}@endif</p>
                            <a href="{{ route('booking.balance', $booking->uuid) }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                <i class="fa-solid fa-wallet me-2"></i>Paga il saldo
                            </a>
                            <p class="mb-0 mt-2 text-muted small">Riceverai i biglietti via email una volta completato il saldo.</p>
                        </div>
                    @else
                        <div class="text-center mb-4 p-4 rounded-4" style="background:#f0fdf4;border:1px solid #bbf7d0">
                            <h3 class="mb-2" style="color:#0E1B33">Prenotazione <strong>#{{ $booking->booking_number }}</strong></h3>
                            <p class="mb-0 text-muted">
                                <i class="fa-regular fa-envelope me-1"></i>
                                Abbiamo inviato i biglietti e la ricevuta a <strong>{{ $booking->customer_email }}</strong>
                            </p>
                        </div>
                    @endif

                    <div class="tg-tour-about-wrap">
                        <div class="tg-tour-about-content">

                            {{-- Tour & Departure --}}
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-water text-primary me-2"></i>Il tuo tour</h4>
                                <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#fafafa;border:1px solid #eef0f3">
                                    @if($booking->tour->primaryImage)
                                        <img src="{{ $booking->tour->primaryImage->url }}" alt="" style="width:110px;height:110px;border-radius:12px;object-fit:cover;flex:0 0 auto">
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
                                        <div class="text-muted small"><i class="fa-regular fa-user-group me-1 text-primary"></i>{{ $booking->seats }} {{ $booking->seats === 1 ? 'partecipante' : 'partecipanti' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="tg-tour-about-border mb-30"></div>

                            {{-- QR per ogni partecipante --}}
                            @php $seats = $booking->seatRecords()->orderBy('seat_number')->get(); @endphp
                            @if($seats->isNotEmpty())
                                <div class="tg-tour-about-inner mb-30">
                                    <h4 class="tg-tour-about-title mb-10 text-center"><i class="fa-solid fa-qrcode text-primary me-2"></i>Biglietti di tutti i partecipanti</h4>
                                    <p class="text-muted small text-center mb-4">
                                        Ogni partecipante ha il proprio biglietto.
                                    </p>

                                    <div class="row g-3 justify-content-center">
                                        @foreach($seats as $seat)
                                            <div class="col-md-6">
                                                <div class="p-3 rounded-3 text-center h-100" style="background:#fff;border:1px solid #eef0f3">
                                                    <div class="small fw-bold mb-2" style="color:#0E1B33">
                                                        <i class="fa-solid fa-ticket me-1 text-primary"></i>Posto #{{ $seat->seat_number }}
                                                        @if($seat->is_primary)
                                                            <span class="badge bg-warning-subtle text-warning ms-1">Prenotante</span>
                                                        @elseif($seat->ageBracket)
                                                            <span class="badge bg-info-subtle text-info ms-1">{{ $seat->ageBracket->label }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted mb-2" style="min-height:1.4em">
                                                        {{ trim(($seat->guest_first_name ?? '') . ' ' . ($seat->guest_last_name ?? '')) ?: '— Nome da compilare —' }}
                                                    </div>
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($seat->qr_code) }}" alt="QR" style="display:block;margin:0 auto;max-width:180px">
                                                    <div class="mt-2 small text-muted"><code>{{ $seat->qr_code }}</code></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="text-center mt-3">
                                        <a href="{{ route('booking.tickets', $booking->uuid) }}" class="btn btn-outline-primary rounded-pill px-3">
                                            <i class="fa-solid fa-print me-2"></i>Apri la pagina biglietti (stampabile)
                                        </a>
                                    </div>
                                </div>
                                <div class="tg-tour-about-border mb-30"></div>
                            @endif

                            {{-- Summary --}}
                            <div class="tg-tour-about-inner mb-30">
                                <h4 class="tg-tour-about-title mb-15"><i class="fa-solid fa-receipt text-primary me-2"></i>Riepilogo importi</h4>
                                <div class="bk-summary-mini">
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
                                    <div class="bk-summary-line fw-bold pt-2 mt-2" style="border-top:1px solid #e4e4e4;font-size:1.1rem">
                                        <span>Totale pagato</span>
                                        <span class="text-primary">€{{ number_format($booking->total_amount, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="{{ route('home') }}" class="tg-btn tg-btn-switch-animation me-2"><i class="fa-solid fa-house me-2"></i>Torna alla home</a>
                                <a href="{{ route('tours.index') }}" class="tg-btn tg-btn-transparent"><i class="fa-solid fa-compass me-2"></i>Esplora altri tour</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('head')
<style>
    .bk-summary-mini { background: #fafafa; border-radius: 10px; padding: .85rem 1rem; }
    .bk-summary-line { display: flex; justify-content: space-between; padding: .25rem 0; font-size: .92rem; color: #0E1B33; }
    .bk-summary-line.discount { color: #198754; }

    .tg-btn-switch-animation { background: var(--tg-theme-primary); color: #fff; padding: 14px 26px; font-weight: 600; }
    .tg-btn-switch-animation:hover { filter: brightness(.88); color: #fff; }
    .tg-btn-transparent { padding: 14px 26px; font-weight: 600; border: 1.5px solid var(--tg-theme-primary); color: var(--tg-theme-primary); }
    .tg-btn-transparent:hover { background: var(--tg-theme-primary); color: #fff; }
</style>
@endpush
