@extends('layouts.public')

@section('title', 'Le mie prenotazioni')

@php
    use Carbon\Carbon;
    $statusMap = [
        'pending' => ['label' => 'In attesa', 'icon' => 'fa-hourglass-half', 'color' => '#d97706', 'bg' => '#fef3c7', 'border' => '#fcd34d'],
        'confirmed' => ['label' => 'Confermata', 'icon' => 'fa-circle-check', 'color' => '#059669', 'bg' => '#ecfdf5', 'border' => '#6ee7b7'],
        'checked_in' => ['label' => 'Check-in effettuato', 'icon' => 'fa-user-check', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'border' => '#7dd3fc'],
        'completed' => ['label' => 'Completata', 'icon' => 'fa-flag-checkered', 'color' => '#1d4ed8', 'bg' => '#dbeafe', 'border' => '#93c5fd'],
        'cancelled' => ['label' => 'Annullata', 'icon' => 'fa-circle-xmark', 'color' => '#dc2626', 'bg' => '#fee2e2', 'border' => '#fca5a5'],
        'no_show' => ['label' => 'No show', 'icon' => 'fa-user-slash', 'color' => '#64748b', 'bg' => '#f1f5f9', 'border' => '#cbd5e1'],
    ];
@endphp

@section('content')

{{-- HERO --}}
<div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8 text-white">
                <h1 class="mb-2 wow fadeInUp">Le mie prenotazioni</h1>
                <p class="lead mb-0 wow fadeInUp" style="opacity:.9">Storico, prossime partenze e biglietti dei tuoi tour.</p>
            </div>
            <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
                <a href="{{ route('tours.index') }}" class="btn btn-light rounded-pill px-4 py-2 fw-semibold">
                    <i class="fa-solid fa-compass me-2"></i>Nuova prenotazione
                </a>
            </div>
        </div>
    </div>
</div>

