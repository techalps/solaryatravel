@extends('layouts.b2b')

@section('title', 'Link & QR')

@section('content')

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-1">Il tuo link &amp; QR</h2>
        <p class="text-muted mb-0">Condividi questo link (o il QR) con i tuoi clienti: prenoteranno in autonomia
            e la vendita risulterà tua, con la tua commissione.</p>
    </div>

    <div class="row g-3">
        {{-- Link --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-link-45deg me-2 text-primary"></i>Link personale</h3>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="refUrl" value="{{ $referralUrl }}" readonly
                               onclick="this.select()">
                        <button class="btn btn-primary" type="button" onclick="copyRef(this)">
                            <i class="bi bi-clipboard me-1"></i>Copia
                        </button>
                    </div>
                    <p class="text-muted small mb-0">
                        Puoi metterlo sul tuo sito, sui social o inviarlo via WhatsApp/email al cliente.
                    </p>

                    <hr class="my-4">

                    <h3 class="h6 fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Come funziona</h3>
                    <ol class="small text-muted ps-3 mb-0" style="line-height:1.8">
                        <li>Il cliente apre il tuo link e prenota da solo sul sito Solarya.</li>
                        <li>Paga lui direttamente (carta o bonifico).</li>
                        <li>La prenotazione risulta tua: la trovi in <a href="{{ route('b2b.bookings.index') }}">Le mie prenotazioni</a> con la commissione.</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- QR --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-qr-code me-2 text-primary"></i>QR code</h3>
                    <img src="{{ route('b2b.referral.qr') }}" alt="QR referral"
                         style="width:220px;height:220px;max-width:100%;border:1px solid #eef0f3;border-radius:14px;padding:8px;background:#fff">
                    <div class="mt-3 d-flex flex-column gap-2 align-items-center">
                        <a href="{{ route('b2b.referral.flyer') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Scarica volantino A4 (PDF)
                        </a>
                        <a href="{{ route('b2b.referral.qr') }}" download="solarya-referral.png" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i>Scarica solo il QR (PNG)
                        </a>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Il volantino è pronto da stampare ed esporre in vetrina.</p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function copyRef(btn) {
        const input = document.getElementById('refUrl');
        navigator.clipboard.writeText(input.value).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato';
            setTimeout(() => btn.innerHTML = original, 1800);
        });
    }
</script>
@endpush
