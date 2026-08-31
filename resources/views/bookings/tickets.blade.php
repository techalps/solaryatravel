@extends('layouts.public')

@section('title', __('account.tickets.title') . ' · ' . $booking->booking_number)

@php
    use Carbon\Carbon;
@endphp

@section('content')

{{-- HERO --}}
<div class="tg-breadcrumb-area pt-150 pb-90 p-relative no-print" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8 text-white">
                <a href="{{ route('booking.show', $booking->uuid) }}" class="text-white text-decoration-none small mb-2 d-inline-block opacity-75">
                    <i class="fa-solid fa-arrow-left me-1"></i>Torna alla prenotazione
                </a>
                <h1 class="mb-2 wow fadeInUp"><i class="fa-solid fa-ticket me-2"></i>{{ __('account.tickets.title') }}</h1>
                <p class="lead mb-0 opacity-90">
                    {{ __('account.common.booking') }} <span class="font-monospace">#{{ $booking->booking_number }}</span>
                    · {{ $booking->seatRecords->count() }} {{ $booking->seatRecords->count() === 1 ? 'passeggero' : 'passeggeri' }}
                </p>
            </div>
            <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
                <button onclick="window.print()" class="btn btn-light rounded-pill px-4 py-2 fw-semibold">
                    <i class="fa-solid fa-print me-2"></i>Stampa biglietti
                </button>
            </div>
        </div>
    </div>
</div>

