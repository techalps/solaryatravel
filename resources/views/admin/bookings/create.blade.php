@extends('layouts.admin')

@section('title', 'Nuova prenotazione')

@section('content')
    <div class="dash-page-header">
        <div>
            <h1>Nuova prenotazione</h1>
            <p>Crea manualmente una prenotazione (telefono, walk-in, agenzia).</p>
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
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label for="tour_departure_id" class="form-label fw-semibold">Partenza *</label>
                                <select name="tour_departure_id" id="tour_departure_id" class="form-select" required {{ $departures->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">— Seleziona partenza —</option>
                                    @foreach($departures as $dep)
                                        <option value="{{ $dep->id }}"
                                            data-available="{{ $dep->seats_available }}"
                                            {{ old('tour_departure_id') == $dep->id ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($dep->departure_date)->format('d/m/Y') }}
                                            · {{ \Carbon\Carbon::parse($dep->start_time)->format('H:i') }}
                                            · {{ $dep->seats_available }}/{{ $dep->capacity }} disp.
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="departure-status">
                                    @if($selectedTour && $departures->isEmpty())
                                        <span class="text-warning">Nessuna partenza futura programmata per questo tour.</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Partecipanti --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-people me-2 text-primary"></i>2. Partecipanti</h3>
                    </div>
                    <div class="dash-card-body" id="brackets-container">
                        @if($selectedTour && $selectedTour->ageBrackets->count())
                            @foreach($selectedTour->ageBrackets as $bracket)
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold">{{ $bracket->label }}</div>
                                        <div class="small text-muted">
                                            {{ $bracket->range_label }} ·
                                            € {{ number_format($bracket->price, 2, ',', '.') }}
                                            @if(!$bracket->counts_as_seat)
                                                <span class="badge text-bg-light border ms-1">non occupa posto</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="width: 110px;">
                                        <input type="number" name="bracket_counts[{{ $bracket->id }}]"
                                            value="{{ old('bracket_counts.' . $bracket->id, 0) }}"
                                            min="0" max="50" class="form-control text-center" data-bracket-input>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-muted text-center py-3">
                                <i class="bi bi-arrow-up me-1"></i>Seleziona prima un tour per vedere le fasce d'età.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 3. Cliente --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-person-badge me-2 text-primary"></i>3. Cliente</h3>
                    </div>
                    <div class="dash-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nome *</label>
                                <input type="text" name="customer_first_name" value="{{ old('customer_first_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cognome *</label>
                                <input type="text" name="customer_last_name" value="{{ old('customer_last_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email *</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="form-control" required>
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

                        <div class="form-check mb-2">
                            <input type="hidden" name="auto_confirm" value="0">
                            <input class="form-check-input" type="checkbox" name="auto_confirm" value="1"
                                id="auto_confirm" {{ old('auto_confirm') ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_confirm">
                                Conferma immediatamente
                                <span class="d-block small text-muted">Salta lo stato "in attesa". Usa per pagamenti già ricevuti (contanti / bonifico / agenzia).</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold mt-3">
                            <i class="bi bi-check2-circle me-1"></i>Crea prenotazione
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    // URL relativo (senza host): la fetch deve sempre puntare all'host corrente,
    // altrimenti se APP_URL non coincide con il dominio di navigazione la richiesta
    // diventa cross-origin e fallisce ("Errore nel caricamento").
    const departuresUrlTpl = @json(route('admin.bookings.departures.json', ['tour' => '__TOUR__'], false));
    const tourSelect = document.getElementById('tour_id');
    const depSelect = document.getElementById('tour_departure_id');
    const depStatus = document.getElementById('departure-status');
    const bracketsContainer = document.getElementById('brackets-container');
    const summaryBox = document.getElementById('summary-box');

    let currentBrackets = []; // [{id, label, price, counts_as_seat}]
    let currentDepartures = [];

    function fetchTourData(tourId, preselectDeparture = null) {
        if (!tourId) {
            depSelect.innerHTML = '<option value="">— Seleziona partenza —</option>';
            depSelect.disabled = true;
            bracketsContainer.innerHTML = '<div class="text-muted text-center py-3"><i class="bi bi-arrow-up me-1"></i>Seleziona prima un tour per vedere le fasce d\'età.</div>';
            depStatus.innerHTML = '';
            currentBrackets = [];
            currentDepartures = [];
            renderSummary();
            return;
        }
        const url = departuresUrlTpl.replace('__TOUR__', tourId);
        depSelect.disabled = true;
        depStatus.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Caricamento partenze…</span>';

        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                currentDepartures = data.departures;
                currentBrackets = data.brackets;

                // Departures
                if (data.departures.length === 0) {
                    depSelect.innerHTML = '<option value="">— Nessuna partenza disponibile —</option>';
                    depSelect.disabled = true;
                    depStatus.innerHTML = '<span class="text-warning">Nessuna partenza futura programmata per questo tour.</span>';
                } else {
                    depSelect.innerHTML = '<option value="">— Seleziona partenza —</option>' +
                        data.departures.map(d =>
                            `<option value="${d.id}" data-available="${d.available}">${d.date} · ${d.time} · ${d.available}/${d.capacity} disp.</option>`
                        ).join('');
                    depSelect.disabled = false;
                    depStatus.innerHTML = '';
                    if (preselectDeparture) depSelect.value = preselectDeparture;
                }

                // Brackets
                if (data.brackets.length === 0) {
                    bracketsContainer.innerHTML = '<div class="text-warning text-center py-3">Nessuna fascia d\'età configurata per questo tour.</div>';
                } else {
                    bracketsContainer.innerHTML = data.brackets.map(b => `
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold">${escapeHtml(b.label)}</div>
                                <div class="small text-muted">
                                    ${escapeHtml(b.range_label)} · € ${b.price.toFixed(2).replace('.', ',')}
                                    ${b.counts_as_seat ? '' : '<span class="badge text-bg-light border ms-1">non occupa posto</span>'}
                                </div>
                            </div>
                            <div style="width: 110px;">
                                <input type="number" name="bracket_counts[${b.id}]" value="0" min="0" max="50" class="form-control text-center" data-bracket-input>
                            </div>
                        </div>
                    `).join('');
                }
                renderSummary();
            })
            .catch((err) => {
                console.error('Caricamento partenze fallito:', err);
                depStatus.innerHTML = '<span class="text-danger">Errore nel caricamento delle partenze. Ricarica la pagina e riprova.</span>';
            });
    }

    function renderSummary() {
        const inputs = document.querySelectorAll('[data-bracket-input]');
        let totalPax = 0, totalSeats = 0, totalPrice = 0;
        const lines = [];

        inputs.forEach(input => {
            const qty = parseInt(input.value, 10) || 0;
            if (qty <= 0) return;
            const m = input.name.match(/bracket_counts\[(\d+)\]/);
            if (!m) return;
            const id = parseInt(m[1], 10);
            const b = currentBrackets.find(x => x.id === id);
            if (!b) return;
            const sub = qty * b.price;
            totalPax += qty;
            if (b.counts_as_seat) totalSeats += qty;
            totalPrice += sub;
            lines.push(`<div class="d-flex justify-content-between small mb-1">
                <span>${escapeHtml(b.label)} × ${qty}</span>
                <span>€ ${sub.toFixed(2).replace('.', ',')}</span>
            </div>`);
        });

        if (totalPax === 0) {
            summaryBox.innerHTML = '<div class="text-muted small text-center py-3">Indica i partecipanti per vedere il totale.</div>';
            return;
        }

        // Apply price modifier from selected departure
        const depOpt = depSelect.options[depSelect.selectedIndex];
        const dep = depOpt && depOpt.value ? currentDepartures.find(x => x.id == depOpt.value) : null;
        const modifier = dep ? (dep.price_modifier || 1) : 1;
        const adjusted = totalPrice * modifier;

        summaryBox.innerHTML = `
            ${lines.join('')}
            <hr class="my-2">
            <div class="d-flex justify-content-between small text-muted">
                <span>Partecipanti</span><span>${totalPax}</span>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Posti occupati</span><span>${totalSeats}</span>
            </div>
            ${modifier !== 1 ? `<div class="d-flex justify-content-between small text-muted"><span>Mod. prezzo</span><span>×${modifier}</span></div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-5 mt-2 pt-2 border-top">
                <span>Totale</span><span>€ ${adjusted.toFixed(2).replace('.', ',')}</span>
            </div>
        `;
    }

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Hydrate currentBrackets / currentDepartures from server-side render if present
    @if($selectedTour)
        @php
            $hydratedBrackets = $selectedTour->ageBrackets->map(fn($b) => [
                'id' => $b->id,
                'label' => $b->label,
                'price' => (float) $b->price,
                'counts_as_seat' => (bool) $b->counts_as_seat,
                'range_label' => $b->range_label,
            ])->values();
            $hydratedDepartures = $departures->map(fn($d) => [
                'id' => $d->id,
                'available' => $d->seats_available,
                'capacity' => $d->capacity,
                'price_modifier' => (float) $d->price_modifier,
            ])->values();
        @endphp
        currentBrackets = {!! $hydratedBrackets->toJson() !!};
        currentDepartures = {!! $hydratedDepartures->toJson() !!};
    @endif

    tourSelect.addEventListener('change', e => fetchTourData(e.target.value));
    depSelect.addEventListener('change', renderSummary);
    document.addEventListener('input', e => {
        if (e.target.matches('[data-bracket-input]')) renderSummary();
    });

    // Initial summary if we already have values
    renderSummary();
})();
</script>
@endpush
