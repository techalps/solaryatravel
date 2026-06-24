@php
    $periodLabels = [
        'today' => 'Oggi',
        'week' => 'Settimana',
        'month' => 'Mese',
        'quarter' => 'Trimestre',
        'year' => 'Anno',
        'all' => 'Tutto',
    ];
    $current = $current ?? 'index';
    $exportType = $exportType ?? null;
@endphp
<aside class="rpt-aside">
    <div class="rpt-aside-section">
        <div class="rpt-aside-label">Periodo</div>
        <div class="rpt-chips">
            @foreach($periodLabels as $value => $label)
                <a href="{{ url()->current() . '?period=' . $value }}"
                   class="rpt-chip {{ $period === $value ? 'is-active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="rpt-aside-section">
        <div class="rpt-aside-label">Report</div>
        <nav class="rpt-nav">
            <a href="{{ route('admin.reports.index', ['period' => $period]) }}"
               class="rpt-nav-link {{ $current === 'index' ? 'is-active' : '' }}">
                <i class="bi bi-grid-1x2"></i>Overview
            </a>
            <a href="{{ route('admin.reports.revenue', ['period' => $period]) }}"
               class="rpt-nav-link {{ $current === 'revenue' ? 'is-active' : '' }}">
                <i class="bi bi-cash-coin"></i>Ricavi
            </a>
            <a href="{{ route('admin.reports.bookings', ['period' => $period]) }}"
               class="rpt-nav-link {{ $current === 'bookings' ? 'is-active' : '' }}">
                <i class="bi bi-receipt"></i>Prenotazioni
            </a>
            <a href="{{ route('admin.reports.occupancy', ['period' => $period]) }}"
               class="rpt-nav-link {{ $current === 'occupancy' ? 'is-active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i>Occupazione
            </a>
        </nav>
    </div>

    <div class="rpt-aside-section">
        <a href="{{ route('admin.reports.export-excel', ['period' => $period]) }}"
           class="rpt-aside-btn">
            <i class="bi bi-file-earmark-excel"></i>Esporta tutto (Excel)
        </a>
        @if($exportType)
            <a href="{{ route('admin.reports.export', ['type' => $exportType, 'period' => $period]) }}"
               class="rpt-aside-btn rpt-aside-btn-ghost">
                <i class="bi bi-download"></i>Esporta CSV (questa scheda)
            </a>
        @endif
    </div>
</aside>
