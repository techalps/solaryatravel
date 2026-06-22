@extends('layouts.admin')

@section('title', 'Imbarco passeggeri')

@section('content')
    @php
        $prev = $date->copy()->subDay()->toDateString();
        $next = $date->copy()->addDay()->toDateString();
        $isToday = $date->isToday();
    @endphp

    <div class="dash-page-header">
        <div>
            <h1 class="mb-1">Imbarco passeggeri</h1>
            <p class="mb-0 text-muted">
                {{ ucfirst($date->translatedFormat('l d F Y')) }}
                @if($isToday) <span class="badge bg-primary-subtle text-primary ms-1">Oggi</span> @endif
                · {{ $items->count() }} {{ \Illuminate\Support\Str::plural('partenza', $items->count()) }}
            </p>
        </div>
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="tour" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:170px">
                <option value="">Tutti i tour</option>
                @foreach($tours as $t)
                    <option value="{{ $t->id }}" @selected($selectedTour === $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            <div class="btn-group">
                <a href="{{ route('admin.boarding.index', ['date' => $prev, 'tour' => $selectedTour]) }}" class="btn btn-sm btn-light border" title="Giorno precedente"><i class="bi bi-chevron-left"></i></a>
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="form-control form-control-sm border" style="max-width:150px" onchange="this.form.submit()">
                <a href="{{ route('admin.boarding.index', ['date' => $next, 'tour' => $selectedTour]) }}" class="btn btn-sm btn-light border" title="Giorno successivo"><i class="bi bi-chevron-right"></i></a>
            </div>
            @unless($isToday)
                <a href="{{ route('admin.boarding.index') }}" class="btn btn-sm btn-outline-secondary">Oggi</a>
            @endunless
        </form>
    </div>

    @if($items->isEmpty())
        <div class="dash-card">
            <div class="dash-card-body text-center py-5">
                <i class="bi bi-calendar-x display-4 text-muted opacity-50 d-block mb-3"></i>
                <h4 class="fw-bold mb-1">Nessuna partenza</h4>
                <p class="text-muted mb-0">Il {{ $date->format('d/m/Y') }} non ci sono tour in programma{{ $selectedTour ? ' per il tour selezionato' : '' }}. Usa le frecce per cambiare giorno.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($items as $it)
                @php $hasScanner = (bool) $it['departure']; @endphp
                <div class="col-md-6 col-xl-4">
                    @if($hasScanner)
                        <a href="{{ route('admin.boarding.show', $it['departure']) }}" class="text-decoration-none">
                    @else
                        <div class="text-decoration-none" style="cursor:default">
                    @endif
                        <div class="dash-card h-100 brd-card">
                            <div class="dash-card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <h3 class="h6 mb-0 fw-bold text-dark">{{ $it['tour']?->name ?? 'Tour' }}</h3>
                                    {{-- Badge prenotazioni in verde --}}
                                    <span class="badge bg-success-subtle text-success flex-shrink-0">{{ $it['count'] }} prenot.</span>
                                </div>
                                <div class="text-muted small mb-3">
                                    <i class="bi bi-clock me-1"></i>{{ $it['time'] }}@if($it['end_time']) – {{ $it['end_time'] }}@endif
                                    @if($it['spanning'])
                                        <span class="badge bg-info-subtle text-info ms-1"><i class="bi bi-water me-1"></i>uso esclusivo</span>
                                    @endif
                                </div>
                                @if($hasScanner)
                                    <span class="text-primary fw-semibold small">Avvia scanner <i class="bi bi-arrow-right"></i></span>
                                @else
                                    <span class="text-muted small">Nessuna prenotazione</span>
                                @endif
                            </div>
                        </div>
                    @if($hasScanner)</a>@else</div>@endif
                </div>
            @endforeach
        </div>
    @endif

@push('styles')
<style>
    .brd-card { transition: transform .12s, box-shadow .12s; }
    .brd-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,23,42,.1) !important; }
</style>
@endpush
@endsection
