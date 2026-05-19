@extends('layouts.admin')

@section('title', 'Assegnazione catamarani — ' . $departure->tour?->name)

@section('content')
    @php
        $departureDate = \Carbon\Carbon::parse($departure->departure_date);
        $departureTime = \Carbon\Carbon::parse($departure->start_time);
    @endphp

    {{-- Header --}}
    <div class="dash-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.assignments.index', ['date' => $departureDate->toDateString()]) }}" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left"></i> Assegnazione del giorno
                </a>
            </div>
            <h1 class="mb-1">Assegnazione catamarani</h1>
            <p class="mb-0 text-muted">
                <span class="fw-semibold text-dark">{{ $departure->tour?->name }}</span>
                · {{ $departureDate->format('d/m/Y') }}
                · {{ $departureTime->format('H:i') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.boarding.show', $departure) }}" class="btn btn-light rounded-pill border px-3 fw-semibold">
                <i class="bi bi-qr-code-scan me-2"></i>Vai all'imbarco
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    @include('admin.departures._assignment_block', [
        'departure' => $departure,
        'catamarans' => $catamarans,
        'byCatamaran' => $byCatamaran,
        'stats' => $stats,
        'unassignedCount' => $unassignedCount,
    ])

@push('scripts')
<script>
    // Submit automatico dei form "sposta" quando l'utente cambia il select.
    document.querySelectorAll('form[data-move-seat]').forEach(function (form) {
        var sel = form.querySelector('select[name="catamaran_id"]');
        if (!sel) return;
        sel.addEventListener('change', function () {
            if (!sel.value) return;
            // Conferma se il catamarano destinazione è pieno (lo si capisce dal label dell'option)
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.full === '1') {
                if (!confirm('Il catamarano selezionato è al completo. Procedere comunque?')) {
                    sel.value = sel.dataset.initial || '';
                    return;
                }
            }
            form.submit();
        });
    });
</script>
@endpush
@endsection
