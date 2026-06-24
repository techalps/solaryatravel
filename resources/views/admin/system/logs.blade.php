@extends('layouts.admin')

@section('title', 'Log & Diagnostica')

@php
    $periodLabels = [
        'today' => 'Oggi', 'week' => '7 giorni', 'month' => '30 giorni',
        'quarter' => '90 giorni', 'all' => 'Tutto',
    ];
    $levelBadge = [
        'info' => 'bg-info-subtle text-info border-info',
        'warning' => 'bg-warning-subtle text-warning border-warning',
        'error' => 'bg-danger-subtle text-danger border-danger',
    ];
    $contextLabels = \App\Http\Controllers\Admin\LogController::CONTEXT_LABELS;
@endphp

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-activity me-2"></i>Log &amp; Diagnostica</h1>
            <div class="text-muted small">Eventi prenotazioni, pagamenti ed email · {{ $startDate->format('d/m/Y') }} → {{ $endDate->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- Filtri --}}
    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Periodo</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($periodLabels as $v => $l)
                        <option value="{{ $v }}" @selected($period === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Livello</label>
                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tutti</option>
                    <option value="info" @selected($level==='info')>Info</option>
                    <option value="warning" @selected($level==='warning')>Warning</option>
                    <option value="error" @selected($level==='error')>Errore</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Tipologia</label>
                <select name="context" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tutte</option>
                    @foreach($contexts as $val => $lbl)
                        <option value="{{ $val }}" @selected($context===$val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-9 col-md-3">
                <label class="form-label small text-muted mb-1">Prenotazione</label>
                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Es. SLY-2026-00042">
            </div>
            <div class="col-3 col-md-1 d-grid">
                <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>

    {{-- KPI --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                <div class="text-muted small">Eventi totali</div>
                <div class="h3 mb-0">{{ number_format($stats['total'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                <div class="text-muted small">Info</div>
                <div class="h3 mb-0 text-info">{{ number_format($stats['info'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                <div class="text-muted small">Warning</div>
                <div class="h3 mb-0 text-warning">{{ number_format($stats['warning'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 {{ $stats['error'] > 0 ? 'border-start border-danger border-3' : '' }}"><div class="card-body py-3">
                <div class="text-muted small">Errori</div>
                <div class="h3 mb-0 text-danger">{{ number_format($stats['error'], 0, ',', '.') }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h2 class="h6 text-muted mb-3">Andamento per livello</h2>
                <div style="height:280px"><canvas id="trendChart"></canvas></div>
            </div></div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h2 class="h6 text-muted mb-3">Per tipologia</h2>
                @forelse($byContext as $row)
                    @php $pct = $stats['total'] > 0 ? round($row['count'] / $stats['total'] * 100) : 0; @endphp
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $row['label'] }}</span>
                            <span class="text-muted">{{ $row['count'] }}</span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">Nessun evento nel periodo.</div>
                @endforelse
            </div></div>
        </div>
    </div>

    {{-- Tabella eventi --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 text-muted mb-3">Eventi ({{ $events->total() }})</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th>Data/ora</th>
                            <th>Livello</th>
                            <th>Tipologia</th>
                            <th>Prenotazione</th>
                            <th>Messaggio</th>
                            <th>Dettagli</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $e)
                            <tr>
                                <td class="small text-nowrap">{{ $e->occurred_at->format('d/m H:i:s') }}</td>
                                <td><span class="badge border {{ $levelBadge[$e->level] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($e->level) }}</span></td>
                                <td class="small">{{ $contextLabels[$e->context] ?? $e->context }}</td>
                                <td class="small text-nowrap">
                                    @if($e->booking_id)
                                        <a href="{{ route('admin.bookings.show', $e->booking_id) }}">{{ $e->booking_number }}</a>
                                    @else
                                        <span class="text-muted">{{ $e->booking_number ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $e->message }}</td>
                                <td class="small">
                                    @if(!empty($e->meta))
                                        <code class="small text-muted">{{ \Illuminate\Support\Str::limit(json_encode($e->meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 80) }}</code>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nessun evento corrisponde ai filtri.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $events->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = @json($days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m')));
    new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Info', data: @json($series['info']), borderColor: '#0dcaf0', backgroundColor: 'rgba(13,202,240,.1)', tension: .3, fill: true },
                { label: 'Warning', data: @json($series['warning']), borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.1)', tension: .3, fill: true },
                { label: 'Errore', data: @json($series['error']), borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.12)', tension: .3, fill: true },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endpush
