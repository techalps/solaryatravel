@extends('layouts.admin')

@section('title', 'Report e statistiche')

@push('styles')
    @include('admin.reports._styles')
@endpush

@php
    $periodTitles = [
        'today' => 'Oggi',
        'week' => 'Questa settimana',
        'month' => 'Questo mese',
        'quarter' => 'Questo trimestre',
        'year' => "Quest'anno",
        'all' => 'Tutto lo storico',
    ];
    $delta = $previousRevenue > 0 ? (($revenue - $previousRevenue) / $previousRevenue) * 100 : null;
@endphp

@section('content')
<div class="rpt-shell">
    @include('admin.reports._sidebar', ['current' => 'index', 'exportType' => 'bookings'])

    <main class="rpt-main">
        <div class="rpt-header">
            <div>
                <h1>Overview report</h1>
                <p class="rpt-header-sub">
                    <i class="bi bi-calendar3"></i>
                    {{ $periodTitles[$period] ?? 'Periodo' }}
                    · {{ $startDate->format('d/m/Y') }} → {{ $endDate->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="rpt-kpis">
            <div class="rpt-kpi is-accent-success">
                <span class="rpt-kpi-label"><i class="bi bi-cash-coin"></i>Ricavi (venduto)</span>
                <span class="rpt-kpi-value">€{{ number_format($revenue, 0, ',', '.') }}</span>
                <span class="rpt-kpi-sub"><i class="bi bi-wallet2"></i>€{{ number_format($collected, 0, ',', '.') }} incassato</span>
                @if($delta !== null)
                    <span class="rpt-kpi-delta {{ $delta >= 0 ? 'is-up' : 'is-down' }}">
                        <i class="bi {{ $delta >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
                        {{ number_format(abs($delta), 1, ',', '.') }}% vs precedente
                    </span>
                @endif
            </div>
            <div class="rpt-kpi is-accent-primary">
                <span class="rpt-kpi-label"><i class="bi bi-receipt"></i>Prenotazioni</span>
                <span class="rpt-kpi-value">{{ $totalBookings }}</span>
                <span class="rpt-kpi-sub"><i class="bi bi-check2-circle"></i>{{ $confirmedBookings }} confermate</span>
            </div>
            <div class="rpt-kpi">
                <span class="rpt-kpi-label"><i class="bi bi-people-fill"></i>Passeggeri</span>
                <span class="rpt-kpi-value">{{ $totalPassengers }}</span>
                <span class="rpt-kpi-sub">posti totali venduti</span>
            </div>
            <div class="rpt-kpi">
                <span class="rpt-kpi-label"><i class="bi bi-graph-up-arrow"></i>Valore medio</span>
                <span class="rpt-kpi-value">€{{ number_format($avgBookingValue, 0, ',', '.') }}</span>
                <span class="rpt-kpi-sub">per prenotazione</span>
            </div>
        </div>

        <section class="rpt-section">
            <div class="rpt-section-head">
                <h2 class="rpt-section-title"><i class="bi bi-graph-up"></i>Andamento ricavi</h2>
                <span class="rpt-section-sub">{{ count($revenueByDay) }} giorni con vendite</span>
            </div>
            <div style="height:320px"><canvas id="revenueChart"></canvas></div>
        </section>

        <div class="rpt-grid-2">
            <section class="rpt-section">
                <div class="rpt-section-head">
                    <h2 class="rpt-section-title"><i class="bi bi-trophy"></i>Top tour</h2>
                    <span class="rpt-section-sub">per prenotazioni</span>
                </div>
                @php $maxBookings = $topTours->max('bookings_count') ?? 0; @endphp
                @forelse($topTours->take(5) as $index => $tour)
                    @php $pct = $maxBookings > 0 ? ($tour->bookings_count / $maxBookings) * 100 : 0; @endphp
                    <div class="rpt-rank-row">
                        <span class="rpt-rank-pos">{{ $index + 1 }}</span>
                        <div class="rpt-rank-body">
                            <div class="rpt-rank-line">
                                <span class="rpt-rank-name">{{ $tour->name }}</span>
                                <span class="rpt-rank-meta">{{ $tour->bookings_count }} prenot.</span>
                            </div>
                            <div class="rpt-bar"><span style="width:{{ $pct }}%"></span></div>
                        </div>
                    </div>
                @empty
                    <div class="rpt-empty">
                        <i class="bi bi-bar-chart"></i>
                        <p>Nessun dato disponibile</p>
                    </div>
                @endforelse
            </section>

            <section class="rpt-section">
                <div class="rpt-section-head">
                    <h2 class="rpt-section-title"><i class="bi bi-pie-chart"></i>Per stato</h2>
                </div>
                <div style="height:260px"><canvas id="statusChart"></canvas></div>
            </section>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Google Sans', 'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#64748b';

    const revenueData = @json($revenueByDay);
    const revenueLabels = Object.keys(revenueData).map(d => new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: 'short' }));
    const revenueValues = Object.values(revenueData);

    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const gradient = revenueCtx.createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.22)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Ricavi (€)',
                data: revenueValues,
                borderColor: '#059669',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#059669',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                    callbacks: { label: c => '€' + c.parsed.y.toLocaleString('it-IT', { minimumFractionDigits: 2 }) }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(15,23,42,0.05)' }, ticks: { callback: v => '€' + v.toLocaleString('it-IT') } },
                x: { grid: { display: false } }
            }
        }
    });

    const statusData = @json($bookingsByStatus);
    const statusLabels = { pending: 'In attesa', confirmed: 'Confermate', completed: 'Completate', cancelled: 'Cancellate', no_show: 'No show' };
    const statusColors = { pending: '#eab308', confirmed: '#10b981', completed: '#0284c7', cancelled: '#ef4444', no_show: '#94a3b8' };

    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData).map(s => statusLabels[s] || s),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: Object.keys(statusData).map(s => statusColors[s] || '#94a3b8'),
                borderWidth: 3,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', boxWidth: 8 } },
                tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10 }
            }
        }
    });
</script>
@endpush
