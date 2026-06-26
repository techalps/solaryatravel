@extends('layouts.admin')

@section('title', 'Commissioni · ' . ($agency->agency_name ?: $agency->name))

@php
    $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.');
    $payable = $bookings->filter(fn ($b) => $b->commission_status !== 'reversed' && ! $b->commission_paid);
    $payableTotal = $payable->sum(fn ($b) => (float) $b->commission_amount);
@endphp

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

    <form method="POST" action="{{ route('admin.commissions.mark-paid-bulk') }}" id="bulkPayForm">
        @csrf
        <div class="dash-card">
            {{-- Barra azioni bulk --}}
            @if($payable->isNotEmpty())
                <div class="dash-card-body border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <div class="small text-muted">
                        <span id="bulkCount">0</span> selezionate ·
                        da liquidare in totale: <strong>{{ $eur($payableTotal) }}</strong>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm" id="bulkPayBtn" disabled>
                        <i class="bi bi-check2-all me-1"></i>Segna pagate le selezionate
                    </button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-4" style="width:38px">
                                @if($payable->isNotEmpty())
                                    <input type="checkbox" class="form-check-input" id="selectAll" title="Seleziona tutte le liquidabili">
                                @endif
                            </th>
                            <th>Prenotazione</th>
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
                            @php $isPayable = $b->commission_status !== 'reversed' && ! $b->commission_paid; @endphp
                            <tr>
                                <td class="ps-4">
                                    @if($isPayable)
                                        <input type="checkbox" name="booking_ids[]" value="{{ $b->id }}"
                                               class="form-check-input bulk-cb" data-amount="{{ $b->commission_amount }}">
                                    @endif
                                </td>
                                <td><a href="{{ route('admin.bookings.show', $b) }}" class="fw-semibold text-decoration-none">{{ $b->booking_number }}</a></td>
                                <td>{{ $b->customer_full_name }}</td>
                                <td class="small">{{ optional($b->booking_date)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $eur($b->total_amount) }}</td>
                                <td class="text-end fw-semibold">{{ $b->commission_status === 'reversed' ? '—' : $eur($b->commission_amount) }}</td>
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
                                    @if($isPayable)
                                        <button type="submit" formaction="{{ route('admin.commissions.mark-paid', $b) }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg me-1"></i>Segna pagata
                                        </button>
                                    @elseif($b->commission_paid)
                                        <button type="submit" formaction="{{ route('admin.commissions.unmark-paid', $b) }}" class="btn btn-sm btn-outline-secondary" title="Annulla marcatura"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Nessuna prenotazione in questo mese.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const selectAll = document.getElementById('selectAll');
        const boxes = Array.from(document.querySelectorAll('.bulk-cb'));
        const btn = document.getElementById('bulkPayBtn');
        const count = document.getElementById('bulkCount');
        if (!boxes.length) return;

        function refresh() {
            const checked = boxes.filter(b => b.checked);
            if (count) count.textContent = checked.length;
            if (btn) btn.disabled = checked.length === 0;
            if (selectAll) selectAll.checked = checked.length === boxes.length;
        }
        selectAll?.addEventListener('change', () => { boxes.forEach(b => b.checked = selectAll.checked); refresh(); });
        boxes.forEach(b => b.addEventListener('change', refresh));
        refresh();
    })();
</script>
@endpush
