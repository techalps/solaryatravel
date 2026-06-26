@extends('layouts.admin')

@section('title', 'Commissioni · ' . ($agency->agency_name ?: $agency->name))

@php $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.'); @endphp

@section('content')
    <div class="dash-page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.commissions.index', ['month' => $month]) }}" class="dash-icon-btn" title="Torna alle commissioni"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="mb-0">{{ $agency->agency_name ?: $agency->name }}</h1>
                <p class="mt-1 mb-0">Provvigione {{ rtrim(rtrim(number_format((float) $agency->commission_rate, 2), '0'), '.') }}% · {{ \Carbon\Carbon::parse($start)->locale('it')->isoFormat('MMMM YYYY') }}</p>
            </div>
        </div>
        <form method="GET">
            <input type="month" name="month" value="{{ $month }}" class="form-control" onchange="this.form.submit()">
        </form>
    </div>

    <div class="dash-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-muted">
                        <th class="ps-4">Prenotazione</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th class="text-end">Totale</th>
                        <th class="text-end">Commissione</th>
                        <th>Stato comm.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                        <tr>
                            <td class="ps-4"><a href="{{ route('admin.bookings.show', $b) }}" class="fw-semibold text-decoration-none">{{ $b->booking_number }}</a></td>
                            <td>{{ $b->customer_full_name }}</td>
                            <td class="small">{{ optional($b->booking_date)->format('d/m/Y') }}</td>
                            <td class="text-end">{{ $eur($b->total_amount) }}</td>
                            <td class="text-end fw-semibold">
                                {{ $b->commission_status === 'reversed' ? '—' : $eur($b->commission_amount) }}
                            </td>
                            <td>
                                @if($b->commission_status === 'reversed')
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Stornata</span>
                                @elseif($b->commission_paid)
                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle-fill me-1"></i>Pagata{{ $b->commission_paid_at ? ' · '.$b->commission_paid_at->format('d/m/Y') : '' }}</span>
                                @else
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split me-1"></i>Da liquidare</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($b->commission_status !== 'reversed' && ! $b->commission_paid)
                                    <form method="POST" action="{{ route('admin.commissions.mark-paid', $b) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Segna pagata</button>
                                    </form>
                                @elseif($b->commission_paid)
                                    <form method="POST" action="{{ route('admin.commissions.unmark-paid', $b) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Annulla marcatura"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">Nessuna prenotazione in questo mese.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
