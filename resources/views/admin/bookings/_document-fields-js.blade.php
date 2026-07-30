{{--
    Modulo JS riutilizzabile per i campi documento d'identità dei passeggeri nei
    form admin (create/edit).

    Del documento si chiedono solo TIPO e NUMERO: si sceglie la tipologia e si
    compila il numero. Scadenza e luogo di rilascio (stato/provincia/comune) non
    si chiedono più in nessun form, nemmeno in admin — troppi campi in fase di
    prenotazione. Con essi è sparita anche la cascata Stato→Provincia→Comune e il
    fetch dei comuni: le colonne restano a database per lo storico.

    Espone window.SolaryaDocFields con:
      - html(prefix, i, doc)   -> stringa HTML del blocco
      - wire(container)         -> no-op, mantenuto per compatibilità con i chiamanti
      - types                   -> mappa value=>label

    L'HTML usa input name="{prefix}[{i}][doc_*]" così lo store() li riceve come
    array paralleli ad adults[]/children[]. prefix vuoto → name "flat" (doc_type),
    usato per l'edit di un singolo seat.
--}}
@php
    $docTypes = \App\Models\BookingSeat::DOC_TYPES;
@endphp
<script>
window.SolaryaDocFields = (function () {
    const TYPES = @json($docTypes);

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Blocco documento per un passeggero. prefix = 'adults'|'children'.
    function html(prefix, i, doc) {
        doc = doc || {};
        const n = (f) => prefix ? `${prefix}[${i}][${f}]` : f;

        const typeOpts = '<option value="">Tipo documento *</option>' +
            Object.keys(TYPES).map(k => `<option value="${esc(k)}"${k === (doc.doc_type||'') ? ' selected' : ''}>${esc(TYPES[k])}</option>`).join('');

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
            </div>
        </div>`;
    }

    // Senza cascata non c'è più nulla da agganciare: resta come no-op così i
    // chiamanti esistenti non vanno toccati.
    function wire() {}

    return { html, wire, types: TYPES };
})();
</script>