<section class="py-5" style="background:#f8fafc">
    <div class="container">
        <div class="mx-auto">

            <div class="alert alert-info border-0 rounded-3 d-flex align-items-start gap-2 mb-4 no-print" style="background:#dbeafe;color:#1e40af">
                <i class="fa-solid fa-circle-info mt-1"></i>
                <div class="small">
                    <strong>{{ __('account.tickets.one_per_passenger') }}</strong> Mostralo all'imbarco (anche dal cellulare).
                    Il personale scansionerà il QR per verificare la presenza.
                </div>
            </div>

            @forelse($booking->seatRecords as $seat)
                <div class="ticket-card mb-4">
                    {{-- Perforated band on left --}}
                    <div class="ticket-perforation"></div>

                    <div class="ticket-inner">
                        {{-- Info side --}}
                        <div class="ticket-info">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="ticket-label">{{ __('account.tickets.passenger') }}</div>
                                    <div class="ticket-pax-name">
                                        @if($seat->is_primary)<i class="fa-solid fa-star" style="color:#f59e0b"></i> @endif
                                        {{ trim(($seat->guest_first_name ?? '') . ' ' . ($seat->guest_last_name ?? '')) ?: '— Da compilare —' }}
                                    </div>
                                    <div class="d-flex gap-1 mt-2 flex-wrap">
                                        @if($seat->is_primary)
                                            <span class="ticket-badge" style="background:#fef3c7;color:#b45309">{{ __('account.common.lead_booker') }}</span>
                                        @endif
                                        @if($seat->ageBracket)
                                            <span class="ticket-badge" style="background:#dbeafe;color:#1e40af">{{ $seat->ageBracket->label }}</span>
                                        @else
                                            <span class="ticket-badge" style="background:#ecfdf5;color:#059669">{{ __('account.common.adult') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="ticket-label">{{ __('account.tickets.seat') }}</div>
                                    <div class="ticket-seat-num">#{{ $seat->seat_number ?? $loop->iteration }}</div>
                                </div>
                            </div>

                            <div class="ticket-divider"></div>

                            <div class="ticket-meta">
                                <div>
                                    <div class="ticket-label">{{ __('account.tickets.tour') }}</div>
                                    <div class="ticket-value">{{ $booking->tour->name ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="ticket-label">{{ __('account.common.date') }}</div>
                                    <div class="ticket-value">
                                        @if($booking->booking_date)
                                            {{ $booking->booking_date->locale(app()->getLocale())->isoFormat('D MMM YYYY') }}
                                        @else — @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="ticket-label">{{ __('account.tickets.departure') }}</div>
                                    <div class="ticket-value">
                                        @if($booking->departure?->start_time)
                                            <i class="fa-regular fa-clock"></i> {{ Carbon::parse($booking->departure->start_time)->format('H:i') }}
                                        @else — @endif
                                    </div>
                                </div>
                                @if($seat->catamaran)
                                    <div>
                                        <div class="ticket-label">{{ __('account.tickets.catamaran') }}</div>
                                        <div class="ticket-value">{{ $seat->catamaran->name }}</div>
                                    </div>
                                @endif
                                @if($booking->tour?->departure_point)
                                    <div class="grid-col-2">
                                        <div class="ticket-label">{{ __('account.tickets.boarding_point') }}</div>
                                        <div class="ticket-value"><i class="fa-solid fa-location-dot text-danger me-1"></i>{{ $booking->tour->departure_point }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- QR side --}}
                        <div class="ticket-qr-side">
                            <div class="ticket-qr-box">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($seat->qr_code) }}&ecc=H&margin=2"
                                     alt="QR biglietto" style="display:block;width:200px;height:200px">
                            </div>
                            <div class="ticket-code"><code>{{ $seat->qr_code }}</code></div>
                            <div class="ticket-code-sub">{{ __('account.common.booking') }} #{{ $booking->booking_number }}</div>
                            @if($seat->boarded_at)
                                <div class="ticket-stamp"><i class="fa-solid fa-check"></i>{{ __('account.tickets.boarded') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning rounded-3 text-center py-4">
                    <i class="fa-solid fa-triangle-exclamation fs-3 d-block mb-2"></i>
                    Nessun passeggero registrato per questa prenotazione.
                </div>
            @endforelse

        </div>
    </div>
</section>

@endsection

@push('head')
<style>
    /* Print */
    @media print {
        @page { size: A4; margin: 12mm; }
        .no-print, header, footer, .navbar { display: none !important; }
        body { background: #fff !important; }
        .ticket-card { page-break-inside: avoid; box-shadow: none !important; border: 2px dashed #0066cc; }
        section { padding: 0 !important; background: #fff !important; }
    }

    .ticket-card {
        position: relative;
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15,23,42,.06);
        border: 1px solid #e2e8f0;
    }
    .ticket-perforation {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 6px;
        background: repeating-linear-gradient(0deg, #0066cc 0 6px, transparent 6px 12px);
    }
    .ticket-inner {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 0;
    }
    @media (max-width: 767.98px) {
        .ticket-inner { grid-template-columns: 1fr; }
        .ticket-qr-side { border-left: none !important; border-top: 2px dashed #cbd5e1 !important; }
    }
    .ticket-info { padding: 26px 28px 26px 32px; }
    .ticket-qr-side {
        padding: 26px;
        text-align: center;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-left: 2px dashed #cbd5e1;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 8px;
        position: relative;
    }
    .ticket-qr-box {
        background: #fff;
        padding: 10px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15,23,42,.06);
    }
    .ticket-code { font-size: .85rem; font-family: ui-monospace, Menlo, monospace; color: #475569; margin-top: 6px; }
    .ticket-code-sub { font-size: .72rem; color: #94a3b8; }

    .ticket-label {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 3px;
    }
    .ticket-value { font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .ticket-pax-name { font-size: 1.5rem; font-weight: 800; color: #0066cc; line-height: 1.15; }
    .ticket-seat-num {
        display: inline-flex;
        align-items: center; justify-content: center;
        min-width: 50px; height: 50px;
        background: #0066cc;
        color: #fff;
        border-radius: 12px;
        font-size: 1.2rem;
        font-weight: 800;
        padding: 0 10px;
    }
    .ticket-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
    }
    .ticket-divider {
        height: 1px;
        background: repeating-linear-gradient(90deg, #cbd5e1 0 6px, transparent 6px 12px);
        margin: 18px 0;
    }
    .ticket-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px 18px;
    }
    .ticket-meta .grid-col-2 { grid-column: span 2; }

    .ticket-stamp {
        position: absolute;
        top: 16px; right: -8px;
        background: #059669;
        color: #fff;
        padding: 4px 14px;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        transform: rotate(8deg);
        box-shadow: 0 2px 6px rgba(5,150,105,.3);
        border-radius: 4px;
    }
    .ticket-stamp i { margin-right: 4px; }
</style>
@endpush
