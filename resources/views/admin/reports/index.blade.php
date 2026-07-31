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

    $comp = $views['competenza'];
    $racc = $views['raccolta'];
    $cassa = $views['cassa'];

    // Delta calcolati sul periodo precedente di PARI durata (mese su mese).
    $deltaOf = function (float $now, float $prev): ?float {
        return $prev > 0 ? (($now - $prev) / $prev) * 100 : null;
    };
    $deltaComp = $deltaOf($comp['gross'], $previousViews['competenza']['gross']);
    $deltaRacc = $deltaOf($racc['gross'], $previousViews['raccolta']['gross']);
    $deltaCassa = $deltaOf($cassa['net'], $previousViews['cassa']['net']);
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

        {{-- I TRE CRITERI, in colonne separate e mai sommate fra loro.
             Mescolarli è esattamente ciò che rendeva i report illeggibili:
             ogni cifra dichiara la propria base di calcolo. --}}
        <div class="rpt-basis-grid">
            {{-- 1. RACCOLTA --}}
            <section class="rpt-basis is-raccolta">
                <header class="rpt-basis-head">
                    <span class="rpt-basis-tag"><i class="bi bi-cart-check"></i>Venduto</span>
                    <h2 class="rpt-basis-title">{{ \App\Support\ReportCriteria::LABEL_RACCOLTA }}</h2>
                    <p class="rpt-basis-help">{{ \App\Support\ReportCriteria::HELP_RACCOLTA }}</p>
                </header>
                <div class="rpt-basis-value">€{{ number_format($racc['gross'], 0, ',', '.') }}</div>
                @if($deltaRacc !== null)
                    <span class="rpt-kpi-delta {{ $deltaRacc >= 0 ? 'is-up' : 'is-down' }}">
                        <i class="bi {{ $deltaRacc >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
                        {{ number_format(abs($deltaRacc), 1, ',', '.') }}% vs periodo precedente
                    </span>
                @endif
                <dl class="rpt-basis-rows">
                    <div><dt>Prenotazioni</dt><dd>{{ $racc['bookings'] }}</dd></div>
                    <div><dt>Passeggeri</dt><dd>{{ $racc['seats'] }}</dd></div>
                    <div><dt>Valore medio</dt><dd>€{{ number_format($racc['avg'], 0, ',', '.') }}</dd></div>
                    <div><dt>Annullate</dt><dd>{{ $racc['cancelled'] }}</dd></div>
                </dl>
            </section>

            {{-- 2. COMPETENZA --}}
            <section class="rpt-basis is-competenza">
                <header class="rpt-basis-head">
                    <span class="rpt-basis-tag"><i class="bi bi-calendar-event"></i>Partenze</span>
                    <h2 class="rpt-basis-title">{{ \App\Support\ReportCriteria::LABEL_COMPETENZA }}</h2>
                    <p class="rpt-basis-help">{{ \App\Support\ReportCriteria::HELP_COMPETENZA }}</p>
                </header>
                <div class="rpt-basis-value">€{{ number_format($comp['gross'], 0, ',', '.') }}</div>
                @if($deltaComp !== null)
                    <span class="rpt-kpi-delta {{ $deltaComp >= 0 ? 'is-up' : 'is-down' }}">
                        <i class="bi {{ $deltaComp >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
                        {{ number_format(abs($deltaComp), 1, ',', '.') }}% vs periodo precedente
                    </span>
                @endif
                <dl class="rpt-basis-rows">
                    <div><dt>Escursioni</dt><dd>{{ $comp['bookings'] }}</dd></div>
                    <div><dt>Passeggeri</dt><dd>{{ $comp['seats'] }}</dd></div>
                    <div><dt>Valore medio</dt><dd>€{{ number_format($comp['avg'], 0, ',', '.') }}</dd></div>
                    <div><dt>Netto (post provvigioni)</dt><dd>€{{ number_format($comp['net'], 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            {{-- 3. CASSA: l'unica che gestisce le rate nel mese giusto. --}}
            <section class="rpt-basis is-cassa">
                <header class="rpt-basis-head">
                    <span class="rpt-basis-tag"><i class="bi bi-cash-stack"></i>Incassato</span>
                    <h2 class="rpt-basis-title">{{ \App\Support\ReportCriteria::LABEL_CASSA }}</h2>
                    <p class="rpt-basis-help">{{ \App\Support\ReportCriteria::HELP_CASSA }}</p>
                </header>
                <div class="rpt-basis-value">€{{ number_format($cassa['net'], 0, ',', '.') }}</div>
                @if($deltaCassa !== null)
                    <span class="rpt-kpi-delta {{ $deltaCassa >= 0 ? 'is-up' : 'is-down' }}">
                        <i class="bi {{ $deltaCassa >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
                        {{ number_format(abs($deltaCassa), 1, ',', '.') }}% vs periodo precedente
                    </span>
                @endif
                <dl class="rpt-basis-rows">
                    <div><dt>Entrate lorde</dt><dd>€{{ number_format($cassa['gross_in'], 0, ',', '.') }}</dd></div>
                    <div><dt>Rimborsi erogati</dt><dd class="text-danger">−€{{ number_format($cassa['refunds'], 0, ',', '.') }}</dd></div>
                    <div><dt>Rate incassate</dt><dd>{{ $cassa['payments'] }}</dd></div>
                </dl>
            </section>
        </div>

        {{-- Il "da incassare" scomposto: due situazioni molto diverse che
             sommate in un'unica voce si confondevano fra loro. --}}
        @if($outstanding['total']['amount'] > 0)
            <section class="rpt-section rpt-outstanding">
                <div class="rpt-section-head">
                    <h2 class="rpt-section-title"><i class="bi bi-exclamation-triangle text-warning"></i>Da incassare sulle partenze del periodo</h2>
                    <span class="rpt-section-sub">{{ \App\Support\ReportCriteria::LABEL_COMPETENZA }}</span>
                </div>
                <div class="rpt-out-grid">
                    <div class="rpt-out-cell">
                        <span class="rpt-out-label">Saldi aperti</span>
                        <span class="rpt-out-value">€{{ number_format($outstanding['partial']['amount'], 0, ',', '.') }}</span>
                        <span class="rpt-out-sub">{{ $outstanding['partial']['count'] }} prenotazioni con acconto versato</span>
                    </div>
                    <div class="rpt-out-cell is-alert">
                        <span class="rpt-out-label">Nessun pagamento registrato</span>
                        <span class="rpt-out-value">€{{ number_format($outstanding['unpaid']['amount'], 0, ',', '.') }}</span>
                        <span class="rpt-out-sub">{{ $outstanding['unpaid']['count'] }} prenotazioni senza alcun incasso a sistema</span>
                    </div>
                    <div class="rpt-out-cell is-total">
                        <span class="rpt-out-label">Totale</span>
                        <span class="rpt-out-value">€{{ number_format($outstanding['total']['amount'], 0, ',', '.') }}</span>
                        <span class="rpt-out-sub">{{ $outstanding['total']['count'] }} prenotazioni</span>
                    </div>
                </div>

                @if($pastDueBookings->isNotEmpty())
                    <div class="rpt-pastdue">
                        <p class="rpt-pastdue-intro">
                            <i class="bi bi-airplane-engines"></i>
                            <strong>{{ $pastDueBookings->count() }}</strong> con partenza <strong>già effettuata</strong> e residuo aperto:
                            o il saldo non è mai stato incassato, o è stato incassato senza registrarlo.
                        </p>
                        <div class="table-responsive">
                            <table class="rpt-table">
                                <thead>
                                    <tr>
                                        <th>Prenotazione</th>
                                        <th>Partenza</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Totale</th>
                                        <th class="text-end">Incassato</th>
                                        <th class="text-end">Residuo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pastDueBookings as $b)
                                        @php $res = (float) $b->total_amount - (float) $b->amount_paid; @endphp
                                        <tr>
                                            <td><a href="{{ route('admin.bookings.show', $b) }}">{{ $b->booking_number }}</a></td>
                                            <td>{{ $b->booking_date?->format('d/m/Y') ?? '-' }}</td>
                                            <td>{{ trim($b->customer_first_name . ' ' . $b->customer_last_name) }}</td>
                                            <td class="text-end">€{{ number_format($b->total_amount, 2, ',', '.') }}</td>
                                            <td class="text-end">€{{ number_format($b->amount_paid, 2, ',', '.') }}</td>
                                            <td class="text-end fw-bold {{ (float) $b->amount_paid == 0.0 ? 'text-danger' : 'text-warning' }}">
                                                €{{ number_format($res, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($unregistered['count'] > 0)
                    <p class="rpt-note-warn">
                        <i class="bi bi-info-circle"></i>
                        {{ $unregistered['count'] }} prenotazioni risultano incassate per
                        €{{ number_format($unregistered['amount'], 0, ',', '.') }} senza un pagamento registrato:
                        quell'importo <strong>non compare nella vista per data di incasso</strong>, perché privo di data.
                    </p>
                @endif
            </section>
        @endif

        <section class="rpt-section">
            <div class="rpt-section-head">
                <h2 class="rpt-section-title"><i class="bi bi-graph-up"></i>Andamento giornaliero</h2>
                <span class="rpt-section-sub">le tre curve a confronto · non vanno sommate</span>
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

    // Tre curve distinte: stesso asse dei giorni, criteri diversi. Sono
    // volutamente separate — sommarle non avrebbe alcun significato.
    const chartDays = @json($chartDays);
    const chartLabels = chartDays.map(d => new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: 'short' }));

    const revenueCtx = document.getElementById('revenueChart').getContext('2d');

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Venduto · per data prenotazione',
                    data: @json($chartRaccolta),
                    borderColor: '#1d4ed8',
                    backgroundColor: 'rgba(29, 78, 216, 0.08)',
                    borderWidth: 2.5, fill: false, tension: 0.35,
                    pointRadius: 2.5, pointBackgroundColor: '#1d4ed8',
                    pointBorderColor: '#fff', pointBorderWidth: 1.5,
                },
                {
                    label: 'Venduto · per data partenza',
                    data: @json($chartCompetenza),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    borderWidth: 2.5, fill: false, tension: 0.35,
                    pointRadius: 2.5, pointBackgroundColor: '#059669',
                    pointBorderColor: '#fff', pointBorderWidth: 1.5,
                },
                {
                    label: 'Incassato · per data incasso',
                    data: @json($chartCassa),
                    borderColor: '#b45309',
                    backgroundColor: 'rgba(180, 83, 9, 0.10)',
                    borderWidth: 2.5, fill: true, tension: 0.35,
                    pointRadius: 2.5, pointBackgroundColor: '#b45309',
                    pointBorderColor: '#fff', pointBorderWidth: 1.5,
                    borderDash: [5, 3],
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { padding: 14, usePointStyle: true, pointStyle: 'line', boxWidth: 26 }
                },
                tooltip: {
                    backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                    callbacks: {
                        label: c => c.dataset.label + ': €' + c.parsed.y.toLocaleString('it-IT', { minimumFractionDigits: 2 })
                    }
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
