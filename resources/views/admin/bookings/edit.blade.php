@extends('layouts.admin')

@section('title', 'Modifica prenotazione ' . $booking->booking_number)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">
@endpush

@section('content')
    @php
        $statusValue = $booking->status?->value ?? (string) $booking->status;
    @endphp

    {{-- Header --}}
    <div class="dash-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left"></i> Torna alla prenotazione
                </a>
            </div>
            <h1 class="mb-1">Modifica #{{ $booking->booking_number }}</h1>
            <p class="text-muted mb-0">
                {{ $booking->customer_first_name }} {{ $booking->customer_last_name }}
                · {{ $booking->tour?->name ?? '—' }}
                @if ($booking->departure?->departure_date)
                    · {{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }}
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-light rounded-pill border px-3 fw-semibold">
                Annulla
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger rounded-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Rimozione partecipanti / extra (disdette con rimborso parziale) --}}
    @php
        $activeSeats = $booking->seatRecords->whereNull('cancelled_at');
        $cancelledSeats = $booking->seatRecords->whereNotNull('cancelled_at');
        $activeAddons = $booking->addons->whereNull('cancelled_at');
        $cancelledAddons = $booking->addons->whereNotNull('cancelled_at');
        $fmt = fn ($v) => '€ ' . number_format((float) $v, 2, ',', '.');
    @endphp

    @if (!in_array($statusValue, ['cancelled', 'refunded']))
    <form action="{{ route('admin.bookings.remove-items', $booking) }}" method="POST" id="removeItemsForm">
        @csrf
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-person-dash me-2 text-danger"></i>Disdetta partecipanti / extra</h5>
                <p class="text-muted small mb-3">Seleziona chi/cosa rimuovere. Alla conferma vedrai il riepilogo penale/rimborso sulla somma dei rimossi. Gli elementi restano nello storico marcati come disdetti.</p>

                <div class="table-responsive mb-3">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px"></th>
                                <th>Partecipante</th>
                                <th>Fascia</th>
                                <th class="text-end">Prezzo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeSeats as $seat)
                                <tr>
                                    <td>
                                        @if ($seat->is_primary)
                                            <span title="Intestatario (non rimovibile)"><i class="bi bi-star-fill text-warning"></i></span>
                                        @else
                                            <input type="checkbox" class="form-check-input rm-seat" name="seat_ids[]" value="{{ $seat->id }}" data-price="{{ (float) $seat->price_paid }}">
                                        @endif
                                    </td>
                                    <td>
                                        {{ trim($seat->guest_first_name . ' ' . $seat->guest_last_name) ?: '—' }}
                                        @if ($seat->is_primary)<span class="badge bg-warning-subtle text-warning ms-1">Intestatario</span>@endif
                                    </td>
                                    <td>{{ $seat->ageBracket?->label ?? 'Adulto' }}</td>
                                    <td class="text-end">{{ $fmt($seat->price_paid) }}</td>
                                </tr>
                            @endforeach
                            @foreach ($activeAddons as $ad)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input rm-addon" name="addon_ids[]" value="{{ $ad->id }}" data-price="{{ (float) $ad->total_price }}"></td>
                                    <td><i class="bi bi-plus-square me-1 text-muted"></i>{{ $ad->addon?->name ?? 'Extra' }} <span class="text-muted small">×{{ $ad->quantity }}</span></td>
                                    <td><span class="badge bg-light text-muted">Extra</span></td>
                                    <td class="text-end">{{ $fmt($ad->total_price) }}</td>
                                </tr>
                            @endforeach
                            @if ($cancelledSeats->isNotEmpty() || $cancelledAddons->isNotEmpty())
                                @foreach ($cancelledSeats as $seat)
                                    <tr class="text-muted text-decoration-line-through">
                                        <td><i class="bi bi-x-circle text-danger"></i></td>
                                        <td>{{ trim($seat->guest_first_name . ' ' . $seat->guest_last_name) ?: '—' }}</td>
                                        <td colspan="2" class="text-end small">Disdetto {{ optional($seat->cancelled_at)->timezone('Europe/Rome')->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                                @foreach ($cancelledAddons as $ad)
                                    <tr class="text-muted text-decoration-line-through">
                                        <td><i class="bi bi-x-circle text-danger"></i></td>
                                        <td>{{ $ad->addon?->name ?? 'Extra' }}</td>
                                        <td colspan="2" class="text-end small">Disdetto {{ optional($ad->cancelled_at)->timezone('Europe/Rome')->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">Selezionati: <strong id="rmCount">0</strong> · Totale rimossi: <strong id="rmTotal">€ 0,00</strong></div>
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 fw-semibold" id="openRemoveModal" disabled>
                        <i class="bi bi-person-dash me-1"></i>Rimuovi selezionati
                    </button>
                </div>
            </div>
        </div>

        {{-- Modale conferma con penale/rimborso sui rimossi --}}
        <div class="modal fade" id="removeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">Conferma rimozione e rimborso</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Costo degli elementi rimossi: <strong id="rmTotalModal">€ 0,00</strong></p>
                        <label class="form-label fw-semibold small">Motivo</label>
                        <textarea name="reason" rows="2" class="form-control rounded-3 mb-3" maxlength="500" placeholder="Es. disdetta di un partecipante…"></textarea>

                        <label class="form-label fw-semibold small">Rimborso al cliente</label>
                        <div class="d-flex flex-column gap-2">
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="penalty" class="form-check-input mt-1" checked>
                                <span class="small">Applica penale ({{ 100 - $refundPercentage }}%) → rimborsa <strong id="rmPenaltyRefund">€ 0,00</strong>
                                    <span class="text-muted d-block">Rimborso {{ $refundPercentage }}% secondo la policy</span></span>
                            </label>
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="full" class="form-check-input mt-1">
                                <span class="small">Rimborso totale dei rimossi → <strong id="rmFullRefund">€ 0,00</strong></span>
                            </label>
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="custom" class="form-check-input mt-1" id="rmCustomRadio">
                                <span class="small flex-grow-1">Importo personalizzato
                                    <span class="input-group input-group-sm mt-1" style="max-width:200px">
                                        <span class="input-group-text">€</span>
                                        <input type="number" step="0.01" min="0" name="refund_amount" class="form-control" placeholder="0,00"
                                               onfocus="document.getElementById('rmCustomRadio').checked=true">
                                    </span>
                                </span>
                            </label>
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="none" class="form-check-input mt-1">
                                <span class="small">Nessun rimborso</span>
                            </label>
                        </div>
                        <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Per carta il rimborso è su Stripe; per bonifico/contanti è manuale.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Indietro</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-3 fw-semibold"><i class="bi bi-check2 me-1"></i>Conferma rimozione</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif

    {{-- Cambio data con conguaglio --}}
    @if (!in_array($statusValue, ['cancelled', 'refunded', 'completed']))
    <form action="{{ route('admin.bookings.reschedule', $booking) }}" method="POST" id="rescheduleForm">
        @csrf
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-calendar2-week me-2 text-primary"></i>Cambia data</h5>

                @if ($reservedBlock)
                    {{-- Uso esclusivo (multi-giorno): nuovo periodo con orari, date libere. --}}
                    <p class="text-muted small mb-3">Catamarano riservato: sposta l'intero periodo. Le date sono libere e i catamarani riservati vengono spostati al nuovo periodo (se liberi).</p>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="ex-start-date" class="form-label fw-semibold">Partenza</label>
                            <input type="text" name="new_start_date" id="ex-start-date" class="form-control" autocomplete="off"
                                   value="{{ $reservedBlock->start_date->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="ex-start-time" class="form-label fw-semibold">Ora</label>
                            <input type="time" name="new_start_time" id="ex-start-time" class="form-control"
                                   value="{{ $reservedBlock->start_time ? \Carbon\Carbon::parse($reservedBlock->start_time)->format('H:i') : '09:00' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="ex-end-date" class="form-label fw-semibold">Ritorno</label>
                            <input type="text" name="new_end_date" id="ex-end-date" class="form-control" autocomplete="off"
                                   value="{{ $reservedBlock->end_date->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="ex-end-time" class="form-label fw-semibold">Ora</label>
                            <input type="time" name="new_end_time" id="ex-end-time" class="form-control"
                                   value="{{ $reservedBlock->end_time ? \Carbon\Carbon::parse($reservedBlock->end_time)->format('H:i') : '18:00' }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold">
                                <i class="bi bi-arrow-left-right me-1"></i>Sposta
                            </button>
                        </div>
                    </div>
                @else
                    <p class="text-muted small mb-3">Sposta la prenotazione su un'altra data/orario. Se il prezzo cambia, vedrai il conguaglio da gestire secondo il metodo di pagamento usato.</p>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="resched-date" class="form-label fw-semibold">Nuova data</label>
                            <input type="text" id="resched-date" class="form-control" placeholder="Caricamento date…" autocomplete="off" disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="resched-time" class="form-label fw-semibold">Orario</label>
                            <select id="resched-time" class="form-select" disabled><option value="">—</option></select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary rounded-pill w-100 fw-semibold" id="reschedPreviewBtn" disabled>
                                <i class="bi bi-arrow-left-right me-1"></i>Verifica
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="tour_departure_id" id="resched-departure-id">
                    <div id="reschedStatus" class="small mt-2"></div>
                @endif
            </div>
        </div>

        {{-- Modale conguaglio --}}
        <div class="modal fade" id="reschedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">Conferma cambio data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Nuova partenza</span><strong id="md-newdate">—</strong></div>
                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Totale attuale</span><span id="md-old">—</span></div>
                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Nuovo totale</span><span id="md-new">—</span></div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-1"><span id="md-difflabel">Differenza</span><span id="md-diff">—</span></div>
                        <div class="small text-muted mt-1">Metodo di pagamento: <strong id="md-method">—</strong></div>

                        {{-- Conguaglio IN AUMENTO con bonifico/manuale --}}
                        <div id="md-surcharge" class="mt-3" style="display:none">
                            <label class="form-label fw-semibold small">Come registrare il conguaglio</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="d-flex align-items-start gap-2"><input type="radio" name="surcharge_handling" value="paid" class="form-check-input mt-1" checked><span class="small">Già incassato (contanti/POS/bonifico ricevuto)</span></label>
                                <label class="d-flex align-items-start gap-2"><input type="radio" name="surcharge_handling" value="pending" class="form-check-input mt-1"><span class="small">In attesa di incasso</span></label>
                            </div>
                        </div>
                        {{-- Conguaglio IN AUMENTO con Stripe --}}
                        <div id="md-stripe" class="mt-3 small text-muted" style="display:none">
                            <i class="bi bi-info-circle me-1"></i>Verrà generato un link di pagamento per la differenza, da inviare al cliente dal dettaglio.
                        </div>
                        {{-- Differenza A CREDITO --}}
                        <div id="md-credit" class="mt-3" style="display:none">
                            <label class="form-label fw-semibold small">Differenza a favore del cliente</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="d-flex align-items-start gap-2"><input type="radio" name="credit_mode" value="none" class="form-check-input mt-1" checked><span class="small">Non rimborsare (sposta soltanto)</span></label>
                                <label class="d-flex align-items-start gap-2"><input type="radio" name="credit_mode" value="refund" class="form-check-input mt-1"><span class="small">Rimborsa la differenza</span></label>
                                <label class="d-flex align-items-start gap-2"><input type="radio" name="credit_mode" value="custom" class="form-check-input mt-1" id="creditCustomRadio"><span class="small flex-grow-1">Importo personalizzato
                                    <span class="input-group input-group-sm mt-1" style="max-width:200px"><span class="input-group-text">€</span>
                                    <input type="number" step="0.01" min="0" name="credit_amount" class="form-control" placeholder="0,00" onfocus="document.getElementById('creditCustomRadio').checked=true"></span>
                                </span></label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Indietro</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-3 fw-semibold"><i class="bi bi-check2 me-1"></i>Conferma cambio data</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i>Dettagli modificabili</h5>

                        @if ($booking->tour?->booking_on_request)
                            <div class="mb-3">
                                <label for="total_price" class="form-label fw-semibold">Prezzo totale (€)</label>
                                <input type="number" step="0.01" min="0" name="total_price" id="total_price"
                                       class="form-control"
                                       value="{{ old('total_price', number_format((float) $booking->total_amount, 2, '.', '')) }}">
                                <small class="text-muted">Tour su richiesta / catamarano riservato: prezzo totale manuale della prenotazione.</small>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Stato</label>
                            <select name="status" id="status" class="form-select">
                                @foreach ($statuses as $st)
                                    <option value="{{ $st->value }}" {{ old('status', $statusValue) === $st->value ? 'selected' : '' }}>
                                        {{ $st->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="form-label fw-semibold">Telefono cliente</label>
                            <input type="text" name="customer_phone" id="customer_phone"
                                   class="form-control"
                                   value="{{ old('customer_phone', $booking->customer_phone) }}"
                                   maxlength="30"
                                   placeholder="+39 ...">
                        </div>

                        <div class="mb-0">
                            <label for="special_requests" class="form-label fw-semibold">Note / richieste</label>
                            <textarea name="special_requests" id="special_requests"
                                      class="form-control" rows="4" maxlength="1000"
                                      placeholder="Note interne o richieste del cliente">{{ old('special_requests', $booking->special_requests) }}</textarea>
                            <small class="text-muted">Max 1000 caratteri.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Riferimento</h5>

                        <div class="mb-2">
                            <div class="text-muted small">Cliente</div>
                            <div class="fw-semibold">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">{{ $booking->customer_email }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Tour</div>
                            <div class="fw-semibold">{{ $booking->tour?->name ?? '—' }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Partenza</div>
                            <div class="fw-semibold">
                                @if ($booking->departure)
                                    {{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }}
                                    · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}
                                @else — @endif
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted small">Posti</div>
                            <div class="fw-semibold">{{ $booking->seats }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                        <i class="bi bi-check2 me-1"></i> Salva modifiche
                    </button>
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-light border rounded-pill fw-semibold py-2">
                        Annulla
                    </a>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        const refundPct = {{ (int) $refundPercentage }};
        const checks = Array.from(document.querySelectorAll('.rm-seat, .rm-addon'));
        if (!checks.length) return;

        const eur = n => '€ ' + (Number(n) || 0).toFixed(2).replace('.', ',');
        const rmCount = document.getElementById('rmCount');
        const rmTotal = document.getElementById('rmTotal');
        const rmTotalModal = document.getElementById('rmTotalModal');
        const rmPenaltyRefund = document.getElementById('rmPenaltyRefund');
        const rmFullRefund = document.getElementById('rmFullRefund');
        const openBtn = document.getElementById('openRemoveModal');

        function selected() {
            return checks.filter(c => c.checked);
        }
        function recompute() {
            const sel = selected();
            const total = sel.reduce((s, c) => s + (parseFloat(c.dataset.price) || 0), 0);
            rmCount.textContent = sel.length;
            rmTotal.textContent = eur(total);
            rmTotalModal.textContent = eur(total);
            rmPenaltyRefund.textContent = eur(total * refundPct / 100);
            rmFullRefund.textContent = eur(total);
            openBtn.disabled = sel.length === 0;
        }
        checks.forEach(c => c.addEventListener('change', recompute));
        recompute();

        openBtn.addEventListener('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('removeModal'));
            modal.show();
        });
    })();
    </script>

    {{-- Cambio data --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js"></script>
    <script src="https://npmcdn.com/flatpickr@4/dist/l10n/it.js"></script>
    <script>
    (function () {
        const dateInput = document.getElementById('resched-date');
        const timeSelect = document.getElementById('resched-time');
        const depIdInput = document.getElementById('resched-departure-id');
        const previewBtn = document.getElementById('reschedPreviewBtn');
        const statusEl = document.getElementById('reschedStatus');
        if (!dateInput) return;

        const eur = n => '€ ' + (Number(n) || 0).toFixed(2).replace('.', ',');
        const depUrl = @json(route('admin.bookings.departures.json', ['tour' => $booking->tour_id], false));
        const previewUrl = @json(route('admin.bookings.reschedule-preview', $booking, false));

        let byDate = {};
        let fp = null;

        fetch(depUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const deps = data.departures || [];
                byDate = {};
                deps.forEach(d => { (byDate[d.iso_date] = byDate[d.iso_date] || []).push(d); });
                const enable = Object.keys(byDate);
                if (!enable.length) { dateInput.placeholder = 'Nessuna data disponibile'; return; }
                dateInput.disabled = false;
                dateInput.placeholder = 'Seleziona una data…';
                fp = flatpickr(dateInput, {
                    enable, dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', disableMobile: true,
                    locale: (flatpickr.l10ns && flatpickr.l10ns.it) ? 'it' : 'default',
                    onChange: (sel, s) => pickDate(s),
                });
            })
            .catch(() => { dateInput.placeholder = 'Errore caricamento date'; });

        function pickDate(dateStr) {
            const list = byDate[dateStr] || [];
            timeSelect.disabled = list.length === 0;
            timeSelect.innerHTML = list.map(d => `<option value="${d.id}">${d.time}</option>`).join('') || '<option value="">—</option>';
            sync();
        }
        function sync() {
            depIdInput.value = timeSelect.value || '';
            previewBtn.disabled = !depIdInput.value;
        }
        timeSelect.addEventListener('change', sync);

        previewBtn.addEventListener('click', function () {
            if (!depIdInput.value) return;
            statusEl.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Calcolo differenza…</span>';
            const url = previewUrl + '?tour_departure_id=' + encodeURIComponent(depIdInput.value);
            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j)))
                .then(data => { statusEl.innerHTML = ''; openModal(data); })
                .catch(j => { statusEl.innerHTML = '<span class="text-danger">' + (j.error || 'Errore nel calcolo.') + '</span>'; });
        });

        function openModal(d) {
            const diff = Number(d.difference) || 0;
            document.getElementById('md-newdate').textContent = d.new_date + ' · ' + d.new_time;
            document.getElementById('md-old').textContent = eur(d.old_total);
            document.getElementById('md-new').textContent = eur(d.new_total);
            const methodLabel = { stripe: 'Carta (Stripe)', bank_transfer: 'Bonifico', manual: 'Manuale/contanti' }[d.payment_method] || d.payment_method;
            document.getElementById('md-method').textContent = methodLabel;

            const diffEl = document.getElementById('md-diff');
            const diffLabel = document.getElementById('md-difflabel');
            diffEl.textContent = (diff > 0 ? '+ ' : (diff < 0 ? '− ' : '')) + eur(Math.abs(diff));
            diffLabel.textContent = diff > 0 ? 'Conguaglio da incassare' : (diff < 0 ? 'Differenza a favore del cliente' : 'Differenza');

            const surcharge = document.getElementById('md-surcharge');
            const stripe = document.getElementById('md-stripe');
            const credit = document.getElementById('md-credit');
            surcharge.style.display = 'none'; stripe.style.display = 'none'; credit.style.display = 'none';

            if (diff > 0.001) {
                if (d.payment_method === 'stripe') stripe.style.display = '';
                else surcharge.style.display = '';
            } else if (diff < -0.001) {
                credit.style.display = '';
            }
            new bootstrap.Modal(document.getElementById('reschedModal')).show();
        }
    })();
    </script>

    {{-- Cambio data uso esclusivo: date libere partenza/ritorno --}}
    <script>
    (function () {
        const startEl = document.getElementById('ex-start-date');
        const endEl = document.getElementById('ex-end-date');
        if (!startEl || !endEl || typeof flatpickr === 'undefined') return;
        const opts = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', disableMobile: true,
            locale: (flatpickr.l10ns && flatpickr.l10ns.it) ? 'it' : 'default' };
        const fpStart = flatpickr(startEl, { ...opts, onChange: (sel, s) => {
            if (fpEnd) { fpEnd.set('minDate', s); if (endEl.value && endEl.value < s) fpEnd.setDate(s, true); }
        }});
        const fpEnd = flatpickr(endEl, { ...opts, minDate: startEl.value || null });
    })();
    </script>
    @endpush
@endsection
