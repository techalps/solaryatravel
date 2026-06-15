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
                        <div class="row g-3">
                            <div class="col-md-5">
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
                            <div class="col-md-4">
                                <label for="booking-date-input" class="form-label fw-semibold">Data di partenza *</label>
                                <input type="text" id="booking-date-input" class="form-control" placeholder="Seleziona un tour…" autocomplete="off" disabled>
                                <div class="form-text" id="departure-status"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="departure-time" class="form-label fw-semibold">Orario *</label>
                                <select id="departure-time" class="form-select" disabled>
                                    <option value="">—</option>
                                </select>
                            </div>
                        </div>
                        {{-- Partenza effettivamente scelta (id reale di tour_departures) --}}
                        <input type="hidden" name="tour_departure_id" id="tour_departure_id" value="{{ old('tour_departure_id') }}">
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

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Stato della prenotazione *</label>
                            <select name="status" id="status" class="form-select" required>
                                @foreach($statuses as $st)
                                    <option value="{{ $st->value }}" {{ old('status', 'pending') === $st->value ? 'selected' : '' }}>
                                        {{ $st->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text" id="status-hint"></div>
                        </div>

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

    let departures = [];       // tutte le partenze del tour
    let byDate = {};           // 'Y-m-d' => [departure,...]
    let currentDeparture = null;
    let adultsCount = 1;
    let adults = [{ first_name: '', last_name: '' }];
    let children = [];         // [{dob, first_name, last_name}]
    let fp = null;

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
        if (fp) { fp.destroy(); fp = null; }
        dateInput.value = '';
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
                departures = data.departures || [];
                byDate = {};
                departures.forEach(d => { (byDate[d.iso_date] = byDate[d.iso_date] || []).push(d); });

                if (departures.length === 0) {
                    depStatus.innerHTML = '<span class="text-warning">Nessuna partenza per questo tour.</span>';
                    dateInput.placeholder = 'Nessuna partenza';
                    return;
                }

                const enableDates = Object.keys(byDate);
                dateInput.disabled = false;
                dateInput.placeholder = 'Seleziona una data…';
                depStatus.innerHTML = '';

                fp = flatpickr(dateInput, {
                    enable: enableDates,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    disableMobile: true,
                    locale: (flatpickr.l10ns && flatpickr.l10ns.it) ? 'it' : 'default',
                    onChange: (sel, dateStr) => pickDate(dateStr),
                });
            })
            .catch(err => {
                console.error(err);
                depStatus.innerHTML = '<span class="text-danger">Errore nel caricamento delle partenze.</span>';
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

    function onDepartureChanged() {
        if (!currentDeparture) {
            participantsArea.classList.add('d-none');
            participantsEmpty.classList.remove('d-none');
            renderSummary();
            return;
        }
        participantsEmpty.classList.add('d-none');
        participantsArea.classList.remove('d-none');
        renderAdults();
        renderChildren();
        renderSummary();
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
        const hasBrackets = currentDeparture && (currentDeparture.brackets || []).length > 0;
        document.getElementById('add-child').disabled = !hasBrackets;
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
        const adultPrice = currentDeparture.adult_price || 0;
        const lines = [];
        let total = 0, seats = 0, pax = 0;

        if (adultsCount > 0) {
            const sub = adultsCount * adultPrice;
            total += sub; seats += adultsCount; pax += adultsCount;
            lines.push(`<div class="d-flex justify-content-between small mb-1"><span>Adulti × ${adultsCount}</span><span>${eur(sub)}</span></div>`);
        }
        children.forEach(c => {
            const res = resolveBracket(c.dob);
            if (!res.bracket) { if (c.dob) pax += 1; return; }
            total += res.bracket.price; pax += 1;
            if (res.bracket.counts_as_seat) seats += 1;
            lines.push(`<div class="d-flex justify-content-between small mb-1"><span>${escapeHtml(res.bracket.label)}</span><span>${eur(res.bracket.price)}</span></div>`);
        });

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
        const v = statusSelect.value;
        if (v === 'pending') statusHint.textContent = 'Verrà inviata al cliente l\'email con il link di pagamento Stripe.';
        else if (v === 'confirmed') statusHint.textContent = 'Pagamento già incassato: verranno inviati i biglietti al cliente.';
        else statusHint.textContent = 'Nessuna email automatica al cliente per questo stato.';
    }

    // ===== Eventi =====
    tourSelect.addEventListener('change', e => fetchTour(e.target.value));
    timeSelect.addEventListener('change', onTimeChanged);
    statusSelect.addEventListener('change', updateStatusHint);

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
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-remove-child]');
        if (btn) { children.splice(+btn.dataset.removeChild, 1); renderChildren(); renderSummary(); }
    });

    // Intestatario → primo adulto
    [custFirst, custLast].forEach(el => el.addEventListener('input', () => { if (!participantsArea.classList.contains('d-none')) renderAdults(); renderSummary(); }));

    updateStatusHint();
})();
</script>
@endpush
