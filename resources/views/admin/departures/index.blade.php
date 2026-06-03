@extends('layouts.admin')

@section('title', 'Assegnazione catamarani')

@section('content')
    <div class="dash-page-header">
        <div>
            <h1 class="mb-1">Assegnazione catamarani</h1>
            <p class="mb-0 text-muted">Partenze dal {{ $from->format('d/m/Y') }}, 14 giorni.</p>
        </div>

        <form method="GET" action="{{ route('admin.assignments.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <select name="tour" class="form-select form-select-sm" style="min-width:180px" onchange="this.form.submit()">
                <option value="">Tutti i tour</option>
                @foreach ($tours as $t)
                    <option value="{{ $t->id }}" @selected($selectedTour === $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $from->toDateString() }}" class="form-control form-control-sm"
                   style="max-width:160px" onchange="this.form.submit()">
            <a href="{{ route('admin.assignments.index') }}" class="btn btn-sm btn-outline-secondary">Oggi</a>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    @if ($byMonth->isEmpty())
        <div class="dash-card">
            <div class="dash-card-body text-center py-5">
                <i class="bi bi-calendar-x display-4 text-muted opacity-50 d-block mb-3"></i>
                <h4 class="fw-bold mb-1">Nessuna partenza in programma</h4>
                <p class="text-muted mb-0">Dal {{ $from->format('d/m/Y') }} non ci sono partenze nei 14 giorni successivi{{ $selectedTour ? ' per il tour selezionato' : '' }}.</p>
            </div>
        </div>
    @else
        @foreach ($byMonth as $month => $daysInMonth)
            @php $monthCarbon = \Carbon\Carbon::parse($month . '-01'); @endphp

            {{-- Intestazione MESE (una sola volta) --}}
            <div class="asg-month-head">{{ ucfirst($monthCarbon->translatedFormat('F Y')) }}</div>

            @foreach ($daysInMonth as $day => $dayBlocks)
                @php $dayCarbon = \Carbon\Carbon::parse($day); @endphp
                <div class="asg-day">
                    {{-- Etichetta GIORNO compatta (senza mese) --}}
                    <div class="asg-day-label">
                        <span class="asg-day-num">{{ $dayCarbon->format('d') }}</span>
                        <span class="asg-day-dow">
                            @if ($dayCarbon->isToday()) Oggi
                            @elseif ($dayCarbon->isTomorrow()) Domani
                            @else {{ ucfirst($dayCarbon->translatedFormat('D')) }} @endif
                        </span>
                    </div>

                    {{-- Partenze del giorno --}}
                    <div class="asg-day-body">
                        @foreach ($dayBlocks as $block)
                            @php
                                $dep = $block['departure'];
                                $depTime = \Carbon\Carbon::parse($dep->start_time)->format('H:i');
                                $totalSeats = $block['catamarans']->sum(fn ($c) => $block['stats'][$c->id]['count'] ?? 0)
                                             + $block['unassignedCount'];
                                $totalCap = $block['catamarans']->sum('capacity');
                            @endphp
                            <div class="dash-card mb-3">
                                <div class="dash-card-body">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="asg-time">{{ $depTime }}</span>
                                            <div>
                                                <div class="fw-bold">{{ $dep->tour?->name ?? 'Tour' }}</div>
                                                <div class="text-muted small">
                                                    {{ $totalSeats }} passegger{{ $totalSeats === 1 ? 'o' : 'i' }}@if ($totalCap > 0) · capienza {{ $totalCap }}@endif
                                                    @if ($block['unassignedCount'] > 0)
                                                        · <span class="text-warning fw-semibold">{{ $block['unassignedCount'] }} da assegnare</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('admin.assignments.show', $dep) }}" class="btn btn-sm btn-light border">
                                                <i class="bi bi-arrows-fullscreen me-1"></i>Apri
                                            </a>
                                            <a href="{{ route('admin.boarding.show', $dep) }}" class="btn btn-sm btn-light border">
                                                <i class="bi bi-qr-code-scan me-1"></i>Imbarco
                                            </a>
                                        </div>
                                    </div>

                                    @include('admin.departures._assignment_block', [
                                        'departure' => $dep,
                                        'catamarans' => $block['catamarans'],
                                        'byCatamaran' => $block['byCatamaran'],
                                        'stats' => $block['stats'],
                                        'unassignedCount' => $block['unassignedCount'],
                                    ])
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endforeach
    @endif

@push('styles')
<style>
    .asg-month-head {
        font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        color: var(--bs-primary, #6f42c1); margin: 1.75rem 0 .75rem; padding-bottom: .4rem;
        border-bottom: 2px solid #eef0f4;
    }
    .asg-month-head:first-child { margin-top: 0; }
    .asg-day { display: flex; gap: 1rem; margin-bottom: 1.25rem; }
    .asg-day-label {
        flex: 0 0 56px; text-align: center; padding-top: .35rem;
        position: sticky; top: 1rem; align-self: flex-start;
    }
    .asg-day-num { display: block; font-size: 1.5rem; font-weight: 700; line-height: 1; color: #1e2330; }
    .asg-day-dow { display: block; font-size: .72rem; text-transform: uppercase; color: #8a93a3; font-weight: 600; margin-top: .15rem; }
    .asg-day-body { flex: 1 1 auto; min-width: 0; }
    .asg-time {
        flex: 0 0 auto; font-weight: 700; font-size: .95rem; color: var(--bs-primary, #6f42c1);
        background: #f4f0fd; border-radius: 8px; padding: .35rem .6rem;
    }
    @media (max-width: 575px) {
        .asg-day { flex-direction: column; gap: .5rem; }
        .asg-day-label { display: flex; gap: .5rem; align-items: baseline; position: static; }
        .asg-day-num { font-size: 1.1rem; }
    }
</style>
@endpush

@push('scripts')
<script>
    // Auto-submit select "sposta passeggero" (stesso comportamento della pagina singola)
    document.querySelectorAll('form[data-move-seat]').forEach(function (form) {
        var sel = form.querySelector('select[name="catamaran_id"]');
        if (!sel) return;
        sel.addEventListener('change', function () {
            if (!sel.value) return;
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
