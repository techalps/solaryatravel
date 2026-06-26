@extends('layouts.b2b')

@section('title', 'Le mie prenotazioni')

@php $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.'); @endphp

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1">Le mie prenotazioni</h2>
            <p class="text-muted mb-0">Tutte le prenotazioni che hai generato, con la relativa commissione.</p>
        </div>
        <a href="{{ route('b2b.bookings.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nuova prenotazione
        </a>
    </div>

    {{-- Filtri --}}
    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold text-secondary mb-1">Cerca</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Numero, nome o email cliente">
                </div>
                <div class="col-8 col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Stato</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i></button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('b2b.bookings.index') }}" class="btn btn-outline-secondary" title="Azzera"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Tabella --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-muted">
                        <th class="ps-4">Prenotazione</th>
                        <th>Cliente</th>
                        <th>Tour / Data</th>
                        <th class="text-end">Totale</th>
                        <th class="text-end">Commissione</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('b2b.bookings.show', $b) }}" class="fw-semibold text-decoration-none">{{ $b->booking_number }}</a>
                                <div class="small text-muted">{{ $b->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div>{{ $b->customer_full_name }}</div>
                                <div class="small text-muted">{{ $b->customer_email }}</div>
                            </td>
                            <td class="small">
                                <div>{{ $b->tour?->name ?? '—' }}</div>
                                <div class="text-muted">{{ optional($b->booking_date)->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-end fw-semibold">{{ $eur($b->total_amount) }}</td>
                            <td class="text-end">
                                @if($b->commission_status === 'reversed')
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Stornata</span>
                                @elseif($b->commission_amount !== null)
                                    <div class="fw-semibold text-success">{{ $eur($b->commission_amount) }}</div>
                                    <div class="small {{ $b->commission_paid ? 'text-success' : 'text-muted' }}">
                                        <i class="bi {{ $b->commission_paid ? 'bi-check-circle-fill' : 'bi-hourglass-split' }} me-1"></i>
                                        {{ $b->commission_paid ? 'Pagata' : 'Da ricevere' }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>@include('b2b.partials.status-badge', ['status' => $b->status])</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('b2b.bookings.show', $b) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                Nessuna prenotazione trovata.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bookings->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

@endsection
