{{--
    Modulo JS riutilizzabile per i campi documento d'identità dei passeggeri nei
    form admin (create/edit). Genera l'HTML del blocco documento e gestisce la
    cascata Stato→Provincia→Comune con fetch on-demand dei comuni.

    Espone window.SolaryaDocFields con:
      - html(prefix, i, doc, {tripDate})   -> stringa HTML del blocco
      - wire(container)                     -> aggancia i listener (delegati) una volta
      - types                               -> mappa value=>label

    L'HTML usa input name="{prefix}[{i}][doc_*]" così lo store() li riceve come
    array paralleli ad adults[]/children[].
--}}
@php
    $docTypes = \App\Models\BookingSeat::DOC_TYPES;
    $countries = \App\Support\Geo::countries();
    $provinces = \App\Support\Geo::provinces();
@endphp
<script>
window.SolaryaDocFields = (function () {
    const TYPES = @json($docTypes);
    const COUNTRIES = @json($countries);
    const PROVINCES = @json($provinces);
    const comuniUrlTpl = @json(url('/api/geo/comuni/__SIGLA__'));
    const comuniCache = {};

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function optionList(items, valKey, labelFn, selected) {
        return items.map(it => {
            const v = valKey ? it[valKey] : it;
            const sel = String(v) === String(selected || '') ? ' selected' : '';
            return `<option value="${esc(v)}"${sel}>${esc(labelFn(it))}</option>`;
        }).join('');
    }

    // Blocco documento per un passeggero. prefix = 'adults'|'children'.
    function html(prefix, i, doc, opts) {
        doc = doc || {};
        opts = opts || {};
        const country = (doc.doc_issue_country || doc.doc_country || 'IT').toUpperCase();
        const isItaly = country === 'IT';
        const prov = doc.doc_issue_province || doc.doc_province || '';
        const place = doc.doc_issue_place || doc.doc_place || '';
        const minAttr = opts.tripDate ? ` min="${esc(opts.tripDate)}"` : '';
        // prefix vuoto → name "flat" (doc_type). Utile per l'edit di un singolo seat.
        const n = (f) => prefix ? `${prefix}[${i}][${f}]` : f;

        const typeOpts = '<option value="">Tipo documento *</option>' +
            Object.keys(TYPES).map(k => `<option value="${esc(k)}"${k === (doc.doc_type||'') ? ' selected' : ''}>${esc(TYPES[k])}</option>`).join('');
        const countryOpts = optionList(COUNTRIES, 'code', c => c.name, country);
        const provOpts = '<option value="">Provincia *</option>' +
            optionList(PROVINCES, 'sigla', p => `${p.name} (${p.sigla})`, prov);

        // Comune: se già noto (edit), lo pre-inseriamo come unica option selezionata;
        // il fetch al focus/refresh completerà la lista.
        const comuneInner = place
            ? `<option value="${esc(place)}" selected>${esc(place)}</option>`
            : '<option value="">Comune *</option>';

        return `
        <div class="ab-doc-block mt-2 p-2 rounded border bg-light-subtle" data-doc-block data-prefix="${prefix}" data-index="${i}">
            <div class="small fw-semibold text-muted mb-2"><i class="bi bi-person-vcard me-1"></i>Documento d'identità <span class="text-danger">*</span></div>
            <div class="row g-2">
                <div class="col-md-6">
                    <select class="form-select form-select-sm" name="${n('doc_type')}" required>${typeOpts}</select>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm text-uppercase" name="${n('doc_number')}" value="${esc(doc.doc_number || '')}" placeholder="Numero documento *" maxlength="40" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-0">Scadenza *</label>
                    <input type="date" class="form-control form-control-sm" name="${n('doc_expiry')}" value="${esc(doc.doc_expiry || '')}"${minAttr} required>
                </div>
                <div class="col-12"><label class="form-label small mb-0">Luogo di emissione *</label></div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="${n('doc_country')}" data-doc-country required>${countryOpts}</select>
                </div>
                <div class="col-md-4" data-doc-province-wrap${isItaly ? '' : ' style="display:none"'}>
                    <select class="form-select form-select-sm" name="${n('doc_province')}" data-doc-province ${isItaly ? 'required' : ''}>${provOpts}</select>
                </div>
                <div class="col-md-4" data-doc-place-wrap>
                    ${isItaly
                        ? `<select class="form-select form-select-sm" name="${n('doc_place')}" data-doc-place ${prov ? '' : 'disabled'} required>${comuneInner}</select>`
                        : `<input type="text" class="form-control form-control-sm" name="${n('doc_place')}" value="${esc(place)}" placeholder="Città / luogo *" maxlength="120" required>`}
                </div>
            </div>
        </div>`;
    }

    async function fetchComuni(sigla) {
        sigla = (sigla || '').toUpperCase();
        if (!sigla) return [];
        if (comuniCache[sigla]) return comuniCache[sigla];
        try {
            const res = await fetch(comuniUrlTpl.replace('__SIGLA__', sigla), { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            comuniCache[sigla] = json.comuni || [];
        } catch (e) {
            comuniCache[sigla] = [];
        }
        return comuniCache[sigla];
    }

    // Popola la select comune di un blocco (conservando l'eventuale valore scelto).
    async function loadComuniInto(block) {
        const provSel = block.querySelector('[data-doc-province]');
        const placeSel = block.querySelector('select[data-doc-place]');
        if (!provSel || !placeSel) return;
        const sigla = provSel.value;
        if (!sigla) { placeSel.disabled = true; return; }
        const current = placeSel.value;
        const list = await fetchComuni(sigla);
        placeSel.disabled = false;
        placeSel.innerHTML = '<option value="">Comune *</option>' +
            list.map(c => `<option value="${esc(c)}"${c === current ? ' selected' : ''}>${esc(c)}</option>`).join('');
    }

    // Il passaggio IT <-> estero cambia il tipo di controllo per il luogo: ricostruiamo
    // provincia+comune per quel blocco senza toccare gli altri campi già compilati.
    function rebuildPlace(block) {
        const country = (block.querySelector('[data-doc-country]').value || '').toUpperCase();
        const isItaly = country === 'IT';
        const prefix = block.dataset.prefix;
        const i = block.dataset.index;
        const provWrap = block.querySelector('[data-doc-province-wrap]');
        const placeWrap = block.querySelector('[data-doc-place-wrap]');
        const n = (f) => prefix ? `${prefix}[${i}][${f}]` : f;

        if (isItaly) {
            provWrap.style.display = '';
            provWrap.querySelector('select').setAttribute('required', 'required');
            placeWrap.innerHTML = `<select class="form-select form-select-sm" name="${n('doc_place')}" data-doc-place disabled required><option value="">Comune *</option></select>`;
        } else {
            provWrap.style.display = 'none';
            const provSel = provWrap.querySelector('select');
            provSel.removeAttribute('required');
            provSel.value = '';
            placeWrap.innerHTML = `<input type="text" class="form-control form-control-sm" name="${n('doc_place')}" placeholder="Città / luogo *" maxlength="120" required>`;
        }
    }

    let wired = false;
    function wire(root) {
        if (wired) return; // listener globali: una volta sola
        wired = true;
        document.addEventListener('change', (e) => {
            const t = e.target;
            const block = t.closest && t.closest('[data-doc-block]');
            if (!block) return;
            if (t.matches('[data-doc-country]')) {
                rebuildPlace(block);
            } else if (t.matches('[data-doc-province]')) {
                loadComuniInto(block);
            }
        });
        // Al primo focus su un comune (edit con provincia già valorizzata), assicura la lista.
        document.addEventListener('focusin', (e) => {
            const sel = e.target.closest && e.target.closest('select[data-doc-place]');
            if (sel && sel.options.length <= 2) {
                const block = sel.closest('[data-doc-block]');
                if (block) loadComuniInto(block);
            }
        });
    }

    return { html, wire, types: TYPES, loadComuniInto };
})();
</script>
