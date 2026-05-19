@extends('layouts.admin')

@section('title', 'Assegnazione catamarani — ' . $date->format('d/m/Y'))

@section('content')
    @php
        $isoDate = $date->toDateString();
        $today = \Carbon\Carbon::today();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();
        $isToday = $isoDate === $today->toDateString();
    @endphp

    {{-- Header con filtro data --}}
    <div class="dash-page-header">
        <div>
            <h1 class="mb-1">Assegnazione catamarani</h1>
            <p class="mb-0 text-muted">
                {{ $date->locale('it')->isoFormat('dddd D MMMM YYYY') }}
                @if ($isToday) <span class="badge bg-primary-subtle text-primary ms-1">Oggi</span> @endif
            </p>
        </div>

        <form method="GET" action="{{ route('admin.assignments.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.assignments.index', ['date' => $yesterday->toDateString()]) }}"
               class="btn btn-light border rounded-pill px-3" title="Giorno precedente">
                <i class="bi bi-chevron-left"></i>
            </a>
            <input type="date" name="date" value="{{ $isoDate }}" class="form-control"
                   style="max-width:170px" onchange="this.form.submit()">
            <a href="{{ route('admin.assignments.index', ['date' => $tomorrow->toDateString()]) }}"
               class="btn btn-light border rounded-pill px-3" title="Giorno successivo">
                <i class="bi bi-chevron-right"></i>
            </a>
            @unless ($isToday)
                <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-primary rounded-pill px-3 fw-semibold">
                    Oggi
                </a>
            @endunless
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    @if ($byTour->isEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <i class="bi bi-calendar-x display-4 text-muted d-block mb-3"></i>
                <h4 class="text-muted">Nessuna partenza in programma</h4>
                <p class="text-muted mb-0">Non ci sono tour attivi con partenze il {{ $date->format('d/m/Y') }}.</p>
            </div>
        </div>
    @else
        @foreach ($byTour as $tourId => $tourBlocks)
            @php $tour = $tourBlocks->first()['departure']->tour; @endphp
            <section class="mb-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h2 class="h4 fw-bold mb-0">
                        <i class="bi bi-compass me-2 text-primary"></i>{{ $tour->name }}
                    </h2>
                    <a href="{{ route('admin.tours.edit', $tour) }}" class="small text-decoration-none text-muted">
                        <i class="bi bi-pencil"></i> modifica tour
                    </a>
                </div>

                @foreach ($tourBlocks as $block)
                    @php
                        $dep = $block['departure'];
                        $depTime = \Carbon\Carbon::parse($dep->start_time)->format('H:i');
                        $totalSeats = $block['catamarans']->sum(fn ($c) => $block['stats'][$c->id]['count'] ?? 0)
                                     + $block['unassignedCount'];
                        $totalCap = $block['catamarans']->sum('capacity');
                    @endphp
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h3 class="h5 fw-bold mb-1">
                                        <i class="bi bi-clock me-2 text-muted"></i>Partenza {{ $depTime }}
                                    </h3>
                                    <div class="text-muted small">
                                        {{ $totalSeats }} passegger{{ $totalSeats === 1 ? 'o' : 'i' }}
                                        @if ($totalCap > 0) · capienza totale {{ $totalCap }} @endif
                                        @if ($block['unassignedCount'] > 0)
                                            · <span class="text-warning fw-semibold">{{ $block['unassignedCount'] }} da assegnare</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.assignments.show', $dep) }}" class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold">
                                        <i class="bi bi-arrows-fullscreen me-1"></i>Apri singola
                                    </a>
                                    <a href="{{ route('admin.boarding.show', $dep) }}" class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold">
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
            </section>
        @endforeach
    @endif

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
