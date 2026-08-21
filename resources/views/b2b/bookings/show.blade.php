@extends('layouts.b2b')

@section('title', 'Prenotazione ' . $booking->booking_number)

@php
    $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.');
    $req = $booking->metadata['b2b_request'] ?? null;
    $hasPendingRequest = $req && ($req['status'] ?? null) === 'pending';
    $canResend = in_array($booking->status, [\App\Enums\BookingStatus::PENDING, \App\Enums\BookingStatus::AWAITING_TRANSFER, \App\Enums\BookingStatus::DEPOSIT_PAID], true);
    $canRequest = $booking->canBeCancelled();

    // Email correggibile finche' la prenotazione e' viva: su una chiusa non c'e'
    // piu' nulla da comunicare. Il reinvio riguarda la comunicazione pertinente
    // allo stato: estremi di pagamento se c'e' da pagare, biglietti se confermata.
    $chiusa = in_array($booking->status, [\App\Enums\BookingStatus::CANCELLED, \App\Enums\BookingStatus::REFUNDED], true);
    $canEditEmail = ! $chiusa;
    $canResendTickets = in_array($booking->status, [\App\Enums\BookingStatus::CONFIRMED, \App\Enums\BookingStatus::CHECKED_IN, \App\Enums\BookingStatus::COMPLETED], true);
