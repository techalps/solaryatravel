@extends('layouts.b2b')

@section('title', 'Widget per il sito')

@php
    // Snippet pronto da incollare. L'embed.js gestisce l'auto-resize dell'iframe.
    $snippet = '<div data-solarya-widget data-ref="'.$token.'"></div>'."\n"
        .'<script src="'.$embedJsUrl.'" async></script>';
@endphp

@section('content')

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-1">Widget per il tuo sito</h2>
        <p class="text-muted mb-0">Incorpora la prenotazione delle crociere direttamente nel tuo sito.
            I clienti prenotano e pagano senza uscire dalle tue pagine, e la vendita risulta tua con la tua commissione.</p>
    </div>

    <div class="row g-3">
        {{-- Codice agenzia + snippet --}}
        <div class="col-lg-7">
            {{-- Codice agenzia --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-2"><i class="bi bi-person-badge me-2 text-primary"></i>Il tuo codice agenzia</h3>
                    <p class="text-muted small mb-2">È il codice che identifica le tue vendite. È già incluso nello snippet qui sotto.</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace fw-bold" id="agencyToken" value="{{ $token }}" readonly onclick="this.select()">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyVal('agencyToken', this)">
                            <i class="bi bi-clipboard me-1"></i>Copia
                        </button>
                    </div>
                </div>
            </div>

            {{-- Snippet --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-2"><i class="bi bi-code-square me-2 text-primary"></i>Codice da incollare</h3>
                    <p class="text-muted small mb-2">Incolla questo codice nel punto della pagina dove vuoi far apparire il modulo di prenotazione.</p>
                    <textarea class="form-control font-monospace small mb-2" id="embedSnippet" rows="3" readonly onclick="this.select()">{{ $snippet }}</textarea>
                    <button class="btn btn-primary" type="button" onclick="copyVal('embedSnippet', this)">
                        <i class="bi bi-clipboard me-1"></i>Copia codice
                    </button>
                </div>
            </div>

            {{-- Come funziona --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Come funziona</h3>
                    <ol class="small text-muted ps-3 mb-0" style="line-height:1.8">
                        <li>Incolli il codice nel tuo sito (una pagina, un articolo, un blocco HTML).</li>
                        <li>Il cliente sceglie la crociera e prenota direttamente dal tuo sito.</li>
                        <li>Paga lui (carta o bonifico): Solarya incassa e tu maturi la commissione.</li>
                        <li>Trovi tutte le prenotazioni in <a href="{{ route('b2b.bookings.index') }}">Le mie prenotazioni</a>.</li>
                    </ol>
                    <hr class="my-3">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-question-circle me-1"></i>
                        Non sai come incollare codice HTML sul tuo sito? Inoltra lo snippet a chi gestisce il tuo sito,
                        oppure usa il <a href="{{ route('b2b.referral') }}">link &amp; QR</a> come alternativa più semplice.
                    </p>
                </div>
            </div>
        </div>

        {{-- Anteprima live --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-eye me-2 text-primary"></i>Anteprima</h3>
                    <div class="border rounded-3 overflow-hidden" style="background:#f8fafc">
                        <iframe src="{{ $widgetUrl }}" style="width:100%;height:560px;border:0" title="Anteprima widget" loading="lazy"></iframe>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Così apparirà sul tuo sito. Sul sito reale l'altezza si adatta automaticamente al contenuto.</p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function copyVal(id, btn) {
        const el = document.getElementById(id);
        navigator.clipboard.writeText(el.value).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato';
            setTimeout(() => btn.innerHTML = original, 1800);
        });
    }
</script>
@endpush
