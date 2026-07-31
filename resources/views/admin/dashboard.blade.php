@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $statusMeta = [
            'pending'    => ['label' => 'In attesa',  'class' => 'text-warning'],
            'confirmed'  => ['label' => 'Confermata', 'class' => 'text-success'],
            'checked_in' => ['label' => 'Check-in',   'class' => 'text-info'],
            'completed'  => ['label' => 'Completata', 'class' => 'text-secondary'],
            'cancelled'  => ['label' => 'Annullata',  'class' => 'text-danger'],
            'no_show'    => ['label' => 'No show',    'class' => 'text-secondary'],
        ];
        $avatarColors = ['bg-primary-subtle text-primary', 'bg-success-subtle text-success', 'bg-warning-subtle text-warning', 'bg-info-subtle text-info', 'bg-danger-subtle text-danger'];
    @endphp

    {{-- Welcome banner --}}
    <div class="dash-welcome mb-4">
        <div class="position-relative" style="z-index:1">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <p class="mb-1 text-warning fw-medium small text-uppercase" style="letter-spacing:.12em">
                        {{ now()->locale('it')->isoFormat('dddd, D MMMM YYYY') }}
                    </p>
                    <h2 class="mb-2">Ciao {{ explode(' ', auth()->user()->name ?? 'Admin')[0] }}, ecco la tua giornata</h2>
                    <p class="mb-0 text-white-50">Panoramica in tempo reale di prenotazioni, ospiti e incassi.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-light rounded-pill px-3 fw-semibold">
                        <i class="bi bi-journal-check me-2"></i>Prenotazioni
                    </a>
                    <a href="{{ route('admin.boarding.index') }}" class="btn btn-warning rounded-pill px-3 fw-semibold text-navy">
                        <i class="bi bi-qr-code-scan me-2"></i>Check-in
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats grid --}}
    <div class="row g-3 mb-4">
        {{-- Le etichette dichiarano il criterio: "Partenze oggi" NON è
             "prenotazioni ricevute oggi", ed erano confondibili. --}}
        <div class="col-sm-6 col-xl-3">
            <div class="dash-stat text-info">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="dash-stat-icon bg-info-subtle text-info"><i class="bi bi-airplane-engines"></i></div>
                    <span class="dash-stat-trend bg-info-subtle text-info"><i class="bi bi-calendar3"></i>Oggi</span>
                </div>
                <div class="dash-stat-value">{{ $todayStats['departures'] }}</div>
                <div class="dash-stat-label mt-1">Partenze oggi</div>
                <div class="dash-stat-note">{{ $todayStats['guests'] }} ospiti da imbarcare</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="dash-stat text-success">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="dash-stat-icon bg-success-subtle text-success"><i class="bi bi-cart-check"></i></div>
                    <span class="dash-stat-trend bg-success-subtle text-success"><i class="bi bi-bag-plus"></i>Vendite</span>
                </div>
                <div class="dash-stat-value">{{ $todayStats['booked'] }}</div>
                <div class="dash-stat-label mt-1">Prenotazioni ricevute oggi</div>
                <div class="dash-stat-note">€{{ number_format($todayStats['booked_value'], 0, ',', '.') }} venduto</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="dash-stat text-warning">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="dash-stat-icon bg-warning-subtle text-warning"><i class="bi bi-cash-stack"></i></div>
                    <span class="dash-stat-trend bg-warning-subtle text-warning"><i class="bi bi-calendar-month"></i>Mese</span>
                </div>
                <div class="dash-stat-value">€{{ number_format($monthViews['cassa']['net'], 0, ',', '.') }}</div>
                <div class="dash-stat-label mt-1">Incassato nel mese</div>
                <div class="dash-stat-note">{{ \App\Support\ReportCriteria::LABEL_CASSA }} · {{ $monthViews['cassa']['payments'] }} rate</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="dash-stat text-primary">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="dash-stat-icon bg-primary-subtle text-primary"><i class="bi bi-graph-up-arrow"></i></div>
                    <span class="dash-stat-trend bg-primary-subtle text-primary"><i class="bi bi-calendar-month"></i>Mese</span>
                </div>
                <div class="dash-stat-value">€{{ number_format($monthViews['raccolta']['gross'], 0, ',', '.') }}</div>
                <div class="dash-stat-label mt-1">Venduto nel mese</div>
                <div class="dash-stat-note">{{ \App\Support\ReportCriteria::LABEL_RACCOLTA }} · {{ $monthViews['raccolta']['bookings'] }} prenotazioni</div>
            </div>
        </div>
    </div>

    {{-- Riga di raccordo: il valore delle partenze del mese resta visibile,
         ma dichiarato come criterio distinto invece di essere confuso con la
         cassa (era il "€ Incasso mese" che non tornava mai). --}}
    <div class="dash-basis-strip mb-4">
        <span class="dash-basis-strip-item">
            <i class="bi bi-calendar-event text-success"></i>
            Partenze di {{ now()->locale('it')->isoFormat('MMMM') }}:
            <strong>€{{ number_format($monthViews['competenza']['gross'], 0, ',', '.') }}</strong>
            <small>{{ \App\Support\ReportCriteria::LABEL_COMPETENZA }} · {{ $monthViews['competenza']['bookings'] }} escursioni</small>
        </span>
        @if($monthViews['competenza']['outstanding'] > 0)
            <span class="dash-basis-strip-item">
                <i class="bi bi-exclamation-triangle text-warning"></i>
                Da incassare su quelle partenze:
                <strong>€{{ number_format($monthViews['competenza']['outstanding'], 0, ',', '.') }}</strong>
                <a href="{{ route('admin.reports.revenue') }}">dettaglio</a>
            </span>
        @endif
    </div>

    {{-- Chart + Today bookings --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h3><i class="bi bi-bar-chart-line me-2 text-primary"></i>Incassato e venduto · ultimi 7 giorni</h3>
                    <a href="{{ route('admin.reports.revenue') }}" class="small text-primary text-decoration-none fw-medium">
                        Report completo <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="dash-card-body">
                    <div style="height:280px"><canvas id="revenueChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h3><i class="bi bi-calendar-event me-2 text-primary"></i>Prenotazioni oggi</h3>
                    <a href="{{ route('admin.bookings.index', ['date_from' => today()->format('Y-m-d'), 'date_to' => today()->format('Y-m-d')]) }}" class="small text-primary text-decoration-none fw-medium">Tutte</a>
                </div>
                <div class="dash-card-body">
                    @if($todayBookings->count() > 0)
                        @foreach($todayBookings->take(5) as $booking)
                            @php
                                $sv = $booking->status->value;
                                $meta = $statusMeta[$sv] ?? ['label' => ucfirst($sv), 'class' => 'text-secondary'];
                                $color = $avatarColors[$loop->index % count($avatarColors)];
                            @endphp
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="dash-list-item">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <span class="avatar-sm {{ $color }}">
                                        {{ strtoupper(substr($booking->customer_first_name, 0, 1) . substr($booking->customer_last_name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                                        <div class="small text-muted text-truncate">{{ $booking->tour?->name ?? '—' }} · {{ $booking->departure?->start_time ?? '' }}</div>
                                    </div>
                                </div>
                                <div class="text-end ms-2">
                                    <div class="fw-semibold small">{{ $booking->seats }} <i class="bi bi-people text-muted"></i></div>
                                    <span class="status-pill {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                </div>
                            </a>
                        @endforeach
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x display-5 opacity-50 d-block mb-2"></i>
                            <p class="mb-0 small">Nessuna prenotazione per oggi</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Pending + Activity --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h3><i class="bi bi-hourglass-split me-2 text-warning"></i>Prenotazioni in attesa</h3>
                    <span class="status-pill text-warning">{{ $pendingBookings->count() }}</span>
                </div>
                <div class="dash-card-body">
                    @if($pendingBookings->count() > 0)
                        @foreach($pendingBookings->take(5) as $booking)
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="dash-list-item">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <span class="avatar-sm bg-warning-subtle text-warning">
                                        <i class="bi bi-hourglass"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold">#{{ $booking->booking_number }}</div>
                                        <div class="small text-muted text-truncate">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                                    </div>
                                </div>
                                <div class="text-end ms-2">
                                    <div class="fw-semibold">€{{ number_format($booking->total_amount, 2, ',', '.') }}</div>
                                    <div class="small text-muted">{{ $booking->created_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @endforeach
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle display-5 opacity-50 d-block mb-2 text-success"></i>
                            <p class="mb-0 small">Tutto sotto controllo, nessuna in attesa</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h3><i class="bi bi-activity me-2 text-info"></i>Attività recente</h3>
                </div>
                <div class="dash-card-body">
                    @forelse($recentActivity as $activity)
                        @php
                            $iconMap = [
                                'calendar'    => ['icon' => 'bi-calendar-plus',  'cls'  => 'bg-info-subtle text-info'],
                                'credit-card' => ['icon' => 'bi-credit-card-2-back', 'cls'  => 'bg-success-subtle text-success'],
                            ];
                            $i = $iconMap[$activity['icon']] ?? ['icon' => 'bi-bell', 'cls' => 'bg-light text-secondary'];
                        @endphp
                        <div class="activity-item">
                            <div class="activity-icon {{ $i['cls'] }}"><i class="bi {{ $i['icon'] }}"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small text-dark">{{ $activity['message'] }}</div>
                                <div class="small text-muted text-truncate">{{ $activity['details'] }}</div>
                            </div>
                            <span class="small text-muted text-nowrap">{{ $activity['time']->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history display-5 opacity-50 d-block mb-2"></i>
                            <p class="mb-0 small">Nessuna attività recente</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Popular catamarans --}}
    <div class="dash-card mb-4">
        <div class="dash-card-header">
            <h3><i class="bi bi-trophy me-2 text-warning"></i>Catamarani più richiesti</h3>
            <span class="small text-muted">Questo mese</span>
        </div>
        <div class="dash-card-body">
            @if($popularCatamarans->count() > 0)
                <div class="row g-3">
                    @foreach($popularCatamarans as $catamaran)
                        <div class="col-6 col-md-4 col-xl">
                            <a href="{{ route('admin.catamarans.show', $catamaran) }}" class="popular-cat-card d-block text-decoration-none text-reset">
                                <div class="popular-cat-icon mx-auto"><i class="bi bi-water"></i></div>
                                <div class="fw-semibold text-dark text-truncate">{{ $catamaran->name }}</div>
                                <div class="display-6 fw-bold text-primary mb-0" style="font-size:1.75rem">{{ $catamaran->bookings_count }}</div>
                                <div class="small text-muted">prenotazioni</div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted small">Nessun dato disponibile</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(2, 132, 199, 0.35)');
        gradient.addColorStop(1, 'rgba(2, 132, 199, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        // Incassi reali, rata per rata (payments.paid_at).
                        label: 'Incassato',
                        data: {!! json_encode($chartData) !!},
                        backgroundColor: gradient,
                        borderColor: '#0284c7',
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#0284c7',
                        pointBorderWidth: 2,
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        // Venduto per data di prenotazione: curva di confronto.
                        label: 'Venduto',
                        data: {!! json_encode($chartBooked) !!},
                        borderColor: '#94a3b8',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#94a3b8',
                        pointBorderWidth: 2,
                        tension: 0.35,
                        fill: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, pointStyle: 'line', boxWidth: 24, padding: 12 }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        titleFont: { family: "'Google Sans', sans-serif", weight: '600' },
                        bodyFont: { family: "'Google Sans', sans-serif" },
                        cornerRadius: 8,
                        callbacks: { label: (c) => c.dataset.label + ': €' + c.parsed.y.toLocaleString('it-IT') }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { family: "'Google Sans', sans-serif" } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(15,23,42,.05)', drawBorder: false },
                        ticks: {
                            color: '#64748b',
                            font: { family: "'Google Sans', sans-serif" },
                            callback: (v) => '€' + v
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
