@extends('layouts.admin')

@section('title', 'Prenotazioni')

@section('content')
    @php
        $statusMeta = [
            'pending'    => ['label' => 'In attesa',  'icon' => 'bi-hourglass-split', 'color' => 'warning'],
            'deposit_paid'      => ['label' => 'Acconto versato', 'icon' => 'bi-wallet2', 'color' => 'info'],
            'awaiting_transfer' => ['label' => 'Attesa bonifico', 'icon' => 'bi-bank',    'color' => 'warning'],
            'confirmed'  => ['label' => 'Confermata', 'icon' => 'bi-check-circle',   'color' => 'success'],
            'checked_in' => ['label' => 'Check-in',   'icon' => 'bi-qr-code-scan',   'color' => 'info'],
            'completed'  => ['label' => 'Completata', 'icon' => 'bi-flag-fill',      'color' => 'secondary'],
            'cancelled'  => ['label' => 'Annullata',  'icon' => 'bi-x-circle',       'color' => 'danger'],
            'no_show'    => ['label' => 'No show',    'icon' => 'bi-eye-slash',      'color' => 'secondary'],
        ];
        $currentStatus = request('status');
        $hasFilters = request()->hasAny(['search', 'status', 'tour', 'date_from', 'date_to']);

        // Card di riepilogo: includi "Acconto versato" solo se l'acconto è attivo.
        $miniStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        if (\App\Support\Settings::depositEnabled()) {
            array_splice($miniStatuses, 1, 0, ['deposit_paid']);
        }

        // Fuso orario di visualizzazione (il DB resta in UTC).
        $TZ = 'Europe/Rome';

        // Helper per le intestazioni ordinabili: link che alterna asc/desc + freccia.
        $sortLink = function (string $key, string $label, string $thClass = '') use ($sort, $dir) {
            $active = $sort === $key;
            $nextDir = ($active && $dir === 'asc') ? 'desc' : 'asc';
            $icon = $active ? ($dir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
            $params = array_merge(request()->except(['page', 'sort', 'dir']), ['sort' => $key, 'dir' => $nextDir]);
            $url = route('admin.bookings.index', $params);
            $opacity = $active ? '' : 'opacity-50';
            return '<th class="' . $thClass . '">'
                . '<a href="' . $url . '" class="text-reset text-decoration-none d-inline-flex align-items-center gap-1">'
                . e($label) . ' <i class="bi ' . $icon . ' small ' . $opacity . '"></i></a></th>';
        };
    @endphp

    {{-- Page header --}}
    <div class="dash-page-header">
        <div>
            <h1>Prenotazioni</h1>
            <p>Gestisci, filtra e monitora tutte le prenotazioni dei tuoi clienti.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($hasFilters)
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-light rounded-pill px-3 fw-semibold border">
                    <i class="bi bi-x-lg me-2"></i>Reset filtri
                </a>
            @endif
            <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-plus-lg me-2"></i>Nuova prenotazione
            </a>
        </div>
    </div>

    {{-- Mini stats / quick filters --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('admin.bookings.index') }}" class="dash-mini-stat text-decoration-none {{ !$currentStatus ? 'is-active' : '' }}">
                <span class="mini-stat-icon bg-primary-subtle text-primary"><i class="bi bi-collection"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $bookings->total() }}</div>
                    <div class="mini-stat-label">Totale</div>
                </div>
            </a>
        </div>
        @foreach ($miniStatuses as $st)
            @php $m = $statusMeta[$st]; @endphp
            <div class="col-6 col-md-4 col-xl">
                <a href="{{ route('admin.bookings.index', array_merge(request()->except('status', 'page'), ['status' => $st])) }}"
                   class="dash-mini-stat text-decoration-none {{ $currentStatus === $st ? 'is-active' : '' }}">
                    <span class="mini-stat-icon bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                        <i class="bi {{ $m['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="mini-stat-value">{{ $stats[$st] ?? 0 }}</div>
                        <div class="mini-stat-label">{{ $m['label'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="dash-filter-bar">
        <form action="{{ route('admin.bookings.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-6 col-xl-4">
                <label for="search" class="form-label">Cerca</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Nome, email, numero..." class="form-control">
                </div>
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="status" class="form-label">Stato</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Tutti</option>
                    @foreach ($statusMeta as $val => $m)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="tour" class="form-label">Tour</label>
                <select name="tour" id="tour" class="form-select">
                    <option value="">Tutti</option>
                    @foreach($tours as $tour)
                        <option value="{{ $tour->id }}" {{ request('tour') == $tour->id ? 'selected' : '' }}>
                            {{ $tour->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="date_from" class="form-label">Da</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="date_to" class="form-label">A</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                @if($hasFilters)
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-light rounded-pill px-3 border">Reset</a>
                @endif
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                    <i class="bi bi-funnel me-2"></i>Applica filtri
                </button>
            </div>
        </form>
    </div>

    {{-- Bookings table --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <h3>
                <i class="bi bi-list-ul me-2 text-primary"></i>
                Elenco prenotazioni
                <span class="ms-2 badge bg-light text-secondary fw-medium">{{ $bookings->total() }}</span>
            </h3>
            <div class="d-flex align-items-center gap-2 small text-muted">
                <i class="bi bi-info-circle"></i>
                Pagina {{ $bookings->currentPage() }} di {{ max(1, $bookings->lastPage()) }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        {!! $sortLink('number', 'Prenotazione') !!}
                        {!! $sortLink('customer', 'Cliente') !!}
                        {!! $sortLink('tour', 'Tour') !!}
                        {!! $sortLink('date', 'Data') !!}
                        {!! $sortLink('seats', 'Ospiti', 'text-center') !!}
                        {!! $sortLink('total', 'Totale', 'text-end') !!}
                        {!! $sortLink('status', 'Stato') !!}
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $sv = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : $booking->status;
                            $m = $statusMeta[$sv] ?? ['label' => ucfirst($sv), 'icon' => 'bi-circle', 'color' => 'secondary'];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="font-monospace fw-semibold text-primary text-decoration-none">
                                    #{{ $booking->booking_number }}
                                </a>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-clock me-1"></i>{{ $booking->created_at->timezone($TZ)->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar-sm bg-primary-subtle text-primary" style="font-size:.75rem">
                                        {{ strtoupper(substr($booking->customer_first_name, 0, 1) . substr($booking->customer_last_name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate" style="max-width:200px">
                                            {{ $booking->customer_first_name }} {{ $booking->customer_last_name }}
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width:200px">
                                            <i class="bi bi-envelope me-1"></i>{{ $booking->customer_email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-compass text-primary"></i>
                                    <span class="text-truncate d-inline-block" style="max-width:180px" title="{{ $booking->tour->name ?? '' }}">{{ $booking->tour->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $blk = $blockByBooking[$booking->booking_number] ?? null;
                                    if ($blk) {
                                        // Uso esclusivo: andata/ritorno dagli orari del blocco.
                                        $startDate = $blk->start_date;
                                        $endDate = $blk->end_date;
                                        $startT = $blk->start_time ? \Carbon\Carbon::parse($blk->start_time)->format('H:i') : null;
                                        $endT = $blk->end_time ? \Carbon\Carbon::parse($blk->end_time)->format('H:i') : null;
                                    } else {
                                        // Tour normale: stessa data andata/ritorno; orario dalla partenza
                                        // (end_time, oppure inizio + durata del tour).
                                        $startDate = \Carbon\Carbon::parse($booking->booking_date);
                                        $endDate = $startDate;
                                        $dep = $booking->departure;
                                        $startT = $dep && $dep->start_time ? \Carbon\Carbon::parse($dep->start_time)->format('H:i') : null;
                                        if ($dep && $dep->end_time) {
                                            $endT = \Carbon\Carbon::parse($dep->end_time)->format('H:i');
                                        } elseif ($dep && $dep->start_time) {
                                            $durMin = (int) round(((float) ($booking->tour->duration_hours ?? 1)) * 60);
                                            $endT = \Carbon\Carbon::parse($dep->start_time)->addMinutes($durMin)->format('H:i');
                                        } else {
                                            $endT = null;
                                        }
                                    }
                                    $sameDay = $startDate && $endDate
                                        && \Carbon\Carbon::parse($startDate)->isSameDay(\Carbon\Carbon::parse($endDate));
                                @endphp
                                <div class="small">
                                    <div>
                                        <span class="text-muted">Andata:</span>
                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</span>
                                        @if($startT)<span class="text-muted">· {{ $startT }}</span>@endif
                                    </div>
                                    <div>
                                        <span class="text-muted">Ritorno:</span>
                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
                                        @if($endT)<span class="text-muted">· {{ $endT }}</span>@endif
                                    </div>
                                    @if($blk)
                                        <span class="badge bg-info-subtle text-info mt-1"><i class="bi bi-water me-1"></i>Uso esclusivo</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="d-inline-flex align-items-center justify-content-center bg-light rounded-pill px-3 py-1 fw-semibold small">
                                    <i class="bi bi-people me-1 text-muted"></i>{{ $booking->seats }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold">€{{ number_format($booking->total_amount, 2, ',', '.') }}</span>
                            </td>
                            <td>
                                <span class="status-pill s-{{ $sv }}">
                                    <i class="bi {{ $m['icon'] }}"></i>{{ $m['label'] }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="dash-icon-btn is-primary" title="Visualizza" data-bs-toggle="tooltip">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($sv === 'pending')
                                        <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dash-icon-btn is-success" title="Conferma" data-bs-toggle="tooltip">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!in_array($sv, ['cancelled', 'completed']))
                                        <button type="button" class="dash-icon-btn is-danger"
                                                title="Annulla" data-bs-toggle="modal"
                                                data-bs-target="#cancelBookingModal"
                                                data-action="{{ route('admin.bookings.cancel', $booking) }}"
                                                data-booking="{{ $booking->booking_number }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                    @if($sv === 'cancelled' && (float) $booking->total_amount > 0)
                                        <button type="button" class="dash-icon-btn is-info"
                                                title="Rimborsa" data-bs-toggle="modal"
                                                data-bs-target="#refundBookingModal"
                                                data-action="{{ route('admin.bookings.refund', $booking) }}"
                                                data-booking="{{ $booking->booking_number }}"
                                                data-total="{{ number_format((float) $booking->total_amount, 2, '.', '') }}">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x display-4 opacity-50 d-block mb-3"></i>
                                    <p class="fw-semibold mb-1">Nessuna prenotazione trovata</p>
                                    <p class="small mb-3">Prova a modificare i filtri di ricerca.</p>
                                    @if($hasFilters)
                                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Rimuovi filtri
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 px-3 py-3 border-top small text-muted">
                <div>
                    Mostrando <strong>{{ $bookings->firstItem() }}–{{ $bookings->lastItem() }}</strong>
                    di <strong>{{ $bookings->total() }}</strong>
                </div>
                <div>
                    {{ $bookings->withQueryString()->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Modal annulla (azione impostata dinamicamente dal bottone cliccato) --}}
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="cancelBookingForm" method="POST" class="modal-content rounded-4 border-0" action="">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Annulla prenotazione <span id="cancelBookingNumber" class="text-muted small ms-1"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Indica il motivo dell'annullamento. Verrà registrato nello storico e inviato per email al cliente.</p>
                    <textarea name="reason" rows="3" class="form-control rounded-3" required maxlength="500"
                              placeholder="Es. richiesta del cliente, maltempo…"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Indietro</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3 fw-semibold">
                        <i class="bi bi-x-lg me-2"></i>Annulla prenotazione
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal rimborso (azione e totale impostati dinamicamente) --}}
    <div class="modal fade" id="refundBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="refundBookingForm" method="POST" class="modal-content rounded-4 border-0" action="">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Registra rimborso <span id="refundBookingNumber" class="text-muted small ms-1"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Marca la prenotazione come rimborsata e invia una mail al cliente con i dettagli.
                        L'accredito sulla carta va effettuato a parte (gestionale Stripe / banca).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Importo rimborsato</label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input type="number" step="0.01" min="0" name="amount" id="refundAmount" class="form-control" value="0.00">
                        </div>
                        <small class="text-muted">Totale prenotazione: € <span id="refundBookingTotal">0,00</span></small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Nota (opzionale)</label>
                        <textarea name="note" rows="2" class="form-control rounded-3" maxlength="500"
                                  placeholder="Es. rimborso parziale per spese di gestione…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Indietro</button>
                    <button type="submit" class="btn btn-info text-white rounded-pill px-3 fw-semibold">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Registra rimborso
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        var cancelModal = document.getElementById('cancelBookingModal');
        if (cancelModal) {
            var form = document.getElementById('cancelBookingForm');
            var label = document.getElementById('cancelBookingNumber');
            cancelModal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                if (!btn) return;
                form.action = btn.getAttribute('data-action') || '';
                label.textContent = '#' + (btn.getAttribute('data-booking') || '');
                form.querySelector('textarea[name="reason"]').value = '';
            });
        }

        var refundModal = document.getElementById('refundBookingModal');
        if (refundModal) {
            var rForm = document.getElementById('refundBookingForm');
            var rLabel = document.getElementById('refundBookingNumber');
            var rTotal = document.getElementById('refundBookingTotal');
            var rAmount = document.getElementById('refundAmount');
            refundModal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                if (!btn) return;
                var total = parseFloat(btn.getAttribute('data-total') || '0') || 0;
                rForm.action = btn.getAttribute('data-action') || '';
                rLabel.textContent = '#' + (btn.getAttribute('data-booking') || '');
                rTotal.textContent = total.toFixed(2).replace('.', ',');
                rAmount.value = total.toFixed(2);
                rAmount.max = total.toFixed(2);
                rForm.querySelector('textarea[name="note"]').value = '';
            });
        }
    });
</script>
@endpush