<section class="py-5" style="background:#f8fafc;min-height:50vh">
    <div class="container">
        <div class="mx-auto">

            @if($bookings->isEmpty())
                {{-- EMPTY STATE --}}
                <div class="text-center bg-white rounded-4 p-5 shadow-sm" style="border:1px solid #e2e8f0">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width:88px;height:88px;background:rgba(0,102,204,.08);color:#0066cc;font-size:2.2rem">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-2" style="color:#0E1B33">Nessuna prenotazione ancora</h2>
                    <p class="text-muted mb-4">Scopri i nostri tour in catamarano lungo la Costiera. La prossima esperienza inizia qui.</p>
                    <a href="{{ route('tours.index') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-compass me-2"></i>Esplora i tour
                    </a>
                </div>
            @else
                {{-- BOOKING LIST --}}
                <div class="vstack gap-3">
                    @foreach($bookings as $booking)
                        @php
                            $key = $booking->status?->value ?? (string) $booking->status;
                            $s = $statusMap[$key] ?? $statusMap['pending'];
                            $isUpcoming = $booking->booking_date && $booking->booking_date->isFuture();
                            $isToday = $booking->booking_date && $booking->booking_date->isToday();
                        @endphp
                        <div class="bk-card position-relative">
                            <div class="bk-card-accent" style="background:{{ $s['color'] }}"></div>
                            <div class="row g-0 align-items-stretch">

                                {{-- Image / icon --}}
                                <div class="col-md-3 bk-card-image">
                                    @php
                                        $img = $booking->tour?->primaryImage;
                                    @endphp
                                    @if($img)
                                        <img src="{{ $img->url }}" alt="{{ $booking->tour->name }}">
                                    @else
                                        <div class="bk-card-image-fallback">
                                            <i class="fa-solid fa-water"></i>
                                        </div>
                                    @endif
                                    @if($isToday)
                                        <span class="bk-tag bk-tag-today"><i class="fa-solid fa-bolt me-1"></i>Oggi</span>
                                    @elseif($isUpcoming)
                                        <span class="bk-tag bk-tag-upcoming"><i class="fa-regular fa-calendar me-1"></i>In arrivo</span>
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="col-md-6 bk-card-body">
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <span class="bk-card-number">#{{ $booking->booking_number }}</span>
                                        <span class="bk-status-badge" style="color:{{ $s['color'] }};background:{{ $s['bg'] }};border-color:{{ $s['border'] }}">
                                            <i class="fa-solid {{ $s['icon'] }}"></i>{{ $s['label'] }}
                                        </span>
                                    </div>
                                    <h3 class="bk-card-title">{{ $booking->tour->name ?? 'Tour' }}</h3>
                                    <div class="bk-card-meta">
                                        @if($booking->booking_date)
                                            <span><i class="fa-regular fa-calendar"></i>{{ $booking->booking_date->locale('it')->isoFormat('ddd D MMM YYYY') }}</span>
                                        @endif
                                        @if($booking->departure?->start_time)
                                            <span><i class="fa-regular fa-clock"></i>{{ Carbon::parse($booking->departure->start_time)->format('H:i') }}</span>
                                        @endif
                                        <span><i class="fa-regular fa-user"></i>{{ $booking->seats }} {{ $booking->seats === 1 ? 'posto' : 'posti' }}</span>
                                    </div>
                                </div>

                                {{-- Side: price + actions --}}
                                <div class="col-md-3 bk-card-side">
                                    <div class="bk-price">€{{ number_format($booking->total_amount, 2, ',', '.') }}</div>
                                    <div class="bk-card-actions">
                                        <a href="{{ route('booking.show', $booking->uuid) }}" class="bk-btn bk-btn-primary">
                                            <i class="fa-solid fa-eye"></i>Dettagli
                                        </a>
                                        @if(in_array($key, ['confirmed', 'checked_in', 'completed']))
                                            <a href="{{ route('booking.tickets', $booking->uuid) }}" class="bk-btn bk-btn-ghost">
                                                <i class="fa-solid fa-ticket"></i>Biglietti
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($bookings->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $bookings->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</section>

@endsection

@push('head')
<style>
    .bk-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .bk-card:hover { box-shadow: 0 10px 32px rgba(15,23,42,.08); transform: translateY(-1px); }
    .bk-card-accent {
        position: absolute; left: 0; top: 0; bottom: 0;
        width: 4px;
    }
    .bk-card-image {
        position: relative;
        min-height: 180px;
        background: #f1f5f9;
        overflow: hidden;
    }
    .bk-card-image img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
        min-height: 180px;
    }
    .bk-card-image-fallback {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #0066cc 0%, #06b6d4 100%);
        color: #fff; font-size: 3rem;
        min-height: 180px;
    }
    .bk-tag {
        position: absolute; top: 12px; left: 12px;
        background: rgba(255,255,255,.95);
        backdrop-filter: blur(4px);
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        box-shadow: 0 2px 8px rgba(15,23,42,.1);
    }
    .bk-tag-today { color: #dc2626; }
    .bk-tag-upcoming { color: #0066cc; }

    .bk-card-body { padding: 20px 22px; }
    .bk-card-number {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        color: #94a3b8;
        font-family: ui-monospace, Menlo, monospace;
    }
    .bk-status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        border: 1px solid;
    }
    .bk-card-title {
        font-size: 1.18rem;
        font-weight: 800;
        color: #0E1B33;
        margin: 6px 0 10px;
        line-height: 1.25;
    }
    .bk-card-meta {
        display: flex; flex-wrap: wrap; gap: 14px;
        font-size: .88rem;
        color: #475569;
    }
    .bk-card-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .bk-card-meta i { color: #94a3b8; }

    .bk-card-side {
        padding: 20px 22px;
        display: flex; flex-direction: column;
        align-items: flex-end; justify-content: center;
        gap: 12px;
        border-left: 1px dashed #e2e8f0;
        background: #fafbfc;
    }
    @media (max-width: 767.98px) {
        .bk-card-side {
            border-left: none;
            border-top: 1px dashed #e2e8f0;
            align-items: stretch;
        }
    }
    .bk-price {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0066cc;
        line-height: 1;
    }
    .bk-card-actions { display: flex; flex-direction: column; gap: 8px; width: 100%; }
    .bk-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: .85rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all .15s ease;
    }
    .bk-btn-primary { background: #0066cc; color: #fff; }
    .bk-btn-primary:hover { background: #0052a3; color: #fff; }
    .bk-btn-ghost { background: #fff; color: #475569; border-color: #cbd5e1; }
    .bk-btn-ghost:hover { background: #f1f5f9; color: #0E1B33; }
</style>
@endpush
