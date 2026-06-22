@extends('layouts.admin')

@section('title', 'Nuova prenotazione')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">
<style>
    .ab-participant-row { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 10px; }
    .ab-participant-row .ab-row-head { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .ab-child-resolved { font-size: .82rem; }
    .ab-child-resolved.ok { color: #059669; }
    .ab-child-resolved.err { color: #dc2626; }
    .flatpickr-day.flatpickr-disabled { text-decoration: line-through; opacity: .35; }
</style>
@endpush

@section('content')
    <div class="dash-page-header">
        <div>
            <h1>Nuova prenotazione</h1>
            <p>Crea manualmente una prenotazione (telefono, walk-in, agenzia). Le date passate sono ammesse per registrazioni retroattive.</p>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Annulla
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Controlla i campi:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.bookings.store') }}" id="adminBookingForm">
        @csrf

        <div class="row g-3">
            {{-- LEFT --}}
            <div class="col-lg-8">
                {{-- 1. Tour & partenza --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-compass me-2 text-primary"></i>1. Tour e partenza</h3>
                    </div>
                    <div class="dash-card-body">
                        {{-- 1) Tour --}}
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="tour_id" class="form-label fw-semibold">Tour *</label>
                                <select name="tour_id" id="tour_id" class="form-select" required>
                                    <option value="">— Seleziona tour —</option>
                                    @foreach($tours as $tour)
                                        <option value="{{ $tour->id }}" {{ old('tour_id', $selectedTour?->id) == $tour->id ? 'selected' : '' }}>
                                            {{ $tour->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- 2) Uso esclusivo (riserva catamarano) --}}
                        <div class="row g-3 mt-1" id="exclusive-toggle-area" style="display:none">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="block_catamaran_day" id="block_catamaran_day" value="1" role="switch">
                                    <label class="form-check-label fw-semibold" for="block_catamaran_day">
                                        Riserva il catamarano (uso esclusivo)
                                    </label>
                                    <div class="form-text">Blocca uno o più catamarani per un periodo. Le date e gli orari sotto definiscono partenza e ritorno; puoi superare la capienza scegliendo più catamarani.</div>
                                </div>
                            </div>
                        </div>

                        {{-- 3) Data/ora partenza + (orario | data/ora ritorno) --}}
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label for="booking-date-input" class="form-label fw-semibold">Data di partenza *</label>
                                <input type="text" id="booking-date-input" class="form-control" placeholder="Seleziona un tour…" autocomplete="off" disabled>
                                <div class="form-text" id="departure-status"></div>
                            </div>

                            {{-- Modalità normale: orario partenza (dalle partenze a calendario) --}}
                            <div class="col-md-6" id="time-col">
                                <label for="departure-time" class="form-label fw-semibold">Orario *</label>
                                <select id="departure-time" class="form-select" disabled>
                                    <option value="">—</option>
                                </select>
                            </div>

                            {{-- Modalità uso esclusivo: ora partenza + data/ora ritorno --}}
                            <div class="col-md-6" id="start-time-col" style="display:none">
                                <label for="block_start_time" class="form-label fw-semibold">Ora di partenza *</label>
                                <input type="time" class="form-control" name="block_start_time" id="block_start_time" value="{{ old('block_start_time', '09:00') }}">
                            </div>
                            <div class="col-md-6" id="return-col" style="display:none">
                                <label for="return-date-input" class="form-label fw-semibold">Data di ritorno *</label>
                                <input type="text" id="return-date-input" class="form-control" placeholder="Seleziona la data di ritorno…" autocomplete="off">
                            </div>
                            <div class="col-md-6" id="end-time-col" style="display:none">
                                <label for="block_end_time" class="form-label fw-semibold">Ora di ritorno *</label>
                                <input type="time" class="form-control" name="block_end_time" id="block_end_time" value="{{ old('block_end_time', '18:00') }}">
                            </div>
                        </div>

                        {{-- 4) Catamarano: singolo (normale) o multi-selezione (uso esclusivo) --}}
                        {{-- Normale: un solo catamarano (o automatico) --}}
                        <div class="row g-3 mt-1" id="single-catamaran-area" style="display:none">
                            <div class="col-md-8">
                                <label for="catamaran_id" class="form-label fw-semibold">Catamarano</label>
                                <select name="catamaran_id" id="catamaran_id" class="form-select">
                                    <option value="">Automatico (assegna il sistema)</option>
                                </select>
                                <div class="form-text">Lascia "Automatico" per far scegliere al sistema il catamarano con più posti liberi.</div>
                            </div>
                        </div>

                        {{-- Uso esclusivo: scegli uno o più catamarani DISPONIBILI nel periodo --}}
                        <div class="mt-3" id="multi-catamaran-area" style="display:none">
                            <label class="form-label fw-semibold">Catamarani da riservare *</label>
                            <div id="multi-catamaran-status" class="form-text mb-2">Seleziona data di partenza e ritorno per vedere i catamarani disponibili.</div>
                            <div id="multi-catamaran-list" class="d-flex flex-column gap-2"></div>
                        </div>

                        {{-- Partenza effettivamente scelta: id reale, oppure "virt:Y-m-d:H:i" --}}
                        <input type="hidden" name="tour_departure_id" id="tour_departure_id" value="{{ old('tour_departure_id') }}">
                        {{-- Periodo di blocco (valorizzato in modalità uso esclusivo) --}}
                        <input type="hidden" name="block_start_date" id="block_start_date" value="{{ old('block_start_date') }}">
                        <input type="hidden" name="block_end_date" id="block_end_date" value="{{ old('block_end_date') }}">
                    </div>
                </div>

                {{-- 2. Partecipanti --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>2. Partecipanti</h3>
                    </div>
                    <div class="dash-card-body">
                        <div id="participants-empty" class="text-muted text-center py-3">
                            <i class="bi bi-arrow-up me-1"></i>Seleziona prima tour e partenza.
                        </div>

                        <div id="participants-area" class="d-none">
                            {{-- Prezzo totale manuale (solo tour su richiesta) --}}
                            <div id="onrequest-price-area" class="alert alert-info py-2 px-3 mb-3" style="display:none">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-cash-coin"></i>
                                    <span class="fw-semibold small">Tour su richiesta: inserisci il prezzo totale (catamarano riservato).</span>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto"><label for="total_price" class="col-form-label small mb-0">Prezzo totale (€)</label></div>
                                    <div class="col-sm-4">
                                        <input type="number" min="0" step="0.01" class="form-control form-control-sm" name="total_price" id="total_price" value="{{ old('total_price') }}" placeholder="0,00">
                                    </div>
                                    <div class="col-12"><div class="form-text">Adulti e bambini servono solo a contare i posti. Il prezzo è il totale finale della prenotazione.</div></div>
                                </div>
                            </div>

                            {{-- Adulti --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Adulti <span class="text-muted small">(prezzo pieno)</span></div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="adults-minus"><i class="bi bi-dash"></i></button>
                                    <span class="btn btn-light btn-sm disabled" id="adults-count-label" style="min-width:42px">1</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="adults-plus"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div id="adults-list" class="mb-3"></div>

                            {{-- Bambini --}}
                            <div class="d-flex justify-content-between align-items-center mb-2" id="children-header">
                                <div class="fw-semibold">Bambini <span class="text-muted small">(con riduzione per età)</span></div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-child"><i class="bi bi-plus me-1"></i>Aggiungi bambino</button>
                            </div>
                            <div id="children-list"></div>
                            <div id="children-empty" class="text-muted small">Nessun bambino. Usa "Aggiungi bambino" e inserisci la data di nascita.</div>
                        </div>
                    </div>
                </div>

                {{-- 3. Cliente --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-person-badge me-2 text-primary"></i>3. Cliente (intestatario)</h3>
                    </div>
                    <div class="dash-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nome *</label>
                                <input type="text" name="customer_first_name" id="customer_first_name" value="{{ old('customer_first_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cognome *</label>
                                <input type="text" name="customer_last_name" id="customer_last_name" value="{{ old('customer_last_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email *</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Codice fiscale</label>
                                <input type="text" name="customer_tax_code" value="{{ old('customer_tax_code') }}" class="form-control text-uppercase" maxlength="16" placeholder="Facoltativo">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Telefono</label>
                                <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Paese</label>
                                <input type="text" name="customer_country" value="{{ old('customer_country', 'IT') }}" class="form-control" maxlength="3">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Richieste speciali / note interne</label>
                                <textarea name="special_requests" rows="2" class="form-control" maxlength="1000">{{ old('special_requests') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Sconto (opzionale) --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-tag me-2 text-primary"></i>4. Codice sconto (opzionale)</h3>
                    </div>
                    <div class="dash-card-body">
                        <input type="text" name="discount_code" value="{{ old('discount_code') }}" class="form-control" placeholder="Es. ESTATE2026" maxlength="50">
                    </div>
                </div>
            </div>

            {{-- RIGHT: riepilogo --}}
            <div class="col-lg-4">
                <div class="dash-card mb-3 sticky-top" style="top: 90px;">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-receipt me-2 text-primary"></i>Riepilogo</h3>
                    </div>
                    <div class="dash-card-body">
                        <div id="summary-box">
                            <div class="text-muted small text-center py-3">
                                Compila il form per vedere il riepilogo.
                            </div>
                        </div>

                        <hr>

                        {{-- Metodo di pagamento --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pagamento</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="d-flex align-items-start gap-2">
                                    <input type="radio" name="payment_method" value="manual" class="form-check-input mt-1" {{ old('payment_method', 'manual') === 'manual' ? 'checked' : '' }}>
                                    <span class="small">Già incassato (contanti / POS / altro)
                                        <span class="text-muted d-block">Registra subito l'incasso e conferma la prenotazione.</span></span>
                                </label>
                                <label class="d-flex align-items-start gap-2">
                                    <input type="radio" name="payment_method" value="stripe" class="form-check-input mt-1" {{ old('payment_method') === 'stripe' ? 'checked' : '' }}>
                                    <span class="small">Link di pagamento (Stripe)
                                        <span class="text-muted d-block">Genera un link da inviare al cliente; resta "in attesa".</span></span>
                                </label>
                                @if($bankTransferEnabled)
                                    <label class="d-flex align-items-start gap-2">
                                        <input type="radio" name="payment_method" value="bank_transfer" class="form-check-input mt-1" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}>
                                        <span class="small">Bonifico bancario
                                            <span class="text-muted d-block">In attesa di bonifico; confermi tu l'incasso.</span></span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        {{-- Acconto / 2 rate (solo se abilitato nelle impostazioni) --}}
                        @if($depositEnabled)
                            <div class="mb-3" id="installment-block">
                                <label class="form-label fw-semibold small">Rata</label>
                                <select name="payment_installment" id="payment_installment" class="form-select form-select-sm">
                                    <option value="full" {{ old('payment_installment', 'full') === 'full' ? 'selected' : '' }}>Intero importo</option>
                                    <option value="deposit" {{ old('payment_installment') === 'deposit' ? 'selected' : '' }}>Acconto {{ $depositPercentage }}% (saldo successivo)</option>
                                </select>
                                <div class="form-text">Con "Acconto" viene registrata/richiesta solo la prima rata.</div>
                            </div>
                        @endif

                        {{-- Stato avanzato (opzionale): forza lo stato per retroattive --}}
                        <details class="mb-3">
                            <summary class="form-label fw-semibold small mb-0" style="cursor:pointer">Stato avanzato (opzionale)</summary>
                            <div class="mt-2">
                                <select name="status" id="status" class="form-select">
                                    <option value="">— Automatico dal pagamento —</option>
                                    @foreach($statuses as $st)
                                        <option value="{{ $st->value }}" {{ old('status') === $st->value ? 'selected' : '' }}>
                                            {{ $st->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Usa solo per registrazioni retroattive (es. Completata, Check-in).</div>
                            </div>
                        </details>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold mt-1">
                            <i class="bi bi-check2-circle me-1"></i>Crea prenotazione
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js"></script>
<script src="https://npmcdn.com/flatpickr@4/dist/l10n/it.js"></script>
<script>
(function () {
    const departuresUrlTpl = @json(route('admin.bookings.departures.json', ['tour' => '__TOUR__'], false));
    const catAvailUrlTpl = @json(route('admin.bookings.catamaran-availability', ['tour' => '__TOUR__'], false));

    const tourSelect = document.getElementById('tour_id');
    const dateInput = document.getElementById('booking-date-input');
    const timeSelect = document.getElementById('departure-time');
    const depStatus = document.getElementById('departure-status');
    const depIdInput = document.getElementById('tour_departure_id');
    const participantsEmpty = document.getElementById('participants-empty');
    const participantsArea = document.getElementById('participants-area');
    const adultsList = document.getElementById('adults-list');
    const adultsCountLabel = document.getElementById('adults-count-label');
    const childrenList = document.getElementById('children-list');
    const childrenEmpty = document.getElementById('children-empty');
    const summaryBox = document.getElementById('summary-box');
    const statusSelect = document.getElementById('status');
    const statusHint = document.getElementById('status-hint');
    const custFirst = document.getElementById('customer_first_name');
    const custLast = document.getElementById('customer_last_name');
    const exclusiveToggleArea = document.getElementById('exclusive-toggle-area');
    const singleCatamaranArea = document.getElementById('single-catamaran-area');
    const catamaranSelect = document.getElementById('catamaran_id');
    const blockDayCheck = document.getElementById('block_catamaran_day');
    const blockStartInput = document.getElementById('block_start_date');
    const blockEndInput = document.getElementById('block_end_date');
    const blockStartTime = document.getElementById('block_start_time');
    const blockEndTime = document.getElementById('block_end_time');
    const timeCol = document.getElementById('time-col');
    const startTimeCol = document.getElementById('start-time-col');
    const returnCol = document.getElementById('return-col');
    const endTimeCol = document.getElementById('end-time-col');
    const returnDateInput = document.getElementById('return-date-input');
    const multiCatArea = document.getElementById('multi-catamaran-area');
    const multiCatList = document.getElementById('multi-catamaran-list');
    const multiCatStatus = document.getElementById('multi-catamaran-status');
    const onRequestPriceArea = document.getElementById('onrequest-price-area');
    const totalPriceInput = document.getElementById('total_price');
    const childrenHeader = document.getElementById('children-header');

    let DEFAULT_TIME = '09:00';       // ora di partenza in uso esclusivo (sync col campo)

    let departures = [];       // tutte le partenze del tour
    let byDate = {};           // 'Y-m-d' => [departure,...]
    let currentDeparture = null;
    let adultsCount = 1;
    let adults = [{ first_name: '', last_name: '' }];
    let children = [];         // [{dob, first_name, last_name, price?}]
    let fp = null;             // flatpickr data di partenza
    let fpReturn = null;       // flatpickr data di ritorno (uso esclusivo)
    let onRequest = false;     // tour "su richiesta": prezzo totale inserito a mano
    let totalPrice = 0;        // prezzo totale manuale (solo su richiesta)
    let exclusive = false;     // uso esclusivo: partenza+ritorno con date libere
    let tourMeta = null;       // dati tour dalla risposta JSON (capienza, ecc.)
    let exclusiveCats = [];    // disponibilità catamarani nel periodo (uso esclusivo)
    let selectedCats = [];     // id catamarani selezionati (uso esclusivo)
    let availReqSeq = 0;       // sequence per ignorare risposte availability obsolete

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function eur(n) { return '€ ' + (Number(n) || 0).toFixed(2).replace('.', ','); }

    function resetDeparture() {
        currentDeparture = null;
        depIdInput.value = '';
        timeSelect.innerHTML = '<option value="">—</option>';
        timeSelect.disabled = true;
    }

    function fetchTour(tourId) {
        resetDeparture();
        departures = []; byDate = {};
        onRequest = false; tourMeta = null;
        exclusiveCats = []; selectedCats = [];
        exclusiveToggleArea.style.display = 'none';
        singleCatamaranArea.style.display = 'none';
        multiCatArea.style.display = 'none';
        if (fp) { fp.destroy(); fp = null; }
        if (fpReturn) { fpReturn.destroy(); fpReturn = null; }
        dateInput.value = '';
        returnDateInput.value = '';
        dateInput.disabled = true;
        participantsArea.classList.add('d-none');
        participantsEmpty.classList.remove('d-none');
        renderSummary();

        if (!tourId) {
            dateInput.placeholder = 'Seleziona un tour…';
            depStatus.innerHTML = '';
            return;
        }

        depStatus.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Caricamento partenze…</span>';

        fetch(departuresUrlTpl.replace('__TOUR__', tourId), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                tourMeta = data.tour || null;
                onRequest = !!(tourMeta && tourMeta.on_request);
                departures = data.departures || [];
                byDate = {};
                departures.forEach(d => { (byDate[d.iso_date] = byDate[d.iso_date] || []).push(d); });

                depStatus.innerHTML = '';
                // Mostra l'opzione "uso esclusivo" appena il tour è caricato.
                exclusiveToggleArea.style.display = '';
                buildDatePickers();   // costruisce i picker in base alla modalità
                renderCatamarans();
            })
            .catch(err => {
                console.error(err);
                depStatus.innerHTML = '<span class="text-danger">Errore nel caricamento delle partenze.</span>';
            });
    }

    // (Ri)costruisce i datepicker secondo la modalità (normale vs uso esclusivo).
    function buildDatePickers() {
        if (fp) { fp.destroy(); fp = null; }
        if (fpReturn) { fpReturn.destroy(); fpReturn = null; }
        dateInput.value = '';
        returnDateInput.value = '';

        const fpBase = {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            disableMobile: true,
            locale: (flatpickr.l10ns && flatpickr.l10ns.it) ? 'it' : 'default',
        };

        if (exclusive) {
            // Uso esclusivo: date LIBERE per partenza e ritorno.
            dateInput.disabled = false;
            dateInput.placeholder = 'Data di partenza…';
            fp = flatpickr(dateInput, { ...fpBase, onChange: (sel, s) => pickExclusiveStart(s) });
            fpReturn = flatpickr(returnDateInput, { ...fpBase, onChange: (sel, s) => pickExclusiveEnd(s) });
            return;
        }

        // Modalità normale: solo le date prenotabili a calendario.
        if (departures.length === 0) {
            depStatus.innerHTML = '<span class="text-warning">Nessuna partenza per questo tour.</span>';
            dateInput.placeholder = 'Nessuna partenza';
            dateInput.disabled = true;
            return;
        }
        dateInput.disabled = false;
        dateInput.placeholder = 'Seleziona una data…';
        fp = flatpickr(dateInput, {
            ...fpBase,
            enable: Object.keys(byDate),
            onChange: (sel, dateStr) => pickDate(dateStr),
        });
    }

    function pickDate(dateStr) {
        resetDeparture();
        const list = byDate[dateStr] || [];
        if (list.length === 0) { onDepartureChanged(); return; }

        timeSelect.disabled = false;
        timeSelect.innerHTML = list.map(d => {
            const past = d.is_past ? ' (passata)' : '';
            const avail = (d.available != null && d.capacity != null) ? ` · ${d.available}/${d.capacity} disp.` : '';
            return `<option value="${d.id}">${d.time}${avail}${past}</option>`;
        }).join('');

        // Seleziona automaticamente se c'è un solo orario
        if (list.length === 1) {
            timeSelect.value = list[0].id;
        }
        onTimeChanged();
    }

    function onTimeChanged() {
        const depId = timeSelect.value;
        currentDeparture = departures.find(d => String(d.id) === String(depId)) || null;
        depIdInput.value = currentDeparture ? currentDeparture.id : '';
        onDepartureChanged();
    }

    // ===== Uso esclusivo: partenza/ritorno con date libere =====
    function buildExclusiveDeparture(startDate) {
        // Partenza "virtuale" su data libera + orario di default. Brackets dal tour
        // su richiesta non esistono: i partecipanti contano solo i posti.
        const cap = tourMeta ? (tourMeta.total_capacity || 0) : 0;
        return {
            id: 'virt:' + startDate + ':' + DEFAULT_TIME,
            iso_date: startDate,
            time: DEFAULT_TIME,
            available: null,
            capacity: cap,
            adult_price: null,
            is_past: false,
            status: 'scheduled',
            brackets: [],
            // Catamarani dal tour (data libera ⇒ nessun "free" calcolato).
            catamarans: (tourMeta && tourMeta.catamarans) ? tourMeta.catamarans.map(c => ({
                id: c.id, name: c.name, capacity: c.capacity, free: null,
            })) : [],
        };
    }

    function pickExclusiveStart(startDate) {
        if (!startDate) { currentDeparture = null; depIdInput.value = ''; onDepartureChanged(); return; }
        currentDeparture = buildExclusiveDeparture(startDate);
        depIdInput.value = currentDeparture.id;
        blockStartInput.value = startDate;
        // Il ritorno non può precedere la partenza; default = stessa data.
        if (fpReturn) { fpReturn.set('minDate', startDate); }
        if (!returnDateInput.value || returnDateInput.value < startDate) {
            if (fpReturn) fpReturn.setDate(startDate, true); else returnDateInput.value = startDate;
            blockEndInput.value = startDate;
        }
        onDepartureChanged();
        fetchCatamaranAvailability();
    }

    function pickExclusiveEnd(endDate) {
        if (!endDate) { blockEndInput.value = blockStartInput.value || ''; return; }
        blockEndInput.value = endDate;
        renderSummary();
        fetchCatamaranAvailability();
    }

    // Sincronizza l'orario di partenza scelto nella partenza virtuale.
    function syncExclusiveStartTime() {
        DEFAULT_TIME = (blockStartTime && blockStartTime.value) ? blockStartTime.value : '09:00';
        if (exclusive && blockStartInput.value) {
            currentDeparture = buildExclusiveDeparture(blockStartInput.value);
            depIdInput.value = currentDeparture.id;
        }
    }

    // ===== Catamarani disponibili nel periodo (uso esclusivo) =====
    function fetchCatamaranAvailability() {
        if (!exclusive || !tourMeta) return;
        const start = blockStartInput.value;
        const end = blockEndInput.value || start;
        if (!start) {
            exclusiveCats = [];
            renderMultiCatamarans();
            return;
        }
        const seq = ++availReqSeq;
        multiCatStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifica disponibilità…';
        // Includi la fascia oraria: due slot disgiunti nello stesso giorno non collidono.
        const st = blockStartTime && blockStartTime.value ? blockStartTime.value : '';
        const et = blockEndTime && blockEndTime.value ? blockEndTime.value : '';
        const url = catAvailUrlTpl.replace('__TOUR__', tourMeta.id)
            + '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end)
            + (st ? '&start_time=' + encodeURIComponent(st) : '')
            + (et ? '&end_time=' + encodeURIComponent(et) : '');
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                if (seq !== availReqSeq) return; // risposta obsoleta
                exclusiveCats = data.catamarans || [];
                // Mantieni selezionati solo quelli ancora disponibili.
                selectedCats = selectedCats.filter(id =>
                    exclusiveCats.some(c => c.id === id && c.available));
                renderMultiCatamarans();
                renderSummary();
            })
            .catch(() => {
                if (seq !== availReqSeq) return;
                multiCatStatus.innerHTML = '<span class="text-danger">Errore nel controllo disponibilità.</span>';
            });
    }

    function renderMultiCatamarans() {
        if (!exclusiveCats.length) {
            multiCatStatus.textContent = blockStartInput.value
                ? 'Nessun catamarano per questo tour.'
                : 'Seleziona data di partenza e ritorno per vedere i catamarani disponibili.';
            multiCatList.innerHTML = '';
            return;
        }
        const availableCount = exclusiveCats.filter(c => c.available).length;
        multiCatStatus.textContent = availableCount > 0
            ? 'Seleziona uno o più catamarani da riservare nel periodo.'
            : 'Nessun catamarano disponibile nel periodo: ci sono prenotazioni attive (vedi sotto).';

        multiCatList.innerHTML = exclusiveCats.map(c => {
            const checked = selectedCats.includes(c.id) ? 'checked' : '';
            const disabled = c.available ? '' : 'disabled';
            const conflictHtml = (!c.available && c.conflicts && c.conflicts.length)
                ? `<div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Bloccato da: `
                    + c.conflicts.map(cf => `#${escapeHtml(cf.booking_number)} (${escapeHtml(cf.date)} · ${escapeHtml(cf.customer)})`).join(', ')
                    + `. Annulla o sposta queste prenotazioni per liberarlo.</div>`
                : '';
            return `
                <div class="ab-participant-row ${c.available ? '' : 'opacity-75'}">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="catamaran_ids[]" value="${c.id}"
                               id="cat-${c.id}" ${checked} ${disabled} data-cat="${c.id}">
                        <label class="form-check-label fw-semibold" for="cat-${c.id}">
                            ${escapeHtml(c.name)} <span class="text-muted small">· ${c.capacity} posti</span>
                            ${c.available ? '<span class="badge bg-success-subtle text-success ms-1">disponibile</span>' : '<span class="badge bg-danger-subtle text-danger ms-1">occupato</span>'}
                        </label>
                        ${conflictHtml}
                    </div>
                </div>`;
        }).join('');
    }

    // Capienza totale dei catamarani selezionati (uso esclusivo).
    function selectedCatsCapacity() {
        return exclusiveCats
            .filter(c => selectedCats.includes(c.id))
            .reduce((sum, c) => sum + (c.capacity || 0), 0);
    }

    function onDepartureChanged() {
        if (!currentDeparture) {
            participantsArea.classList.add('d-none');
            participantsEmpty.classList.remove('d-none');
            renderCatamarans();
            renderSummary();
            return;
        }
        participantsEmpty.classList.add('d-none');
        participantsArea.classList.remove('d-none');
        // Tour su richiesta: mostra i prezzi manuali e abilita sempre i bambini.
        onRequestPriceArea.style.display = onRequest ? '' : 'none';
        childrenHeader.querySelector('.text-muted').textContent =
            onRequest ? '(contano solo come posti)' : '(con riduzione per età)';
        renderCatamarans();
        renderAdults();
        renderChildren();
        renderSummary();

        // Modalità normale: avvisa se la data non ha catamarani disponibili
        // (es. tutti riservati/bloccati). Il backend rifiuterebbe comunque.
        if (!exclusive) {
            const noAvail = currentDeparture.available != null && currentDeparture.available <= 0;
            depStatus.innerHTML = noAvail
                ? '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Nessun catamarano disponibile in questa data (tutti riservati/occupati).</span>'
                : '';
        }
    }

    // ===== Catamarano =====
    function renderCatamarans() {
        if (!tourMeta) {
            singleCatamaranArea.style.display = 'none';
            multiCatArea.style.display = 'none';
            return;
        }

        if (exclusive) {
            // Modalità uso esclusivo: lista multi-selezione (popolata da fetchCatamaranAvailability).
            singleCatamaranArea.style.display = 'none';
            multiCatArea.style.display = '';
            renderMultiCatamarans();
            return;
        }

        // Modalità normale: select singolo (automatico o un catamarano).
        multiCatArea.style.display = '';  // override sotto
        multiCatArea.style.display = 'none';
        singleCatamaranArea.style.display = currentDeparture ? '' : 'none';
        if (!currentDeparture) return;

        const cats = currentDeparture.catamarans || [];
        const prev = catamaranSelect.value;
        catamaranSelect.innerHTML =
            '<option value="">Automatico (assegna il sistema)</option>' +
            cats.map(c => {
                const seats = (c.free != null) ? `${c.free} liberi` : `${c.capacity} posti`;
                return `<option value="${c.id}">${escapeHtml(c.name)} · ${seats}</option>`;
            }).join('');
        if (prev && cats.some(c => String(c.id) === String(prev))) {
            catamaranSelect.value = prev;
        }
    }

    // ===== Adulti =====
    function renderAdults() {
        adultsCountLabel.textContent = adultsCount;
        // sincronizza array
        const next = [];
        for (let i = 0; i < adultsCount; i++) {
            next.push(adults[i] || { first_name: '', last_name: '' });
        }
        // primo adulto = intestatario
        next[0] = { first_name: custFirst.value || '', last_name: custLast.value || '' };
        adults = next;

        adultsList.innerHTML = adults.map((a, i) => `
            <div class="ab-participant-row">
                <div class="ab-row-head mb-2">Adulto ${i + 1}${i === 0 ? ' · intestatario' : ''}</div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" placeholder="Nome"
                            name="adults[${i}][first_name]" value="${escapeHtml(a.first_name)}" ${i === 0 ? 'readonly' : ''} data-adult="${i}" data-field="first_name">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" placeholder="Cognome"
                            name="adults[${i}][last_name]" value="${escapeHtml(a.last_name)}" ${i === 0 ? 'readonly' : ''} data-adult="${i}" data-field="last_name">
                    </div>
                </div>
                ${i === 0 ? '<div class="form-text">Compilato dai dati cliente qui sotto.</div>' : ''}
            </div>
        `).join('');
    }

    // ===== Bambini =====
    function ageAt(dob, depDate) {
        const b = new Date(dob), d = new Date(depDate);
        if (isNaN(b) || isNaN(d)) return null;
        let age = d.getFullYear() - b.getFullYear();
        const m = d.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && d.getDate() < b.getDate())) age--;
        return age;
    }

    function resolveBracket(dob) {
        if (!currentDeparture) return { error: 'Seleziona una partenza.' };
        const brackets = currentDeparture.brackets || [];
        if (!dob) return { error: null };
        const age = ageAt(dob, currentDeparture.iso_date);
        if (age == null) return { error: 'Data non valida.' };
        if (new Date(dob) > new Date(currentDeparture.iso_date)) return { error: 'Nascita successiva alla partenza.' };
        const b = brackets.find(x => age >= x.min_age && (x.max_age == null || age <= x.max_age));
        if (!b) return { age, error: `Nessuna riduzione per ${age} anni: inseriscilo come adulto.` };
        return { age, bracket: b };
    }

    function childBadgeHtml(c) {
        // Tour su richiesta: nessuna fascia/prezzo per bambino (conta solo come posto).
        if (onRequest) return '';
        if (!c.dob) return '';
        const res = resolveBracket(c.dob);
        return res.bracket
            ? `<div class="ab-child-resolved ok mt-1"><i class="bi bi-check-circle me-1"></i>${escapeHtml(res.bracket.label)} (${res.age} anni) · ${eur(res.bracket.price)}</div>`
            : `<div class="ab-child-resolved err mt-1"><i class="bi bi-exclamation-triangle me-1"></i>${escapeHtml(res.error || '')}</div>`;
    }

    // Aggiorna SOLO il badge della riga (senza ridisegnare gli input): così
    // il campo data non viene ricreato a ogni cifra digitata e mantiene il focus.
    function updateChildBadge(i) {
        const slot = childrenList.querySelector(`[data-child-badge="${i}"]`);
        if (slot) slot.innerHTML = childBadgeHtml(children[i]);
    }

    function renderChildren() {
        // Su richiesta i bambini sono sempre aggiungibili (nessun bracket richiesto).
        const hasBrackets = currentDeparture && (currentDeparture.brackets || []).length > 0;
        document.getElementById('add-child').disabled = !onRequest && !hasBrackets;
        childrenEmpty.classList.toggle('d-none', children.length > 0);

        childrenList.innerHTML = children.map((c, i) => {
            return `
            <div class="ab-participant-row">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="ab-row-head">Bambino ${i + 1}</div>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" data-remove-child="${i}"><i class="bi bi-trash"></i></button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="date" class="form-control form-control-sm" name="children[${i}][dob]" value="${escapeHtml(c.dob)}" max="${currentDeparture ? currentDeparture.iso_date : ''}" data-child="${i}" data-field="dob">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Nome" name="children[${i}][first_name]" value="${escapeHtml(c.first_name)}" data-child="${i}" data-field="first_name">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Cognome" name="children[${i}][last_name]" value="${escapeHtml(c.last_name)}" data-child="${i}" data-field="last_name">
                    </div>
                </div>
                <div data-child-badge="${i}">${childBadgeHtml(c)}</div>
            </div>`;
        }).join('');
    }

    // ===== Riepilogo =====
    function renderSummary() {
        if (!currentDeparture) {
            summaryBox.innerHTML = '<div class="text-muted small text-center py-3">Seleziona tour e partenza.</div>';
            return;
        }
        const lines = [];
        let total = 0, seats = 0, pax = 0;

        if (onRequest) {
            // Su richiesta: il totale è il prezzo unico inserito; adulti+bambini = posti.
            const childCount = children.length;
            seats = adultsCount + childCount;
            pax = seats;
            total = Number(totalPrice) || 0;
            if (adultsCount > 0) lines.push(`<div class="d-flex justify-content-between small mb-1"><span>Adulti × ${adultsCount}</span><span class="text-muted">posti</span></div>`);
            if (childCount > 0) lines.push(`<div class="d-flex justify-content-between small mb-1"><span>Bambini × ${childCount}</span><span class="text-muted">posti</span></div>`);
            lines.push(`<div class="d-flex justify-content-between small mb-1"><span>Prezzo totale (catamarano riservato)</span><span>${eur(total)}</span></div>`);
        } else {
            const unitAdult = currentDeparture.adult_price || 0;
            if (adultsCount > 0) {
                const sub = adultsCount * unitAdult;
                total += sub; seats += adultsCount; pax += adultsCount;
                lines.push(`<div class="d-flex justify-content-between small mb-1"><span>Adulti × ${adultsCount}</span><span>${eur(sub)}</span></div>`);
            }
            children.forEach((c) => {
                const res = resolveBracket(c.dob);
                if (!res.bracket) { if (c.dob) pax += 1; return; }
                total += res.bracket.price; pax += 1;
                if (res.bracket.counts_as_seat) seats += 1;
                lines.push(`<div class="d-flex justify-content-between small mb-1"><span>${escapeHtml(res.bracket.label)}</span><span>${eur(res.bracket.price)}</span></div>`);
            });
        }

        summaryBox.innerHTML = `
            ${lines.join('')}
            <hr class="my-2">
            <div class="d-flex justify-content-between small text-muted"><span>Partecipanti</span><span>${pax}</span></div>
            <div class="d-flex justify-content-between small text-muted"><span>Posti occupati</span><span>${seats}</span></div>
            <div class="d-flex justify-content-between fw-bold fs-5 mt-2 pt-2 border-top"><span>Totale</span><span>${eur(total)}</span></div>
            ${currentDeparture.is_past ? '<div class="alert alert-warning py-2 px-2 mt-2 mb-0 small"><i class="bi bi-clock-history me-1"></i>Partenza passata: prenotazione retroattiva.</div>' : ''}
        `;
    }

    function updateStatusHint() {
        if (!statusHint) return; // hint rimosso dalla UI: nessuna azione
        const v = statusSelect.value;
        if (v === 'pending') statusHint.textContent = 'Verrà inviata al cliente l\'email con il link di pagamento Stripe.';
        else if (v === 'confirmed') statusHint.textContent = 'Pagamento già incassato: verranno inviati i biglietti al cliente.';
        else statusHint.textContent = 'Nessuna email automatica al cliente per questo stato.';
    }

    // ===== Eventi =====
    tourSelect.addEventListener('change', e => fetchTour(e.target.value));
    timeSelect.addEventListener('change', onTimeChanged);
    statusSelect.addEventListener('change', updateStatusHint);

    // Uso esclusivo: i campi data/orario diventano data di partenza + data di ritorno.
    blockDayCheck.addEventListener('change', () => setExclusiveMode(blockDayCheck.checked));

    function setExclusiveMode(on) {
        exclusive = on;
        resetDeparture();
        // Colonne: normale = orario; esclusivo = ora partenza + data/ora ritorno.
        timeCol.style.display = exclusive ? 'none' : '';
        startTimeCol.style.display = exclusive ? '' : 'none';
        returnCol.style.display = exclusive ? '' : 'none';
        endTimeCol.style.display = exclusive ? '' : 'none';
        // Reset periodo e selezioni.
        blockStartInput.value = '';
        blockEndInput.value = '';
        exclusiveCats = []; selectedCats = [];
        if (exclusive) syncExclusiveStartTime();
        // Ricostruisci i picker e l'area partecipanti/catamarani.
        if (tourMeta) buildDatePickers();
        onDepartureChanged();
    }

    // Aggiorna l'ora di partenza/virtuale e ricontrolla la disponibilità nella fascia.
    blockStartTime.addEventListener('change', () => { syncExclusiveStartTime(); renderSummary(); fetchCatamaranAvailability(); });
    blockEndTime.addEventListener('change', () => { fetchCatamaranAvailability(); });

    // Selezione catamarani (uso esclusivo): aggiorna lista e capienza.
    multiCatList.addEventListener('change', e => {
        const cb = e.target.closest('input[data-cat]');
        if (!cb) return;
        const id = parseInt(cb.value, 10);
        if (cb.checked) {
            if (!selectedCats.includes(id)) selectedCats.push(id);
        } else {
            selectedCats = selectedCats.filter(x => x !== id);
        }
        renderSummary();
    });

    document.getElementById('adults-plus').addEventListener('click', () => { adultsCount++; renderAdults(); renderSummary(); });
    document.getElementById('adults-minus').addEventListener('click', () => { adultsCount = Math.max(1, adultsCount - 1); renderAdults(); renderSummary(); });
    document.getElementById('add-child').addEventListener('click', () => { children.push({ dob: '', first_name: '', last_name: '' }); renderChildren(); renderSummary(); });

    // Input partecipanti (delegato)
    document.addEventListener('input', e => {
        const t = e.target;
        if (t.dataset.adult != null) {
            adults[+t.dataset.adult][t.dataset.field] = t.value;
        } else if (t.dataset.child != null) {
            const i = +t.dataset.child;
            children[i][t.dataset.field] = t.value;
            if (t.dataset.field === 'dob') {
                // Aggiorna solo il badge della riga (non ricreare l'input data,
                // altrimenti si perde il focus mentre si digita l'anno) + riepilogo.
                updateChildBadge(i);
                renderSummary();
            }
        }
    });

    // Prezzo totale manuale (tour su richiesta)
    totalPriceInput.addEventListener('input', e => { totalPrice = e.target.value; renderSummary(); });
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-remove-child]');
        if (btn) { children.splice(+btn.dataset.removeChild, 1); renderChildren(); renderSummary(); }
    });

    // Intestatario → primo adulto
    [custFirst, custLast].forEach(el => el.addEventListener('input', () => { if (!participantsArea.classList.contains('d-none')) renderAdults(); renderSummary(); }));

    // Guardia submit: in uso esclusivo serve almeno un catamarano disponibile
    // selezionato e la capienza scelta deve coprire i passeggeri.
    document.getElementById('adminBookingForm').addEventListener('submit', e => {
        if (!exclusive) {
            // Modalità normale: blocca se la data non ha posti disponibili.
            if (currentDeparture && currentDeparture.available != null && currentDeparture.available <= 0) {
                e.preventDefault();
                alert('Questa data non ha catamarani disponibili (tutti riservati/occupati). Scegli un\'altra data.');
            }
            return;
        }
        if (selectedCats.length === 0) {
            e.preventDefault();
            alert('Seleziona almeno un catamarano disponibile da riservare.');
            return;
        }
        const totalPax = adultsCount + children.length;
        const cap = selectedCatsCapacity();
        if (cap < totalPax) {
            e.preventDefault();
            alert('I catamarani selezionati hanno ' + cap + ' posti, ma i passeggeri sono ' + totalPax
                + '. Seleziona altri catamarani per coprire tutti i passeggeri.');
        }
    });

    updateStatusHint();
})();
</script>
@endpush
