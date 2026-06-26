@extends('layouts.b2b')

@section('title', 'Dashboard')

@php
    $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.');
@endphp

@section('content')

    {{-- Saluto --}}
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-1">Ciao, {{ $agency->agency_name ?: $agency->name }} 👋</h2>
        <p class="text-muted mb-0">Ecco il riepilogo della tua attività con Solarya Travel.</p>
    </div>

    {{-- Card riepilogo --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.06em">Prenotazioni</span>
                        <i class="bi bi-journal-check text-primary fs-5"></i>
                    </div>
                    <div class="fs-3 fw-bold mt-2">{{ $totalePrenotazioni }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.06em">Incassato generato</span>
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                    <div class="fs-3 fw-bold mt-2">{{ $eur($incassatoGenerato) }}</div>
                    <div class="small text-muted">Totale clienti (IVA incl.)</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.06em">Commissione maturata</span>
                        <i class="bi bi-percent text-warning fs-5"></i>
                    </div>
                    <div class="fs-3 fw-bold mt-2">{{ $eur($commissioneMaturata) }}</div>
                    <div class="small text-muted">La tua provvigione</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.06em">Da ricevere</span>
                        <i class="bi bi-hourglass-split text-danger fs-5"></i>
                    </div>
                    <div class="fs-3 fw-bold mt-2">{{ $eur($commissioneDaRicevere) }}</div>
                    <div class="small text-muted">Già pagata: {{ $eur($commissionePagata) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Accesso rapido --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <a href="{{ route('b2b.bookings.create') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-plus-circle-fill fs-1"></i>
                        <div>
                            <div class="h5 fw-bold mb-1">Nuova prenotazione</div>
                            <div class="small opacity-75">Crea una prenotazione per il tuo cliente.</div>
                        </div>
                        <i class="bi bi-arrow-right ms-auto fs-3"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-lg-4">
            <a href="{{ route('b2b.referral') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-qr-code fs-1 text-dark"></i>
                        <div>
                            <div class="h6 fw-bold mb-1 text-dark">Il tuo link & QR</div>
                            <div class="small text-muted">Da dare ai clienti.</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Ultime prenotazioni --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between py-3">
            <h3 class="h6 fw-bold mb-0">Ultime prenotazioni</h3>
            <a href="{{ route('b2b.bookings.index') }}" class="btn btn-sm btn-link text-decoration-none">Vedi tutte</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-muted">
                        <th class="ps-4">Prenotazione</th>
                        <th>Cliente</th>
                        <th>Tour</th>
                        <th class="text-end">Totale</th>
                        <th class="text-end">Commissione</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultime as $b)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('b2b.bookings.show', $b) }}" class="fw-semibold text-decoration-none">{{ $b->booking_number }}</a>
                                <div class="small text-muted">{{ $b->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td>{{ $b->customer_full_name }}</td>
                            <td class="small">{{ $b->tour?->name ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ $eur($b->total_amount) }}</td>
                            <td class="text-end">
                                @if($b->commission_status === 'reversed')
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Stornata</span>
                                @elseif($b->commission_amount !== null)
                                    <span class="fw-semibold text-success">{{ $eur($b->commission_amount) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>@include('b2b.partials.status-badge', ['status' => $b->status])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                Ancora nessuna prenotazione.
                                <a href="{{ route('b2b.bookings.create') }}">Creane una</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
