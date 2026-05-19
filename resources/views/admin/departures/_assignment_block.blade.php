{{--
    Blocco "assegnazione catamarani" per una singola partenza.
    Variabili attese:
      - $departure        TourDeparture
      - $catamarans       Collection<Catamaran>     (operativi nella data)
      - $byCatamaran      Collection (seats raggruppati per catamaran_id, 0 = non assegnato)
      - $stats            array [catamaran_id => ['count','capacity','free']]
      - $unassignedCount  int
--}}
@if ($catamarans->isEmpty())
    <div class="alert alert-warning rounded-3 mb-3">
        Nessun catamarano operativo per questa partenza.
    </div>
@endif

<div class="row g-3">
    @foreach ($catamarans as $catamaran)
        @php
            $st = $stats[$catamaran->id] ?? ['count' => 0, 'capacity' => (int) $catamaran->capacity, 'free' => (int) $catamaran->capacity];
            $seats = $byCatamaran->get($catamaran->id) ?? collect();
            $pct = $st['capacity'] > 0 ? min(100, round($st['count'] * 100 / $st['capacity'])) : 0;
            $barColor = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
            $isFull = $st['free'] === 0;
        @endphp
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <i class="bi bi-water me-2 text-primary"></i>{{ $catamaran->name }}
                            </h5>
                            <div class="text-muted small">Capienza {{ $st['capacity'] }} posti</div>
                        </div>
                        <span class="badge bg-{{ $barColor }}-subtle text-{{ $barColor }} fw-semibold">
                            {{ $st['count'] }}/{{ $st['capacity'] }}
                            @if ($isFull) · pieno @endif
                        </span>
                    </div>

                    <div class="progress mb-3" role="progressbar" style="height:6px">
                        <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $pct }}%"></div>
                    </div>

                    @if ($seats->isEmpty())
                        <div class="text-muted small fst-italic">Nessun passeggero assegnato.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($seats as $seat)
                                @include('admin.departures._seat_row', [
                                    'seat' => $seat,
                                    'catamarans' => $catamarans,
                                    'stats' => $stats,
                                ])
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    @if ($unassignedCount > 0)
        @php $seats = $byCatamaran->get(0) ?? collect(); @endphp
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 border-start border-warning border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Senza catamarano
                            </h5>
                            <div class="text-muted small">Passeggeri da assegnare</div>
                        </div>
                        <span class="badge bg-warning-subtle text-warning fw-semibold">{{ $unassignedCount }}</span>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($seats as $seat)
                            @include('admin.departures._seat_row', [
                                'seat' => $seat,
                                'catamarans' => $catamarans,
                                'stats' => $stats,
                            ])
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
