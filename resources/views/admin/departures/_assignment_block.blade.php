{{--
    Blocco "assegnazione catamarani" per una singola partenza.
    Variabili attese:
      - $departure        TourDeparture
      - $catamarans       Collection<Catamaran>     (operativi nella data)
      - $byCatamaran      Collection (seats raggruppati per catamaran_id, 0 = non assegnato)
      - $stats            array [catamaran_id => ['count','capacity','free','exclusive']]
      - $unassignedCount  int
      - $exclusiveByCatamaran array [catamaran_id => ['booking_number','holder','booking']]
--}}
@php $exclusiveByCatamaran = $exclusiveByCatamaran ?? []; @endphp
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
            $exclusive = $exclusiveByCatamaran[$catamaran->id] ?? null;
            $pct = $st['capacity'] > 0 ? min(100, round($st['count'] * 100 / $st['capacity'])) : 0;
            $barColor = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
            $isFull = ($st['free'] ?? 0) === 0;
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
                        @if ($exclusive)
                            {{-- Uso esclusivo: il catamarano è interamente riservato alla prenotazione. --}}
                            <span class="badge bg-info-subtle text-info fw-semibold"><i class="bi bi-water me-1"></i>Uso esclusivo</span>
                        @else
                            <span class="badge bg-{{ $barColor }}-subtle text-{{ $barColor }} fw-semibold">
                                {{ $st['count'] }}/{{ $st['capacity'] }}
                                @if ($isFull) · pieno @endif
                            </span>
                        @endif
                    </div>

                    @unless ($exclusive)
                        <div class="progress mb-3" role="progressbar" style="height:6px">
                            <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $pct }}%"></div>
                        </div>
                    @endunless

                    @if ($exclusive)
                        {{-- Riservato in esclusiva. Se la riserva è di QUESTA partenza
                             (non "spanning" da un altro giorno), si può spostare su un
                             catamarano libero: sposta posti + riserva insieme. --}}
                        @php
                            $exBooking = $exclusive['booking'];
                            $isOwnDeparture = $exBooking && (int) $exBooking->tour_departure_id === (int) $departure->id;
                            // Catamarani destinazione validi: liberi (non esclusivi, non pieni) e diversi dall'attuale.
                            $freeTargets = $catamarans->filter(function ($c) use ($catamaran, $stats, $exclusiveByCatamaran) {
                                if ($c->id === $catamaran->id) return false;
                                if (isset($exclusiveByCatamaran[$c->id])) return false;
                                return (($stats[$c->id]['count'] ?? 0) === 0); // completamente libero
                            });
                        @endphp
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 px-0">
                                <div class="me-2">
                                    <div class="fw-semibold">{{ $exclusive['holder'] ?: '—' }}</div>
                                    <div class="small text-muted">
                                        <a href="{{ route('admin.bookings.show', $exBooking) }}" class="text-decoration-none">#{{ $exclusive['booking_number'] }}</a>
                                    </div>
                                </div>
                                @if ($isOwnDeparture && $freeTargets->isNotEmpty())
                                    <form method="POST" action="{{ route('admin.bookings.move-reservation', $exBooking) }}" class="d-flex align-items-center gap-1">
                                        @csrf
                                        <select name="catamaran_id" class="form-select form-select-sm" style="width:auto"
                                                onchange="if(this.value) this.form.submit()">
                                            <option value="">Sposta su…</option>
                                            @foreach ($freeTargets as $t)
                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="badge bg-light text-muted border">Riservato (uso esclusivo)</span>
                                @endif
                            </li>
                        </ul>
                    @elseif ($seats->isEmpty())
                        <div class="text-muted small fst-italic">Nessun passeggero assegnato.</div>
                    @else
                        {{-- Spostamento in blocco: seleziona i passeggeri e scegli la
                             barca di destinazione. La capienza si vede PRIMA di spostare. --}}
                        @php
                            // Destinazioni possibili: gli altri catamarani operativi,
                             // esclusi quelli in uso esclusivo (non assegnabili).
                            $targets = $catamarans
                                ->reject(fn ($c) => (int) $c->id === (int) $catamaran->id)
                                ->reject(fn ($c) => isset($exclusiveByCatamaran[$c->id]))
                                ->values();
                        @endphp
                        @if ($targets->isNotEmpty())
                            <form method="POST"
                                  action="{{ route('admin.assignments.move-bulk', $departure) }}"
                                  data-bulk-move
                                  class="border rounded-3 p-2 mb-2"
                                  style="background:#f8f9fa">
                                @csrf
                                {{-- Riga 1: selezione. Riga 2: destinazione + azione.
                                     Due righe distinte invece di un flex che va a capo
                                     da solo: la colonna è stretta (col-xl-6) e il
                                     risultato era imprevedibile. --}}
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" data-bulk-all
                                               id="all-{{ $departure->id }}-{{ $catamaran->id }}">
                                        <label class="form-check-label small fw-semibold" for="all-{{ $departure->id }}-{{ $catamaran->id }}">
                                            Seleziona tutti
                                        </label>
                                    </div>
                                    <span class="small text-muted" data-bulk-count>0 selezionati</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <select name="target_catamaran_id" class="form-select form-select-sm"
                                            data-bulk-target>
                                        <option value="">Sposta su…</option>
                                        @foreach ($targets as $t)
                                            @php $ts = $stats[$t->id] ?? ['free' => (int) $t->capacity, 'count' => 0, 'capacity' => (int) $t->capacity]; @endphp
                                            <option value="{{ $t->id }}" data-free="{{ $ts['free'] }}">
                                                {{ $t->name }} — {{ $ts['free'] }} {{ $ts['free'] === 1 ? 'posto libero' : 'posti liberi' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold text-nowrap"
                                            data-bulk-submit disabled>
                                        <i class="bi bi-arrow-left-right me-1"></i>Sposta
                                    </button>
                                </div>
                                {{-- Esito della verifica capienza, prima di spostare. --}}
                                <div class="small mt-2 d-none" data-bulk-feedback></div>
                        @endif
                        <ul class="list-group list-group-flush" data-seat-list>
                            @foreach ($seats as $seat)
                                @include('admin.departures._seat_row', [
                                    'seat' => $seat,
                                    'catamarans' => $catamarans,
                                    'stats' => $stats,
                                    'exclusiveByCatamaran' => $exclusiveByCatamaran,
                                ])
                            @endforeach
                        </ul>
                        @if ($targets->isNotEmpty())
                            </form>
                        @endif
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
                                'exclusiveByCatamaran' => $exclusiveByCatamaran,
                            ])
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
/**
 * Spostamento in blocco dei passeggeri fra catamarani.
 *
 * Il punto chiave è che l'operatore deve sapere PRIMA di spostare se il gruppo
 * selezionato entra nella barca di destinazione: il pulsante resta disabilitato
 * e il messaggio spiega perché, invece di far scoprire il problema dopo il
 * submit. Il controllo è comunque ripetuto lato server (fonte di verità).
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-bulk-move]').forEach(function (form) {
        var checks   = form.querySelectorAll('[data-seat-check]');
        var allBox   = form.querySelector('[data-bulk-all]');
        var target   = form.querySelector('[data-bulk-target]');
        var submit   = form.querySelector('[data-bulk-submit]');
        var counter  = form.querySelector('[data-bulk-count]');
        var feedback = form.querySelector('[data-bulk-feedback]');
        if (!checks.length || !target || !submit) return;

        function selected() {
            return Array.prototype.filter.call(checks, function (c) { return c.checked; });
        }

        function refresh() {
            var n = selected().length;
            counter.textContent = n + (n === 1 ? ' selezionato' : ' selezionati');

            // Stato della casella "seleziona tutti": piena, vuota o parziale.
            if (allBox) {
                allBox.checked = n === checks.length && n > 0;
                allBox.indeterminate = n > 0 && n < checks.length;
            }

            var opt  = target.options[target.selectedIndex];
            var free = opt ? parseInt(opt.dataset.free || '0', 10) : null;

            feedback.classList.add('d-none');
            feedback.className = 'small mt-2 d-none';

            if (n === 0) {
                submit.disabled = true;
                return;
            }
            if (!target.value) {
                submit.disabled = true;
                feedback.textContent = 'Scegli il catamarano di destinazione.';
                feedback.className = 'small mt-2 text-muted';
                return;
            }

            if (n > free) {
                // Non ci stanno: blocca e dì esattamente quanti mancano.
                submit.disabled = true;
                feedback.textContent = 'Non ci stanno tutti: ' + n +
                    (n === 1 ? ' passeggero selezionato' : ' passeggeri selezionati') +
                    ' ma su ' + opt.text.split(' — ')[0] + ' ci sono ' + free +
                    (free === 1 ? ' posto libero' : ' posti liberi') +
                    ((n - free) === 1 ? ' (ne manca 1).' : ' (ne mancano ' + (n - free) + ').');
                feedback.className = 'small mt-2 text-danger fw-semibold';
                return;
            }

            submit.disabled = false;
            var left = free - n;
            feedback.textContent = 'Ci stanno: dopo lo spostamento su ' +
                opt.text.split(' — ')[0] +
                (left === 1 ? ' resterà 1 posto libero.'
                            : ' resteranno ' + left + ' posti liberi.');
            feedback.className = 'small mt-2 text-success';
        }

        if (allBox) {
            allBox.addEventListener('change', function () {
                Array.prototype.forEach.call(checks, function (c) { c.checked = allBox.checked; });
                refresh();
            });
        }
        Array.prototype.forEach.call(checks, function (c) {
            c.addEventListener('change', refresh);
        });
        target.addEventListener('change', refresh);

        form.addEventListener('submit', function (e) {
            var n = selected().length;
            if (n === 0 || !target.value) { e.preventDefault(); return; }
            var name = target.options[target.selectedIndex].text.split(' — ')[0];
            if (!confirm('Spostare ' + n + (n === 1 ? ' passeggero' : ' passeggeri') + ' su ' + name + '?')) {
                e.preventDefault();
            }
        });

        refresh();
    });
});
</script>
@endpush
@endonce
