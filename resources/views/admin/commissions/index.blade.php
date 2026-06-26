@extends('layouts.admin')

@section('title', 'Commissioni agenzie')

@php $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.'); @endphp

@section('content')
    <div class="dash-page-header">
        <div>
            <h1>Commissioni agenzie</h1>
            <p class="mt-1 mb-0">Provvigioni B2B maturate e da liquidare, per mese.</p>
        </div>
        <form method="GET" class="d-flex align-items-end gap-2">
            <div>
                <label class="form-label small fw-semibold text-secondary mb-1">Mese</label>
                <input type="month" name="month" value="{{ $month }}" class="form-control" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    {{-- Totali del mese --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="dash-mini-stat"><div class="dash-mini-stat-value">{{ $eur($totals->generated) }}</div>
                <div class="dash-mini-stat-label"><i class="bi bi-cash-stack me-1"></i>Generato (IVA incl.)</div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-mini-stat"><div class="dash-mini-stat-value">{{ $eur($totals->earned) }}</div>
                <div class="dash-mini-stat-label"><i class="bi bi-percent me-1"></i>Commissioni maturate</div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-mini-stat"><div class="dash-mini-stat-value text-success">{{ $eur($totals->paid) }}</div>
                <div class="dash-mini-stat-label"><i class="bi bi-check-circle me-1"></i>Già pagate</div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-mini-stat"><div class="dash-mini-stat-value text-danger">{{ $eur($totals->due) }}</div>
                <div class="dash-mini-stat-label"><i class="bi bi-hourglass-split me-1"></i>Da liquidare</div></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header"><h3><i class="bi bi-building me-2 text-primary"></i>Per agenzia · {{ \Carbon\Carbon::parse($start)->locale('it')->isoFormat('MMMM YYYY') }}</h3></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-muted">
                        <th class="ps-4">Agenzia</th>
                        <th class="text-center">Prenotazioni</th>
                        <th class="text-end">Generato</th>
                        <th class="text-end">Commissione</th>
                        <th class="text-end">Pagata</th>
                        <th class="text-end">Da liquidare</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $row->agency?->agency_name ?: $row->agency?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $row->agency?->email }}</div>
                            </td>
                            <td class="text-center">{{ $row->bookings_count }}</td>
                            <td class="text-end">{{ $eur($row->total_generated) }}</td>
                            <td class="text-end fw-semibold">{{ $eur($row->commission_earned) }}</td>
                            <td class="text-end text-success">{{ $eur($row->commission_paid) }}</td>
                            <td class="text-end fw-bold {{ $row->commission_due > 0 ? 'text-danger' : 'text-muted' }}">{{ $eur($row->commission_due) }}</td>
                            <td class="text-end pe-4">
                                @if($row->agency)
                                    <a href="{{ route('admin.commissions.agency', ['agency' => $row->agency->id, 'month' => $month]) }}"
                                       class="btn btn-sm btn-outline-primary">Dettaglio</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>Nessuna prenotazione B2B in questo mese.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
