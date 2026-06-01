@extends('layouts.admin')

@section('title', 'Report ricavi')

@push('styles')
    @include('admin.reports._styles')
@endpush

@section('content')
<div class="rpt-shell">
    @include('admin.reports._sidebar', ['current' => 'revenue', 'exportType' => 'revenue'])

    <main class="rpt-main">
        <div class="rpt-header">
            <div>
                <h1>Report ricavi</h1>
                <p class="rpt-header-sub">
                    <i class="bi bi-calendar3"></i>
                    {{ $startDate->format('d/m/Y') }} → {{ $endDate->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="rpt-kpis">
            <div class="rpt-kpi is-accent-success">
                <span class="rpt-kpi-label"><i class="bi bi-cash-coin"></i>Totale ricavi</span>
                <span class="rpt-kpi-value">€{{ number_format($stats['total'], 0, ',', '.') }}</span>
                <span class="rpt-kpi-sub">netto del periodo</span>
            </div>
            <div class="rpt-kpi">
                <span class="rpt-kpi-label"><i class="bi bi-receipt"></i>Transazioni</span>
                <span class="rpt-kpi-value">{{ $stats['transactions'] }}</span>
                <span class="rpt-kpi-sub">pagamenti riusciti</span>
            </div>
            <div class="rpt-kpi">
                <span class="rpt-kpi-label"><i class="bi bi-graph-up-arrow"></i>Media transazione</span>
                <span class="rpt-kpi-value">€{{ number_format($stats['avg_transaction'], 0, ',', '.') }}</span>
                <span class="rpt-kpi-sub">per pagamento</span>
            </div>
            <div class="rpt-kpi is-accent-danger">
                <span class="rpt-kpi-label"><i class="bi bi-arrow-counterclockwise"></i>Rimborsi</span>
                <span class="rpt-kpi-value">€{{ number_format($stats['refunds'], 0, ',', '.') }}</span>
                <span class="rpt-kpi-sub">erogati nel periodo</span>
            </div>
        </div>

        <section class="rpt-section">
            <div class="rpt-section-head">
                <h2 class="rpt-section-title"><i class="bi bi-bar-chart"></i>Ricavi mensili {{ now()->year }}</h2>
            </div>
            <div style="height:340px"><canvas id="monthlyChart"></canvas></div>
        </section>

        <div class="rpt-grid-2">
            <section class="rpt-section">
                <div class="rpt-section-head">
                    <h2 class="rpt-section-title"><i class="bi bi-compass"></i>Ricavi per tour</h2>
                    <span class="rpt-section-sub">solo prenotazioni pagate</span>
                </div>
                @php $maxRevenue = $revenueByTour->max('total') ?? 0; @endphp
                <div class="rpt-rank">
                    @forelse($revenueByTour as $item)
                        @php $pct = $maxRevenue > 0 ? ($item->total / $maxRevenue) * 100 : 0; @endphp
                        <div class="rpt-rank-row">
                            <span class="rpt-rank-pos"><i class="bi bi-compass"></i></span>
                            <div class="rpt-rank-body">
                                <div class="rpt-rank-line">
                                    <span class="rpt-rank-name">{{ $item->tour->name ?? 'Tour sconosciuto' }}</span>
                                    <span class="rpt-rank-meta">€{{ number_format($item->total, 0, ',', '.') }}</span>
                                </div>
                                <div class="rpt-bar is-success"><span style="width:{{ $pct }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <div class="rpt-empty">
                            <i class="bi bi-inbox"></i>
                            <p>Nessun ricavo nel periodo</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rpt-section">
                <div class="rpt-section-head">
                    <h2 class="rpt-section-title"><i class="bi bi-credit-card"></i>Per gateway</h2>
                </div>
                <div class="rpt-rank">
                    @php $maxGw = $revenueByGateway->max('total') ?? 0; @endphp
                    @forelse($revenueByGateway as $g)
                        @php
                            $gw = $g->gateway ?? 'sconosciuto';
                            $pct = $maxGw > 0 ? ($g->total / $maxGw) * 100 : 0;
                            $icon = match($gw) {
                                'stripe' => 'bi-stripe',
                                'paypal' => 'bi-paypal',
                                'bank_transfer' => 'bi-bank',
                                'cash' => 'bi-cash-stack',
                                default => 'bi-wallet2',
                            };
                        @endphp
                        <div class="rpt-rank-row">
                            <span class="rpt-rank-pos"><i class="bi {{ $icon }}"></i></span>
                            <div class="rpt-rank-body">
                                <div class="rpt-rank-line">
                                    <span class="rpt-rank-name text-none">{{ str_replace('_', ' ', $gw) }}</span>
                                    <span class="rpt-rank-meta">€{{ number_format($g->total, 0, ',', '.') }} · {{ $g->count }}×</span>
                                </div>
                                <div class="rpt-bar"><span style="width:{{ $pct }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <div class="rpt-empty">
                            <i class="bi bi-credit-card"></i>
                            <p>Nessuna transazione</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rpt-section">
            <div class="rpt-section-head">
                <h2 class="rpt-section-title"><i class="bi bi-calendar3"></i>Dettaglio giornaliero</h2>
            </div>
            <div class="table-responsive">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th class="text-end">Transazioni</th>
                            <th class="text-end">Totale</th>
                            <th class="text-end">Media</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyRevenue as $day)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($day->date)->locale('it')->isoFormat('ddd D MMM YYYY') }}</td>
                                <td class="text-end">{{ $day->transactions }}</td>
                                <td class="text-end" style="font-weight:700;color:#059669">€{{ number_format($day->total, 2, ',', '.') }}</td>
                                <td class="text-end">€{{ number_format($day->total / max($day->transactions, 1), 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="rpt-empty"><i class="bi bi-inbox"></i><p>Nessun dato disponibile</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Google Sans', 'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#64748b';

    const monthlyData = @json($monthlyRevenue);
    const months = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
    const monthlyValues = months.map((_, i) => monthlyData[i + 1] || 0);

    new Chart(document.getElementById('monthlyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Ricavi (€)',
                data: monthlyValues,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                hoverBackgroundColor: '#059669',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, callbacks: { label: c => '€' + c.parsed.y.toLocaleString('it-IT', { minimumFractionDigits: 2 }) } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(15,23,42,0.05)' }, ticks: { callback: v => '€' + v.toLocaleString('it-IT') } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
