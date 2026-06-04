@extends('layouts.public')

@section('title', 'Prenotazione ' . $booking->booking_number)

@php
    use Carbon\Carbon;
    $statusMap = [
        'pending' => ['label' => 'In attesa di pagamento', 'icon' => 'fa-hourglass-half', 'color' => '#d97706', 'bg' => '#fef3c7'],
        'deposit_paid' => ['label' => 'Acconto versato', 'icon' => 'fa-wallet', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
        'awaiting_transfer' => ['label' => 'In attesa di bonifico', 'icon' => 'fa-building-columns', 'color' => '#d97706', 'bg' => '#fef3c7'],
        'confirmed' => ['label' => 'Confermata', 'icon' => 'fa-circle-check', 'color' => '#059669', 'bg' => '#ecfdf5'],
        'checked_in' => ['label' => 'Check-in effettuato', 'icon' => 'fa-user-check', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
        'completed' => ['label' => 'Completata', 'icon' => 'fa-flag-checkered', 'color' => '#1d4ed8', 'bg' => '#dbeafe'],
        'cancelled' => ['label' => 'Annullata', 'icon' => 'fa-circle-xmark', 'color' => '#dc2626', 'bg' => '#fee2e2'],
        'no_show' => ['label' => 'No show', 'icon' => 'fa-user-slash', 'color' => '#64748b', 'bg' => '#f1f5f9'],
    ];
    $key = $booking->status?->value ?? (string) $booking->status;
    $s = $statusMap[$key] ?? $statusMap['pending'];
@endphp

@section('content')

{{-- HERO --}}
<div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-9 text-white">
                <a href="{{ route('bookings.my') }}" class="text-white text-decoration-none small mb-2 d-inline-block opacity-75">
                    <i class="fa-solid fa-arrow-left me-1"></i>Le mie prenotazioni
                </a>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="bk-status-badge d-inline-block fw-semibold" style="--sc:{{ $s['color'] }};--sb:{{ $s['bg'] }};border-radius:999px;padding:.35em .75em;font-size:.85em;line-height:1">
                        <i class="fa-solid {{ $s['icon'] }} me-1"></i>{{ $s['label'] }}
                    </span>
                    <span class="small opacity-75 font-monospace">#{{ $booking->booking_number }}</span>
                </div>
                <h1 class="mb-0 wow fadeInUp">{{ $booking->tour->name ?? 'Tour' }}</h1>
            </div>
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                <div class="text-white" style="opacity:.85">Totale</div>
                <div class="text-white fw-bold" style="font-size:2rem;line-height:1">€{{ number_format($booking->total_amount, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<section class="py-5" style="background:#f8fafc">
    <div class="container">
        <div class="row g-4">

            {{-- MAIN COLUMN --}}
            <div class="col-lg-8">

                {{-- Tour & Departure --}}
                <div class="bk-show-card mb-4">
                    <h3 class="bk-show-section-title"><i class="fa-solid fa-water text-primary"></i>Il tuo tour</h3>
                    <div class="d-flex gap-3 align-items-start">
                        @if($booking->tour?->primaryImage)
                            <img src="{{ $booking->tour->primaryImage->url }}"
                                 alt="" style="width:120px;height:120px;border-radius:14px;object-fit:cover;flex:0 0 auto">
                        @endif
                        <div class="flex-grow-1">
                            <h4 class="fw-bold mb-2" style="color:#0E1B33">{{ $booking->tour->name ?? 'Tour' }}</h4>
                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                @if($booking->booking_date)
                                    <span><i class="fa-regular fa-calendar text-primary me-1"></i>{{ $booking->booking_date->locale('it')->isoFormat('dddd D MMMM YYYY') }}</span>
                                @endif
                                @if($booking->departure?->start_time)
                                    <span><i class="fa-regular fa-clock text-primary me-1"></i>{{ Carbon::parse($booking->departure->start_time)->format('H:i') }}</span>
                                @endif
                            </div>
                            @if($booking->tour?->departure_point)
                                <div class="small text-muted mt-2"><i class="fa-solid fa-location-dot text-primary me-1"></i>{{ $booking->tour->departure_point }}</div>
                            @endif
                            @if($booking->tour?->duration_hours)
                                <div class="small text-muted mt-1"><i class="fa-regular fa-hourglass-half text-primary me-1"></i>Durata: {{ $booking->tour->duration_hours }}h</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Partecipanti --}}
                <div class="bk-show-card mb-4">
                    <h3 class="bk-show-section-title"><i class="fa-solid fa-users text-primary"></i>Partecipanti ({{ $booking->seatRecords->count() }})</h3>
                    <div class="bk-pax-list">
                        @foreach($booking->seatRecords as $seat)
                            <div class="bk-pax-row">
                                <span class="bk-pax-num">{{ $seat->seat_number }}</span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="color:#0E1B33">
                                        {{ trim(($seat->guest_first_name ?? '') . ' ' . ($seat->guest_last_name ?? '')) ?: '— Dati da compilare —' }}
                                        @if($seat->is_primary)
                                            <span class="badge ms-1" style="background:#fef3c7;color:#b45309">Prenotante</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        @if($seat->ageBracket)
                                            <i class="fa-regular fa-child me-1"></i>{{ $seat->ageBracket->label }}
                                            @if($seat->guest_date_of_birth)
                                                · {{ $seat->guest_date_of_birth->format('d/m/Y') }}
                                            @endif
                                        @else
                                            <i class="fa-regular fa-user me-1"></i>Adulto
                                        @endif
                                        @if($seat->tax_code)
                                            · <span class="font-monospace">{{ $seat->tax_code }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if($seat->boarded_at)
                                    <span class="badge" style="background:#dcfce7;color:#15803d"><i class="fa-solid fa-check me-1"></i>Imbarcato</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Addons --}}
                @if($booking->addons->isNotEmpty())
                    <div class="bk-show-card mb-4">
                        <h3 class="bk-show-section-title"><i class="fa-solid fa-plus text-primary"></i>Extra prenotati</h3>
                        <div class="bk-pax-list">
                            @foreach($booking->addons as $bookingAddon)
                                <div class="bk-pax-row">
                                    <span class="bk-pax-num"><i class="fa-solid fa-gift"></i></span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold" style="color:#0E1B33">{{ $bookingAddon->addon->name ?? 'Extra' }}</div>
                                        <div class="small text-muted">Quantità: {{ $bookingAddon->quantity }}</div>
                                    </div>
                                    <span class="fw-bold text-primary">€{{ number_format($bookingAddon->total_price, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Special requests --}}
                @if($booking->special_requests)
                    <div class="bk-show-card mb-4">
                        <h3 class="bk-show-section-title"><i class="fa-regular fa-message text-primary"></i>Richieste speciali</h3>
                        <p class="small text-muted mb-0">{{ $booking->special_requests }}</p>
                    </div>
                @endif

            </div>

            {{-- SIDEBAR --}}
            <div class="col-lg-4">

                {{-- Riepilogo importi --}}
                <div class="bk-show-card mb-4">
                    <h3 class="bk-show-section-title"><i class="fa-solid fa-receipt text-primary"></i>Riepilogo</h3>
                    <div class="bk-summary">
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
                            <div class="bk-summary-line" style="color:#059669">
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
                        <div class="bk-summary-total">
                            <span>Totale</span>
                            <span class="text-primary">€{{ number_format($booking->total_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Pagamento --}}
                @if($booking->payments->isNotEmpty())
                    @php $payment = $booking->payments->sortByDesc('created_at')->first(); @endphp
                    <div class="bk-show-card mb-4">
                        <h3 class="bk-show-section-title"><i class="fa-solid fa-credit-card text-primary"></i>Pagamento</h3>
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Stato</span>
                            <span class="fw-semibold">
                                @if($payment->status?->value === 'succeeded')
                                    <i class="fa-solid fa-circle-check" style="color:#059669"></i> Riuscito
                                @elseif($payment->status?->value === 'pending')
                                    <i class="fa-solid fa-hourglass-half" style="color:#d97706"></i> In attesa
                                @elseif($payment->status?->value === 'failed')
                                    <i class="fa-solid fa-circle-xmark" style="color:#dc2626"></i> Fallito
                                @elseif($payment->status?->value === 'refunded')
                                    <i class="fa-solid fa-arrow-rotate-left" style="color:#7c3aed"></i> Rimborsato
                                @else
                                    {{ $payment->status?->value ?? '—' }}
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Metodo</span>
                            <span class="fw-semibold text-none">{{ $payment->gateway ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Data</span>
                            <span class="fw-semibold">{{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Azioni --}}
                <div class="bk-show-card">
                    <h3 class="bk-show-section-title"><i class="fa-solid fa-bolt text-primary"></i>Azioni</h3>
                    <div class="d-flex flex-column gap-2">
                        @if(in_array($key, ['confirmed', 'checked_in', 'completed']))
                            <a href="{{ route('booking.tickets', $booking->uuid) }}" class="btn btn-primary rounded-pill fw-semibold">
                                <i class="fa-solid fa-ticket me-2"></i>Visualizza biglietti
                            </a>
                        @endif
                        @if($key === 'pending')
                            <a href="{{ route('payment.show', $booking->uuid) }}" class="btn btn-warning rounded-pill fw-semibold">
                                <i class="fa-solid fa-credit-card me-2"></i>Paga ora
                            </a>
                        @endif
                        @if($booking->hasBalanceDue())
                            <a href="{{ route('booking.balance', $booking->uuid) }}" class="btn btn-primary rounded-pill fw-semibold">
                                <i class="fa-solid fa-wallet me-2"></i>Paga il saldo (€ {{ number_format((float) $booking->balance_amount, 2, ',', '.') }})
                            </a>
                        @endif
                        @if($key === 'awaiting_transfer')
                            <a href="{{ route('booking.bank-transfer', $booking->uuid) }}" class="btn btn-outline-primary rounded-pill fw-semibold">
                                <i class="fa-solid fa-building-columns me-2"></i>Dati per il bonifico
                            </a>
                        @endif
                        @if($booking->canBeCancelled() && $booking->booking_date && $booking->booking_date->isFuture())
                            <form method="POST" action="{{ route('booking.cancel', $booking->uuid) }}"
                                  onsubmit="return confirm('Sei sicuro di voler annullare questa prenotazione?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger rounded-pill fw-semibold w-100">
                                    <i class="fa-solid fa-xmark me-2"></i>Annulla prenotazione
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@push('head')
<style>
    /* Specificity (0,2,0) batte .tg-breadcrumb-area span (0,1,1) anche con !important */
    .tg-breadcrumb-area .bk-status-badge {
        color: var(--sc) !important;
        background: var(--sb) !important;
    }

    .bk-show-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px;
    }
    .bk-show-section-title {
        font-size: 1rem;
        font-weight: 800;
        color: #0E1B33;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bk-pax-list { display: flex; flex-direction: column; gap: 4px; }
    .bk-pax-row {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .bk-pax-row:last-child { border-bottom: none; }
    .bk-pax-num {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: #eff6ff;
        color: #0066cc;
        font-weight: 700;
        font-size: .85rem;
        display: inline-flex;
        align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .bk-summary { display: flex; flex-direction: column; gap: 6px; }
    .bk-summary-line {
        display: flex;
        justify-content: space-between;
        font-size: .9rem;
        color: #475569;
    }
    .bk-summary-total {
        display: flex;
        justify-content: space-between;
        padding-top: 12px;
        margin-top: 8px;
        border-top: 1px solid #e2e8f0;
        font-size: 1.1rem;
        font-weight: 800;
        color: #0E1B33;
    }
</style>
@endpush
