@extends('layouts.admin')

@section('title', 'Imbarco · ' . ($departure->tour?->name ?? 'Tour'))

@push('styles')
<style>
    .seat-card { transition: all .2s ease; border: 2px solid transparent; }
    .seat-card.boarded { background: #d1fae5; border-color: #10b981; }
    .seat-card.boarded .seat-name { color: #065f46; text-decoration: line-through; opacity: .7; }
    .seat-card.flash { animation: flashGreen 1s ease; }
    @keyframes flashGreen {
        0% { transform: scale(1); }
        30% { transform: scale(1.04); box-shadow: 0 0 0 6px rgba(16,185,129,.25); }
        100% { transform: scale(1); }
    }
    #qr-reader { width: 100%; max-width: 420px; border-radius: 1rem; overflow: hidden; }
    #qr-reader__dashboard_section_csr button { border-radius: .5rem !important; }
    .scan-feedback { min-height: 60px; }
    .scan-feedback .alert { margin-bottom: 0; }
    .progress-circle {
        width: 96px; height: 96px; position: relative;
        background: conic-gradient(#10b981 var(--p), #e5e7eb 0); border-radius: 50%;
        display: grid; place-items: center;
    }
    .progress-circle::before { content: ''; position: absolute; inset: 8px; background: #fff; border-radius: 50%; }
    .progress-circle span { position: relative; font-weight: 700; font-size: 1.1rem; color: #065f46; }
</style>
@endpush

@section('content')
    <div class="dash-page-header">
        <div>
            <h1>{{ $departure->tour?->name ?? 'Tour non disponibile' }}</h1>
            <p class="mb-0">
                <i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($departure->departure_date)->locale('it')->isoFormat('dddd D MMMM YYYY') }}
                <span class="ms-2"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($departure->start_time)->format('H:i') }}@if($departure->end_time) – {{ \Carbon\Carbon::parse($departure->end_time)->format('H:i') }}@endif</span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.assignments.show', $departure) }}" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-water me-1"></i>Gestisci catamarani
            </a>
            <a href="{{ route('admin.boarding.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Tutte le partenze
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- LEFT: Scanner --}}
        <div class="col-lg-5">
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-qr-code-scan me-2 text-primary"></i>Scansiona biglietto</h3>
                </div>
                <div class="dash-card-body text-center">
                    <div id="qr-reader" class="mx-auto mb-3"></div>
                    <button type="button" id="btn-toggle-camera" class="btn btn-primary rounded-pill px-4 fw-semibold mb-3">
                        <i class="bi bi-camera-video me-1"></i>Avvia scanner
                    </button>

                    <form id="manual-form" class="mb-3">
                        <label class="form-label small text-muted">Inserimento manuale</label>
                        <div class="input-group">
                            <input type="text" id="manual-code" class="form-control" placeholder="SLY-S-XXXXXXXXXX" autocomplete="off">
                            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check2"></i></button>
                        </div>
                    </form>

                    <div id="scan-feedback" class="scan-feedback"></div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-body text-center">
                    <div class="d-flex align-items-center justify-content-around">
                        <div class="progress-circle" id="progress-circle" style="--p: 0%">
                            <span id="progress-text">0/0</span>
                        </div>
                        <div class="text-start">
                            <div class="small text-muted">Imbarcati</div>
                            <div class="h2 fw-bold mb-0"><span id="count-boarded">0</span> <span class="text-muted small">/ <span id="count-total">0</span></span></div>
                            <div class="small text-muted mt-1">Aggiornato <span id="updated-at">—</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Passenger list --}}
        <div class="col-lg-7">
            <div class="dash-card">
                <div class="dash-card-header d-flex justify-content-between align-items-center">
                    <h3><i class="bi bi-people-fill me-2 text-primary"></i>Lista passeggeri</h3>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-filter="all">Tutti</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="pending">Da imbarcare</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="boarded">Imbarcati</button>
                    </div>
                </div>
                <div class="dash-card-body p-0">
                    <div id="seats-container" class="vstack gap-2 p-3" style="max-height:70vh; overflow-y:auto;">
                        <div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div> Caricamento...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const stateUrl = @json(route('admin.boarding.state', $departure));
    const scanUrl  = @json(route('admin.boarding.scan',  $departure));
    const toggleUrlTpl = @json(route('admin.boarding.toggle', [$departure, '__SEAT__']));
    const csrf = @json(csrf_token());

    let currentFilter = 'all';
    let qrScanner = null;
    let scannerActive = false;
    let lastScannedCode = null;
    let lastScannedAt = 0;

    const $container = document.getElementById('seats-container');
    const $feedback = document.getElementById('scan-feedback');

    function fetchState() {
        fetch(stateUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(renderState)
            .catch(() => {});
    }

    function renderState(data) {
        document.getElementById('count-boarded').textContent = data.boarded;
        document.getElementById('count-total').textContent = data.total;
        document.getElementById('progress-text').textContent = data.boarded + '/' + data.total;
        const pct = data.total > 0 ? Math.round((data.boarded / data.total) * 100) : 0;
        document.getElementById('progress-circle').style.setProperty('--p', pct + '%');
        const d = new Date(data.updated_at);
        document.getElementById('updated-at').textContent = d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        if (!data.seats.length) {
            $container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox display-5 d-block mb-2 opacity-50"></i>Nessun passeggero confermato per questa partenza.</div>';
            return;
        }

        const html = data.seats
            .filter(s => currentFilter === 'all' || (currentFilter === 'boarded' ? s.boarded : !s.boarded))
            .map(seatRow).join('');
        $container.innerHTML = html || '<div class="text-center text-muted py-4">Nessun passeggero in questa categoria.</div>';
    }

    function seatRow(s) {
        const cat = s.catamaran ? `<span class="badge text-bg-light border ms-1">${escapeHtml(s.catamaran)}</span>` : '';
        const bracket = s.age_bracket ? `<span class="text-muted small ms-2">${escapeHtml(s.age_bracket)}</span>` : '';
        const time = s.boarded_at ? `<span class="small text-success ms-2"><i class="bi bi-check-circle-fill"></i> ${s.boarded_at}</span>` : '';
        const btn = s.boarded
            ? `<button class="btn btn-sm btn-outline-danger seat-toggle" data-id="${s.id}"><i class="bi bi-arrow-counterclockwise"></i></button>`
            : `<button class="btn btn-sm btn-success seat-toggle" data-id="${s.id}"><i class="bi bi-check-lg"></i> Imbarca</button>`;
        return `
        <div class="seat-card d-flex align-items-center justify-content-between p-3 rounded-3 bg-white border ${s.boarded ? 'boarded' : ''}" data-seat="${s.id}">
            <div class="min-w-0">
                <div class="seat-name fw-semibold">
                    ${s.is_primary ? '<i class="bi bi-star-fill text-warning me-1" title="Capogruppo"></i>' : ''}
                    ${escapeHtml(s.name)}${bracket}
                </div>
                <div class="small text-muted">
                    <span class="font-monospace">#${escapeHtml(s.booking_number)}</span> ${cat} ${time}
                </div>
            </div>
            <div>${btn}</div>
        </div>`;
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showFeedback(type, message) {
        const cls = { success: 'alert-success', error: 'alert-danger', warn: 'alert-warning' }[type] || 'alert-info';
        const icon = { success: 'check-circle-fill', error: 'x-circle-fill', warn: 'exclamation-triangle-fill' }[type] || 'info-circle-fill';
        $feedback.innerHTML = `<div class="alert ${cls} d-flex align-items-center mb-0"><i class="bi bi-${icon} me-2 fs-5"></i><div>${escapeHtml(message)}</div></div>`;
        clearTimeout(showFeedback._t);
        showFeedback._t = setTimeout(() => { $feedback.innerHTML = ''; }, 4000);
    }

    function flashSeat(id) {
        const el = document.querySelector(`[data-seat="${id}"]`);
        if (el) {
            el.classList.add('flash');
            setTimeout(() => el.classList.remove('flash'), 1000);
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function submitScan(code) {
        if (!code) return;
        // debounce: ignora stesso codice se entro 2 sec
        const now = Date.now();
        if (code === lastScannedCode && (now - lastScannedAt) < 2000) return;
        lastScannedCode = code;
        lastScannedAt = now;

        fetch(scanUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ qr_code: code })
        })
        .then(async r => {
            // Parsing difensivo: alcune risposte (proxy/HTTP2, sessione) possono
            // non essere JSON valido pur essendo l'imbarco andato a buon fine.
            let body = null;
            try { body = await r.json(); } catch (e) { body = null; }
            return { ok: r.ok, status: r.status, body };
        })
        .then(({ ok, status, body }) => {
            if (ok && body && body.success) {
                showFeedback('success', `${body.seat.name} imbarcato!`);
                if (navigator.vibrate) navigator.vibrate(150);
                fetchState();
                setTimeout(() => flashSeat(body.seat.id), 200);
            } else if (body && body.code === 'already_boarded') {
                showFeedback('warn', body.message);
                if (navigator.vibrate) navigator.vibrate([60, 60, 60]);
            } else if (body && body.message) {
                showFeedback('error', body.message);
                if (navigator.vibrate) navigator.vibrate([200, 80, 200]);
            } else {
                // Risposta non interpretabile: NON dichiarare un errore secco.
                // Ricarico lo stato dal server: se l'imbarco è avvenuto, l'UI lo riflette.
                fetchState();
                showFeedback('warn', 'Risposta non confermata — verifica lo stato del passeggero.');
            }
        })
        .catch(() => {
            // Errore di rete reale: ricarico comunque lo stato per non lasciare l'UI ferma.
            fetchState();
            showFeedback('warn', 'Connessione instabile — stato aggiornato, riprova se necessario.');
        });
    }

    // ====== Scanner ======
    document.getElementById('btn-toggle-camera').addEventListener('click', function () {
        if (scannerActive) {
            qrScanner.stop().then(() => {
                scannerActive = false;
                this.innerHTML = '<i class="bi bi-camera-video me-1"></i>Avvia scanner';
                this.classList.remove('btn-danger');
                this.classList.add('btn-primary');
            });
        } else {
            if (!qrScanner) qrScanner = new Html5Qrcode('qr-reader');
            qrScanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 260, height: 260 } },
                (decoded) => submitScan(decoded.trim())
            ).then(() => {
                scannerActive = true;
                this.innerHTML = '<i class="bi bi-stop-fill me-1"></i>Ferma scanner';
                this.classList.remove('btn-primary');
                this.classList.add('btn-danger');
            }).catch(err => showFeedback('error', 'Impossibile aprire la fotocamera: ' + err));
        }
    });

    // ====== Manual input ======
    document.getElementById('manual-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('manual-code');
        submitScan(input.value.trim());
        input.value = '';
        input.focus();
    });

    // ====== Toggle (delegated) ======
    $container.addEventListener('click', function (e) {
        const btn = e.target.closest('.seat-toggle');
        if (!btn) return;
        const id = btn.dataset.id;
        btn.disabled = true;
        fetch(toggleUrlTpl.replace('__SEAT__', id), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        })
        .then(r => r.json())
        .then(body => {
            if (body.success) {
                showFeedback(body.action === 'boarded' ? 'success' : 'warn',
                    body.action === 'boarded' ? `${body.seat.name} imbarcato.` : `Imbarco annullato per ${body.seat.name}.`);
                fetchState();
                if (body.action === 'boarded') setTimeout(() => flashSeat(body.seat.id), 200);
            }
        })
        .finally(() => { btn.disabled = false; });
    });

    // ====== Filters ======
    document.querySelectorAll('[data-filter]').forEach(b => {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-filter]').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            fetchState();
        });
    });

    // Initial + polling
    fetchState();
    setInterval(fetchState, 5000);
})();
</script>
@endpush