@endphp

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <a href="{{ route('b2b.bookings.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i>Le mie prenotazioni</a>
            <h2 class="h4 fw-bold mb-0 mt-1">{{ $booking->booking_number }}</h2>
        </div>
        @include('b2b.partials.status-badge', ['status' => $booking->status])
    </div>

    {{-- Richiesta in attesa --}}
    @if($hasPendingRequest)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-hourglass-split fs-5"></i>
            <div>
                Hai richiesto <strong>{{ $req['type'] === 'cancellation' ? 'l\'annullamento' : 'una modifica' }}</strong>
                di questa prenotazione. È in attesa di valutazione da parte di Solarya.
            </div>
        </div>
    @endif

    <div class="row g-3">
        {{-- Colonna principale --}}
        <div class="col-lg-8">
            {{-- Cliente --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 py-3"><h3 class="h6 fw-bold mb-0"><i class="bi bi-person me-2 text-primary"></i>Cliente</h3></div>
                <div class="card-body pt-0">
                    <div class="row g-2 small">
                        <div class="col-sm-6"><span class="text-muted">Nome</span><div class="fw-semibold">{{ $booking->customer_full_name }}</div></div>
                        <div class="col-sm-6">
                            <span class="text-muted">Email</span>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <span>{{ $booking->customer_email }}</span>
                                @if($canEditEmail)
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                                            data-bs-toggle="collapse" data-bs-target="#editEmailForm"
                                            title="Correggi l'email del cliente">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6"><span class="text-muted">Telefono</span><div class="fw-semibold">{{ $booking->customer_phone ?: '—' }}</div></div>
                    </div>

                    {{-- Correzione email: l'unico dato modificabile dall'agenzia senza
                         approvazione. Un indirizzo sbagliato rende il cliente
                         irraggiungibile, e aspettare un'approvazione lo terrebbe tale. --}}
                    @if($canEditEmail)
                        <div class="collapse mt-3" id="editEmailForm">
                            <form method="POST" action="{{ route('b2b.bookings.update-email', $booking->uuid) }}" class="bg-light rounded-3 p-3">
                                @csrf
                                <label for="customer_email" class="form-label small fw-semibold mb-1">
                                    Correggi l'email del cliente
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="email" name="customer_email" id="customer_email"
                                           value="{{ old('customer_email', $booking->customer_email) }}"
                                           class="form-control @error('customer_email') is-invalid @enderror" required>
                                    <button type="submit" class="btn btn-primary">Salva</button>
                                </div>
                                @error('customer_email')
                                    <div class="small text-danger mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-muted mt-2" style="font-size:.76rem">
                                    Dopo il salvataggio ricordati di <strong>reinviare le comunicazioni</strong>:
                                    quelle già spedite sono andate all'indirizzo precedente.
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tour & partecipanti --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 py-3"><h3 class="h6 fw-bold mb-0"><i class="bi bi-compass me-2 text-primary"></i>Tour & partecipanti</h3></div>
                <div class="card-body pt-0">
                    <div class="row g-2 small mb-3">
                        <div class="col-sm-6"><span class="text-muted">Tour</span><div class="fw-semibold">{{ $booking->tour?->name ?? '—' }}</div></div>
                        <div class="col-sm-6"><span class="text-muted">Data</span><div class="fw-semibold">{{ optional($booking->booking_date)->format('d/m/Y') }}@if($booking->departure?->start_time) · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}@endif</div></div>
                        <div class="col-sm-6"><span class="text-muted">Posti</span><div class="fw-semibold">{{ $booking->seats }}</div></div>
                    </div>
                    @if($booking->seatRecords->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm small mb-0">
                                <tbody>
                                    @foreach($booking->seatRecords as $seat)
                                        <tr>
                                            <td>
                                                {{ trim(($seat->guest_first_name ?? '').' '.($seat->guest_last_name ?? '')) ?: 'Partecipante' }}
                                                @if($seat->hasDocument())
                                                    <div class="text-muted" style="font-size:.78rem">
                                                        <i class="bi bi-person-vcard me-1"></i>{{ $seat->docTypeLabel() }}
                                                        {{ $seat->doc_number }}@if($seat->doc_expiry) · scad. {{ $seat->doc_expiry->format('d/m/Y') }}@endif
                                                    </div>
                                                @else
                                                    <div class="text-warning" style="font-size:.78rem"><i class="bi bi-exclamation-triangle me-1"></i>Documento mancante</div>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $seat->ageBracket?->label ?? 'Adulto' }}</td>
                                            <td class="text-end">{{ $eur($seat->price_paid) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted mt-2 mb-0" style="font-size:.78rem"><i class="bi bi-info-circle me-1"></i>Per modificare i dati del documento contatta Solarya.</p>
                    @endif
                </div>
            </div>

            {{-- Biglietti / QR --}}
            @if(in_array($booking->status, [\App\Enums\BookingStatus::CONFIRMED, \App\Enums\BookingStatus::DEPOSIT_PAID, \App\Enums\BookingStatus::CHECKED_IN, \App\Enums\BookingStatus::COMPLETED], true))
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-0 py-3"><h3 class="h6 fw-bold mb-0"><i class="bi bi-qr-code me-2 text-primary"></i>Biglietti</h3></div>
                    <div class="card-body pt-0 d-flex align-items-center gap-3 flex-wrap">
                        {{-- QR e biglietti sono rotte del SITO PUBBLICO (routes/web.php):
                             route() li costruirebbe sull'host corrente, cioe' il
                             sottodominio b2b, dove non esistono -> 404 e immagine rotta.
                             public_site_route() li forza sull'host principale. --}}
                        <img src="{{ public_site_route('booking.qr', $booking->uuid) }}" alt="QR prenotazione" width="120" height="120" style="border:1px solid #eef0f3;border-radius:12px">
                        <div>
                            <p class="small text-muted mb-2">QR della prenotazione, valido per l'imbarco.</p>
                            <a href="{{ public_site_route('booking.tickets', $booking->uuid) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-ticket-perforated me-1"></i>Apri biglietti
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar: pagamento + commissione + azioni --}}
        <div class="col-lg-4">
            {{-- Pagamento --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 py-3"><h3 class="h6 fw-bold mb-0"><i class="bi bi-credit-card me-2 text-primary"></i>Pagamento</h3></div>
                <div class="card-body pt-0 small">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Totale</span><span class="fw-bold">{{ $eur($booking->total_amount) }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Versato</span><span class="fw-semibold">{{ $eur($booking->amount_paid) }}</span></div>
                    @if($booking->hasBalanceDue())
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">Saldo</span><span class="fw-semibold text-danger">{{ $eur($booking->balance_amount) }}</span></div>
                        @if($booking->balance_due_at)
                            <div class="text-muted">Scadenza saldo: {{ $booking->balance_due_at->format('d/m/Y H:i') }}</div>
                        @endif
                    @endif
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Metodo</span>
                        <span class="fw-semibold">{{ ['full' => 'Intero', 'deposit' => 'Acconto + saldo', 'bank_transfer' => 'Bonifico'][$booking->payment_type] ?? $booking->payment_type }}</span>
                    </div>

                    @if($canResend)
                        <form method="POST" action="{{ route('b2b.bookings.resend-payment', $booking->uuid) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 btn-sm">
                                <i class="bi bi-envelope me-1"></i>Reinvia estremi al cliente
                            </button>
                        </form>
                        <p class="text-muted mt-2 mb-0" style="font-size:.76rem">
                            Invia a <strong>{{ $booking->customer_email }}</strong> il link di pagamento (carta) o le istruzioni di bonifico.
                            @if($booking->payment_link_sent_at)<br>Ultimo invio: {{ $booking->payment_link_sent_at->format('d/m/Y H:i') }}.@endif
                        </p>
                    @endif

                    {{-- Prenotazione gia' confermata: la comunicazione da reinviare
                         sono i biglietti, tipicamente dopo aver corretto l'email. --}}
                    @if($canResendTickets)
                        <form method="POST" action="{{ route('b2b.bookings.resend-communications', $booking->uuid) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 btn-sm">
                                <i class="bi bi-ticket-perforated me-1"></i>Reinvia i biglietti al cliente
                            </button>
                        </form>
                        <p class="text-muted mt-2 mb-0" style="font-size:.76rem">
                            Invia di nuovo a <strong>{{ $booking->customer_email }}</strong> i biglietti con i QR.
                            @if($booking->tickets_sent_at)<br>Ultimo invio: {{ $booking->tickets_sent_at->format('d/m/Y H:i') }}.@endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Commissione (dato dell'agenzia) --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 py-3"><h3 class="h6 fw-bold mb-0"><i class="bi bi-percent me-2 text-warning"></i>La tua commissione</h3></div>
                <div class="card-body pt-0 small">
                    @if($booking->commission_status === 'reversed')
                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Stornata</span>
                        <p class="text-muted mt-2 mb-0" style="font-size:.76rem">La commissione è stata azzerata (annullamento/rimborso).</p>
                    @elseif($booking->commission_amount !== null)
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">Percentuale</span><span class="fw-semibold">{{ rtrim(rtrim(number_format((float) $booking->commission_rate_snapshot, 2), '0'), '.') }}%</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">Importo</span><span class="fw-bold text-success">{{ $eur($booking->commission_amount) }}</span></div>
                        <div class="mt-2">
                            @if($booking->commission_paid)
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle-fill me-1"></i>Pagata da Solarya</span>
                            @else
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split me-1"></i>Da ricevere</span>
                            @endif
                        </div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>

            {{-- Azioni: richieste ad admin --}}
            @if($canRequest && ! $hasPendingRequest)
                <div class="card border-0 shadow-sm">
                    <div class="card-body small">
                        <p class="text-muted mb-2" style="font-size:.78rem">Annullamento e modifica devono essere approvati da Solarya.</p>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2" data-bs-toggle="collapse" data-bs-target="#reqModForm">
                            <i class="bi bi-pencil me-1"></i>Richiedi modifica
                        </button>
                        <div class="collapse mb-2" id="reqModForm">
                            <form method="POST" action="{{ route('b2b.bookings.request-modification', $booking->uuid) }}">
                                @csrf
                                <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" placeholder="Cosa va modificato? *" required></textarea>
                                <button type="submit" class="btn btn-primary btn-sm w-100">Invia richiesta</button>
                            </form>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="collapse" data-bs-target="#reqCancelForm">
                            <i class="bi bi-x-circle me-1"></i>Richiedi annullamento
                        </button>
                        <div class="collapse mt-2" id="reqCancelForm">
                            <form method="POST" action="{{ route('b2b.bookings.request-cancellation', $booking->uuid) }}">
                                @csrf
                                <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" placeholder="Motivo (opzionale)"></textarea>
                                <button type="submit" class="btn btn-danger btn-sm w-100">Invia richiesta di annullamento</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
