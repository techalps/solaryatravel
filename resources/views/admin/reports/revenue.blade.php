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

        {{-- I tre criteri separati. Prima qui c'era un unico riquadro "Venduto"
             con sotto "X incassato · Y da incassare": quel Y sommava saldi
             realmente aperti e incassi mai registrati, e non essendo legato al
             mese corrente sembrava non scendere mai. --}}
        <div class="rpt-basis-grid">
            <section class="rpt-basis is-raccolta">
                <header class="rpt-basis-head">
                    <span class="rpt-basis-tag"><i class="bi bi-cart-check"></i>Venduto</span>
                    <h2 class="rpt-basis-title">{{ \App\Support\ReportCriteria::LABEL_RACCOLTA }}</h2>
                    <p class="rpt-basis-help">{{ \App\Support\ReportCriteria::HELP_RACCOLTA }}</p>
                </header>
                <div class="rpt-basis-value">€{{ number_format($views['raccolta']['gross'], 0, ',', '.') }}</div>
                <dl class="rpt-basis-rows">
                    <div><dt>Prenotazioni</dt><dd>{{ $views['raccolta']['bookings'] }}</dd></div>
                    <div><dt>Valore medio</dt><dd>€{{ number_format($views['raccolta']['avg'], 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            <section class="rpt-basis is-competenza">
                <header class="rpt-basis-head">
                    <span class="rpt-basis-tag"><i class="bi bi-calendar-event"></i>Partenze</span>
                    <h2 class="rpt-basis-title">{{ \App\Support\ReportCriteria::LABEL_COMPETENZA }}</h2>
                    <p class="rpt-basis-help">{{ \App\Support\ReportCriteria::HELP_COMPETENZA }}</p>
                </header>
                <div class="rpt-basis-value">€{{ number_format($views['competenza']['gross'], 0, ',', '.') }}</div>
                <dl class="rpt-basis-rows">
                    <div><dt>Escursioni</dt><dd>{{ $views['competenza']['bookings'] }}</dd></div>
                    <div><dt>Netto (post provvigioni)</dt><dd>€{{ number_format($views['competenza']['net'], 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            <section class="rpt-basis is-cassa">
                <header class="rpt-basis-head">
                    <span class="rpt-basis-tag"><i class="bi bi-cash-stack"></i>Incassato</span>
                    <h2 class="rpt-basis-title">{{ \App\Support\ReportCriteria::LABEL_CASSA }}</h2>
                    <p class="rpt-basis-help">{{ \App\Support\ReportCriteria::HELP_CASSA }}</p>
                </header>
                <div class="rpt-basis-value">€{{ number_format($views['cassa']['net'], 0, ',', '.') }}</div>
                <dl class="rpt-basis-rows">
                    <div><dt>Entrate lorde</dt><dd>€{{ number_format($views['cassa']['gross_in'], 0, ',', '.') }}</dd></div>
                    <div><dt>Rimborsi erogati</dt><dd class="text-danger">−€{{ number_format($views['cassa']['refunds'], 0, ',', '.') }}</dd></div>
                    <div><dt>Rate incassate</dt><dd>{{ $views['cassa']['payments'] }}</dd></div>
                </dl>
            </section>
        </div>

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

        {{-- Diretto vs agenzie: le provvigioni B2B non comparivano in nessun
             report, quindi il "venduto" sovrastimava quanto resta a Solarya. --}}
        <section class="rpt-section">
            <div class="rpt-section-head">
                <h2 class="rpt-section-title"><i class="bi bi-diagram-3"></i>Canale di vendita</h2>
                <span class="rpt-section-sub">{{ \App\Support\ReportCriteria::LABEL_COMPETENZA }} · provvigioni incluse</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th>Canale</th>
                            <th class="text-end">Prenotazioni</th>
                            <th class="text-end">Passeggeri</th>
                            <th class="text-end">Venduto</th>
                            <th class="text-end">Incassato</th>
                            <th class="text-end">Provvigioni</th>
                            <th class="text-end">Netto Solarya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="bi bi-globe me-1 text-primary"></i>Diretto (sito)</td>
                            <td class="text-end">{{ $channels['direct']['bookings'] }}</td>
                            <td class="text-end">{{ $channels['direct']['seats'] }}</td>
                            <td class="text-end">€{{ number_format($channels['direct']['gross'], 2, ',', '.') }}</td>
                            <td class="text-end">€{{ number_format($channels['direct']['collected'], 2, ',', '.') }}</td>
                            <td class="text-end text-muted">—</td>
                            <td class="text-end">€{{ number_format($channels['direct']['gross'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-shop me-1 text-warning"></i>Agenzie (B2B)</td>
                            <td class="text-end">{{ $channels['agency']['bookings'] }}</td>
                            <td class="text-end">{{ $channels['agency']['seats'] }}</td>
                            <td class="text-end">€{{ number_format($channels['agency']['gross'], 2, ',', '.') }}</td>
                            <td class="text-end">€{{ number_format($channels['agency']['collected'], 2, ',', '.') }}</td>
                            <td class="text-end text-danger">−€{{ number_format($channels['agency']['commission'], 2, ',', '.') }}</td>
                            <td class="text-end">€{{ number_format($channels['agency']['gross'] - $channels['agency']['commission'], 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold border-top">
                            <td>Totale</td>
                            <td class="text-end">{{ $channels['total']['bookings'] }}</td>
                            <td class="text-end">{{ $channels['total']['seats'] }}</td>
                            <td class="text-end">€{{ number_format($channels['total']['gross'], 2, ',', '.') }}</td>
                            <td class="text-end">€{{ number_format($channels['total']['collected'], 2, ',', '.') }}</td>
                            <td class="text-end text-danger">−€{{ number_format($channels['total']['commission'], 2, ',', '.') }}</td>
                            <td class="text-end">€{{ number_format($channels['total']['net'], 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

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
                    <span class="rpt-section-sub">prenotazioni confermate/incassate</span>
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
                            <th class="text-end">Prenotazioni</th>
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
