@extends('layouts.admin')

@section('title', 'Prenotazione ' . $booking->booking_number)

@section('content')
    @php
        $statusMeta = [
            'pending'    => ['label' => 'In attesa',  'icon' => 'bi-hourglass-split', 'color' => 'warning'],
            'deposit_paid'      => ['label' => 'Acconto versato', 'icon' => 'bi-wallet2', 'color' => 'info'],
            'awaiting_transfer' => ['label' => 'Attesa bonifico', 'icon' => 'bi-bank',    'color' => 'warning'],
            'confirmed'  => ['label' => 'Confermata', 'icon' => 'bi-check-circle',   'color' => 'success'],
            'checked_in' => ['label' => 'Check-in',   'icon' => 'bi-qr-code-scan',   'color' => 'info'],
            'completed'  => ['label' => 'Completata', 'icon' => 'bi-flag-fill',      'color' => 'secondary'],
            'cancelled'  => ['label' => 'Annullata',  'icon' => 'bi-x-circle',       'color' => 'danger'],
            'no_show'    => ['label' => 'No show',    'icon' => 'bi-eye-slash',      'color' => 'secondary'],
            'refunded'   => ['label' => 'Rimborsata', 'icon' => 'bi-arrow-counterclockwise', 'color' => 'dark'],
        ];
        $statusValue = $booking->status?->value ?? (string) $booking->status;
        $sm = $statusMeta[$statusValue] ?? ['label' => ucfirst($statusValue), 'icon' => 'bi-circle', 'color' => 'secondary'];
        $currency = $booking->currency ?: 'EUR';
        $fmtMoney = fn ($v) => number_format((float) $v, 2, ',', '.') . ' ' . $currency;
    @endphp

    {{-- Header --}}
    <div class="dash-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.bookings.index') }}" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left"></i> Prenotazioni
                </a>
            </div>
            <h1 class="mb-1">#{{ $booking->booking_number }}</h1>
            <p class="mb-0">
                <span class="badge bg-{{ $sm['color'] }}-subtle text-{{ $sm['color'] }} fw-semibold">
                    <i class="bi {{ $sm['icon'] }} me-1"></i>{{ $sm['label'] }}
                </span>
                <span class="text-muted ms-2">creata il {{ $booking->created_at?->format('d/m/Y H:i') }}</span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.bookings.export', $booking) }}" class="btn btn-light rounded-pill border px-3 fw-semibold">
                <i class="bi bi-download me-2"></i>Export
            </a>
            <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-light rounded-pill border px-3 fw-semibold">
                <i class="bi bi-pencil me-2"></i>Modifica
            </a>
            @if ($booking->departure)
                <a href="{{ route('admin.assignments.show', $booking->departure) }}" class="btn btn-light rounded-pill border px-3 fw-semibold">
                    <i class="bi bi-water me-2"></i>Gestisci catamarani
                </a>
            @endif
            @if ($statusValue === 'pending')
                <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-success rounded-pill px-3 fw-semibold">
                        <i class="bi bi-check-lg me-2"></i>Conferma
                    </button>
                </form>
            @endif
            @if ($statusValue === 'awaiting_transfer')
                @php $isExpired = $booking->payment_deadline && $booking->payment_deadline->isPast(); @endphp
                <form action="{{ route('admin.bookings.confirm-transfer', $booking) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Confermi di aver ricevuto il bonifico per questa prenotazione?');">
                    @csrf
                    <button class="btn btn-success rounded-pill px-3 fw-semibold">
                        <i class="bi bi-bank me-2"></i>Conferma incasso bonifico
                    </button>
                </form>
                @if ($booking->payment_deadline)
                    <span class="badge rounded-pill {{ $isExpired ? 'text-bg-danger' : 'text-bg-warning' }} align-self-center">
                        <i class="bi bi-clock-history me-1"></i>
                        @if($isExpired)
                            Scaduta il {{ $booking->payment_deadline->format('d/m/Y H:i') }} — annullamento automatico in corso
                        @else
                            Scade il {{ $booking->payment_deadline->format('d/m/Y H:i') }}
                        @endif
                    </span>
                @endif
            @endif
            @if (!in_array($statusValue, ['cancelled', 'refunded', 'completed']))
                <button type="button" class="btn btn-outline-danger rounded-pill px-3 fw-semibold"
                        data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                    <i class="bi bi-x-lg me-2"></i>Annulla
                </button>
            @endif
            @if ($statusValue === 'cancelled' && (float) $booking->total_amount > 0)
                <button type="button" class="btn btn-outline-info rounded-pill px-3 fw-semibold"
                        data-bs-toggle="modal" data-bs-target="#refundBookingModal">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Rimborsa
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    {{-- Canale B2B: agenzia attribuita, commissione e richieste --}}
    @if ($booking->b2b_user_id)
        @php
            $b2bReq = $booking->metadata['b2b_request'] ?? null;
            $b2bReqPending = $b2bReq && ($b2bReq['status'] ?? null) === 'pending';
            $attrLabel = ['b2b_portal' => 'Portale agenzia', 'b2b_referral' => 'Link referral', 'b2b_widget' => 'Widget sul sito'][$booking->attribution_source] ?? $booking->attribution_source;
        @endphp
        <div class="card shadow-sm rounded-4 mb-3 border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="fw-bold mb-1">
                            <span class="badge rounded-pill bg-primary-subtle text-primary me-1"><i class="bi bi-briefcase-fill me-1"></i>B2B</span>
                            {{ $booking->b2bUser?->agency_name ?: $booking->b2bUser?->name ?? 'Agenzia' }}
                            <span class="text-muted small fw-normal">· {{ $attrLabel }}</span>
                        </div>
                        <div class="small text-muted">
                            Commissione:
                            @if($booking->commission_status === 'reversed')
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">Stornata</span>
                            @else
                                <strong>€ {{ number_format((float) $booking->commission_amount, 2, ',', '.') }}</strong>
                                ({{ rtrim(rtrim(number_format((float) $booking->commission_rate_snapshot, 2), '0'), '.') }}%)
                                @if($booking->commission_paid)
                                    <span class="badge bg-success-subtle text-success-emphasis ms-1"><i class="bi bi-check-circle-fill me-1"></i>Pagata</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Da liquidare</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    @if($booking->commission_status !== 'reversed' && ! $booking->commission_paid)
                        <form method="POST" action="{{ route('admin.commissions.mark-paid', $booking) }}">
                            @csrf
                            <button type="submit" class="btn btn-success rounded-pill px-3 fw-semibold btn-sm">
                                <i class="bi bi-check-lg me-1"></i>Segna commissione pagata
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Richiesta dell'agenzia in attesa di valutazione --}}
                @if($b2bReqPending)
                    <div class="alert alert-warning mt-3 mb-0">
                        <div class="fw-semibold mb-1">
                            <i class="bi bi-hourglass-split me-1"></i>
                            L'agenzia ha richiesto: {{ $b2bReq['type'] === 'cancellation' ? 'ANNULLAMENTO' : 'MODIFICA' }}
                        </div>
                        @if(!empty($b2bReq['reason']))
                            <div class="small mb-2">Motivo: {{ $b2bReq['reason'] }}</div>
                        @endif
                        <div class="d-flex flex-wrap gap-2">
                            @if($b2bReq['type'] === 'cancellation')
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                                    <i class="bi bi-x-circle me-1"></i>Procedi con l'annullamento
                                </button>
                            @endif
                            <form method="POST" action="{{ route('admin.bookings.resolve-b2b-request', $booking) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check2 me-1"></i>Segna gestita</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.resolve-b2b-request', $booking) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="decision" value="rejected">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x me-1"></i>Rifiuta richiesta</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Link di pagamento Stripe: presente quando la prenotazione attende il pagamento --}}
    @if ($booking->checkout_url && in_array($statusValue, ['pending', 'deposit_paid']))
        <div class="card shadow-sm rounded-4 mb-3 border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="fw-bold mb-1"><i class="bi bi-link-45deg me-1 text-primary"></i>Link di pagamento</div>
                        <div class="input-group input-group-sm" style="max-width:520px">
                            <input type="text" class="form-control font-monospace small" id="payLinkInput" value="{{ $booking->checkout_url }}" readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('payLinkInput').value); this.innerHTML='<i class=\'bi bi-check2\'></i>'">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        @if ($booking->payment_link_sent_at)
                            <div class="small text-muted mt-1"><i class="bi bi-envelope-check me-1"></i>Inviato il {{ $booking->payment_link_sent_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                    <form action="{{ route('admin.bookings.send-payment-link', $booking) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary rounded-pill px-3 fw-semibold">
                            <i class="bi bi-envelope-arrow-up me-2"></i>{{ $booking->payment_link_sent_at ? 'Reinvia' : 'Invia' }} al cliente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Saldo da incassare (prenotazioni con acconto) --}}
    @if ($booking->hasBalanceDue())
        <div class="card shadow-sm rounded-4 mb-3 border-start border-4 border-warning">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="min-w-0">
                        <div class="fw-bold mb-1"><i class="bi bi-wallet2 me-1 text-warning"></i>Saldo da incassare</div>
                        <div class="d-flex flex-wrap gap-3 small">
                            <span>Acconto versato: <strong>{{ $fmtMoney($booking->amount_paid) }}</strong></span>
                            <span>Saldo: <strong class="text-primary">{{ $fmtMoney($booking->balance_amount) }}</strong></span>
                            @if ($booking->balance_due_at)
                                <span>Scadenza: <strong>{{ $booking->balance_due_at->timezone('Europe/Rome')->format('d/m/Y') }}</strong></span>
                            @endif
                        </div>
                        @if ($booking->balance_reminder_sent_at)
                            <div class="small text-muted mt-1"><i class="bi bi-envelope-check me-1"></i>Richiesta inviata il {{ $booking->balance_reminder_sent_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                    <form action="{{ route('admin.bookings.send-balance-request', $booking) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning rounded-pill px-3 fw-semibold">
                            <i class="bi bi-envelope-arrow-up me-2"></i>{{ $booking->balance_reminder_sent_at ? 'Reinvia' : 'Invia' }} richiesta di saldo
                        </button>
                    </form>
                </div>
                <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Il cliente riceve un'email con il link per saldare (carta) o le istruzioni per il bonifico, secondo il metodo scelto.</div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        {{-- Colonna principale --}}
        <div class="col-lg-8">
            {{-- Tour & partenza --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Tour & partenza</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Tour</div>
                            <div class="fw-semibold">{{ $booking->tour?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Data</div>
                            <div class="fw-semibold">
                                {{ $booking->departure?->departure_date
                                    ? \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y')
                                    : '—' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Orario</div>
                            <div class="fw-semibold">
                                {{ $booking->departure?->start_time
                                    ? \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i')
                                    : '—' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Posti</div>
                            <div class="fw-semibold">{{ $booking->seats }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Prenotata il</div>
                            <div class="fw-semibold">{{ optional($booking->booking_date)->format('d/m/Y') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Sorgente</div>
                            <div class="fw-semibold text-none">{{ $booking->source ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Lingua</div>
                            <div class="fw-semibold text-uppercase">{{ $booking->locale ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person me-2 text-primary"></i>Cliente</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Nome</div>
                            <div class="fw-semibold">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">
                                <a href="mailto:{{ $booking->customer_email }}">{{ $booking->customer_email }}</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Telefono</div>
                            <div class="fw-semibold">
                                @if ($booking->customer_phone)
                                    <a href="tel:{{ $booking->customer_phone }}">{{ $booking->customer_phone }}</a>
                                @else — @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Paese</div>
                            <div class="fw-semibold">{{ $booking->customer_country ?? '—' }}</div>
                        </div>
                        @if ($booking->special_requests)
                            <div class="col-12">
                                <div class="text-muted small">Note / richieste</div>
                                <div class="bg-light rounded-3 p-3">{{ $booking->special_requests }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Catamarani riservati (uso esclusivo) --}}
            @if (isset($reservedBlocks) && $reservedBlocks->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-water me-2 text-primary"></i>Catamarani riservati (uso esclusivo)</h5>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Catamarano</th>
                                        <th>Dal</th>
                                        <th>Al</th>
                                        <th>Orari</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservedBlocks as $blk)
                                        <tr>
                                            <td class="fw-semibold">{{ $blk->catamaran?->name ?? ('#' . $blk->catamaran_id) }}</td>
                                            <td>{{ $blk->start_date->format('d/m/Y') }}</td>
                                            <td>{{ $blk->end_date->format('d/m/Y') }}</td>
                                            <td>
                                                @if ($blk->start_time || $blk->end_time)
                                                    {{ $blk->start_time ? \Carbon\Carbon::parse($blk->start_time)->format('H:i') : '—' }}
                                                    →
                                                    {{ $blk->end_time ? \Carbon\Carbon::parse($blk->end_time)->format('H:i') : '—' }}
                                                @else
                                                    <span class="text-muted">Intera giornata</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Sposta la riserva su un altro catamarano libero (posti + blocco). --}}
                        @if (isset($freeCatamaransForReservation) && $freeCatamaransForReservation->isNotEmpty() && $reservedBlocks->count() === 1)
                            <form method="POST" action="{{ route('admin.bookings.move-reservation', $booking) }}" class="d-flex align-items-center gap-2 mt-3">
                                @csrf
                                <label class="small fw-semibold text-muted mb-0">Sposta la riserva su:</label>
                                <select name="catamaran_id" class="form-select form-select-sm" style="width:auto" required>
                                    <option value="">Scegli catamarano…</option>
                                    @foreach ($freeCatamaransForReservation as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-left-right me-1"></i>Sposta
                                </button>
                            </form>
                            <p class="small text-muted mt-2 mb-0">Sposta posti e riserva sul nuovo catamarano; quello attuale si libera.</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Partecipanti / posti --}}
            @if ($booking->seatRecords->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-people me-2 text-primary"></i>Partecipanti</h5>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Ospite</th>
                                        <th>Fascia</th>
                                        <th>Catamarano</th>
                                        <th>Documento</th>
                                        <th class="text-end">Prezzo</th>
                                        <th>Imbarco</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->seatRecords as $i => $seat)
                                        <tr class="{{ $seat->cancelled_at ? 'text-muted' : '' }}">
                                            <td>{{ $i + 1 }}{{ $seat->is_primary ? ' ★' : '' }}</td>
                                            <td>
                                                <span class="{{ $seat->cancelled_at ? 'text-decoration-line-through' : '' }}">
                                                    @if ($seat->guest_first_name || $seat->guest_last_name)
                                                        {{ trim($seat->guest_first_name . ' ' . $seat->guest_last_name) }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </span>
                                                @if ($seat->cancelled_at)
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Disdetto</span>
                                                @endif
                                                <div class="small text-muted">{{ $seat->qr_code }}</div>
                                            </td>
                                            <td>{{ $seat->ageBracket?->label ?? '—' }}</td>
                                            <td>{{ $seat->catamaran?->name ?? '—' }}</td>
                                            <td>
                                                @if ($seat->hasDocument())
                                                    @php
                                                        $docExpired = $booking->departure && $seat->doc_expiry
                                                            && $seat->doc_expiry->lt($booking->departure->departure_date);
                                                    @endphp
                                                    <span class="badge {{ $docExpired ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                                        <i class="bi bi-person-vcard me-1"></i>{{ $seat->docTypeLabel() }}
                                                    </span>
                                                    <div class="small text-muted">
                                                        {{ $seat->doc_number }} · scad. {{ $seat->doc_expiry?->format('d/m/Y') }}
                                                        @if ($docExpired) <span class="text-danger">(scaduto!)</span> @endif
                                                    </div>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Mancante</span>
                                                @endif
                                                @unless ($seat->cancelled_at)
                                                    <button type="button" class="btn btn-link btn-sm p-0 small" data-bs-toggle="collapse" data-bs-target="#doc-edit-{{ $seat->id }}">
                                                        <i class="bi bi-pencil me-1"></i>{{ $seat->hasDocument() ? 'Modifica' : 'Inserisci' }}
                                                    </button>
                                                @endunless
                                            </td>
                                            <td class="text-end">{{ $fmtMoney($seat->price_paid) }}</td>
                                            <td>
                                                @if ($seat->cancelled_at)
                                                    <span class="text-muted small">{{ $seat->cancelled_at->timezone('Europe/Rome')->format('d/m/Y') }}</span>
                                                @elseif ($seat->boarded_at)
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="bi bi-check2 me-1"></i>{{ $seat->boarded_at->format('d/m H:i') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @unless ($seat->cancelled_at)
                                            <tr class="collapse" id="doc-edit-{{ $seat->id }}">
                                                <td colspan="7" class="bg-light-subtle">
                                                    <form method="POST" action="{{ route('admin.bookings.seats.document', [$booking, $seat]) }}">
                                                        @csrf
                                                        <div class="fw-semibold small mb-2">
                                                            <i class="bi bi-person-vcard me-1"></i>Documento di {{ trim($seat->guest_first_name . ' ' . $seat->guest_last_name) ?: 'passeggero' }}
                                                        </div>
                                                        <div id="doc-edit-fields-{{ $seat->id }}"></div>
                                                        <div class="mt-2">
                                                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check2 me-1"></i>Salva documento</button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endunless
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @push('scripts')
                @include('admin.bookings._document-fields-js')
                <script>
                (function () {
                    const tripDate = @json(optional($booking->departure)->departure_date?->toDateString());
                    @foreach ($booking->seatRecords as $seat)
                        @unless ($seat->cancelled_at)
                        (function () {
                            const slot = document.getElementById('doc-edit-fields-{{ $seat->id }}');
                            if (!slot) return;
                            const doc = @json([
                                'doc_type' => $seat->doc_type,
                                'doc_number' => $seat->doc_number,
                                'doc_expiry' => $seat->doc_expiry?->toDateString(),
                                'doc_issue_country' => $seat->doc_issue_country ?: 'IT',
                                'doc_issue_province' => $seat->doc_issue_province,
                                'doc_issue_place' => $seat->doc_issue_place,
                            ]);
                            // prefix vuoto → name "flat" (doc_type), atteso da updateSeatDocument().
                            slot.innerHTML = SolaryaDocFields.html('', 0, doc, { tripDate });
                            const block = slot.querySelector('[data-doc-block]');
                            if (block && block.querySelector('[data-doc-province]')?.value) SolaryaDocFields.loadComuniInto(block);
                        })();
                        @endunless
                    @endforeach
                    SolaryaDocFields.wire(document);
                })();
                </script>
                @endpush
            @endif

            {{-- Addons --}}
            @if ($booking->addons->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-bag-plus me-2 text-primary"></i>Servizi extra</h5>
                        <ul class="list-group list-group-flush">
                            @foreach ($booking->addons as $a)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 {{ $a->cancelled_at ? 'text-muted' : '' }}">
                                    <div>
                                        <div class="fw-semibold {{ $a->cancelled_at ? 'text-decoration-line-through' : '' }}">
                                            {{ $a->addon?->name ?? 'Addon' }}
                                            @if ($a->cancelled_at)<span class="badge bg-danger-subtle text-danger ms-1">Disdetto</span>@endif
                                        </div>
                                        <div class="small text-muted">Q.tà {{ $a->quantity }} × {{ $fmtMoney($a->unit_price ?? 0) }}</div>
                                    </div>
                                    <div class="fw-semibold">{{ $fmtMoney($a->total_price ?? (($a->unit_price ?? 0) * ($a->quantity ?? 1))) }}</div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Pagamenti --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Pagamenti</h5>
                    @if ($booking->payments->isEmpty())
                        <div class="text-muted">Nessun pagamento registrato.</div>
                        @if ($booking->checkout_url)
                            <a href="{{ $booking->checkout_url }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Apri link Stripe
                            </a>
                        @endif
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Gateway</th>
                                        <th>Riferimento</th>
                                        <th>Stato</th>
                                        <th class="text-end">Importo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->payments as $p)
                                        @php
                                            $pStatus = $p->status?->value ?? (string) $p->status;
                                            $pColor = match ($pStatus) {
                                                'succeeded' => 'success',
                                                'pending', 'processing' => 'warning',
                                                'failed' => 'danger',
                                                'refunded', 'partially_refunded' => 'dark',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $p->paid_at?->format('d/m/Y H:i') ?? $p->created_at?->format('d/m/Y H:i') }}</td>
                                            <td class="text-none">{{ $p->gateway }}</td>
                                            <td><code class="small">{{ $p->gateway_payment_id ?? '—' }}</code></td>
                                            <td><span class="badge bg-{{ $pColor }}-subtle text-{{ $pColor }}">{{ $pStatus }}</span></td>
                                            <td class="text-end fw-semibold">{{ $fmtMoney($p->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar riepilogo --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-3 sticky-lg-top" style="top: 1rem;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Riepilogo</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotale tour</span>
                        <span class="fw-semibold">{{ $fmtMoney($booking->base_price) }}</span>
                    </div>
                    @if ((float) $booking->addons_total > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Servizi extra</span>
                            <span class="fw-semibold">{{ $fmtMoney($booking->addons_total) }}</span>
                        </div>
                    @endif
                    @if ((float) $booking->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>
                                Sconto
                                @if ($booking->discountCode)
                                    <span class="small text-muted">({{ $booking->discountCode->code }})</span>
                                @endif
                            </span>
                            <span class="fw-semibold">-{{ $fmtMoney($booking->discount_amount) }}</span>
                        </div>
                    @endif
                    @if ((float) $booking->tax_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tasse</span>
                            <span class="fw-semibold">{{ $fmtMoney($booking->tax_amount) }}</span>
                        </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="fw-bold">Totale</span>
                        <span class="fw-bold fs-5 text-primary">{{ $fmtMoney($booking->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Timeline</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2">
                            <i class="bi bi-circle-fill text-muted me-2" style="font-size:.5rem;"></i>
                            Creata: <strong>{{ $booking->created_at?->format('d/m/Y H:i') }}</strong>
                        </li>
                        @if ($booking->payment_link_sent_at)
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-info me-2" style="font-size:.5rem;"></i>
                                Link pagamento inviato: <strong>{{ $booking->payment_link_sent_at->format('d/m/Y H:i') }}</strong>
                            </li>
                        @endif
                        @if ($booking->confirmed_at)
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-success me-2" style="font-size:.5rem;"></i>
                                Confermata: <strong>{{ $booking->confirmed_at->format('d/m/Y H:i') }}</strong>
                            </li>
                        @endif
                        @if ($booking->tickets_sent_at)
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-success me-2" style="font-size:.5rem;"></i>
                                Biglietti inviati: <strong>{{ $booking->tickets_sent_at->format('d/m/Y H:i') }}</strong>
                            </li>
                        @endif
                        @if ($booking->checked_in_at)
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-info me-2" style="font-size:.5rem;"></i>
                                Check-in: <strong>{{ $booking->checked_in_at->format('d/m/Y H:i') }}</strong>
                            </li>
                        @endif
                        @if ($booking->cancelled_at)
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-danger me-2" style="font-size:.5rem;"></i>
                                Annullata: <strong>{{ $booking->cancelled_at->format('d/m/Y H:i') }}</strong>
                                @if ($booking->cancellation_reason)
                                    <div class="text-muted ms-3">{{ $booking->cancellation_reason }}</div>
                                @endif
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-envelope me-2 text-primary"></i>Comunicazioni</h6>
                    <form action="{{ route('admin.bookings.resend', $booking) }}" method="POST" class="d-grid gap-2">
                        @csrf
                        <button class="btn btn-outline-primary rounded-pill fw-semibold">
                            <i class="bi bi-send me-2"></i>Reinvia conferma
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal annulla --}}
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST" class="modal-content rounded-4 border-0">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Annulla prenotazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $refundCalc = app(\App\Services\PaymentService::class)->calculateRefundAmount($booking);
                        $paidAmount = (float) ($refundCalc['paid'] ?? 0);
                    @endphp
                    <p class="text-muted">Indica il motivo dell'annullamento. Verrà registrato nello storico e inviato per email al cliente.</p>
                    <textarea name="reason" rows="3" class="form-control rounded-3 mb-3" required maxlength="500"
                              placeholder="Es. richiesta del cliente, maltempo…"></textarea>

                    @if($paidAmount > 0)
                        <label class="form-label fw-semibold small">Rimborso al cliente</label>
                        <div class="small text-muted mb-2">Versato: € {{ number_format($paidAmount, 2, ',', '.') }}</div>
                        <div class="d-flex flex-column gap-2 mb-2">
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="penalty" class="form-check-input mt-1" checked>
                                <span class="small">Applica penale ({{ $refundCalc['percentage'] }}%) → rimborsa <strong>€ {{ number_format((float) $refundCalc['amount'], 2, ',', '.') }}</strong>
                                    <span class="text-muted d-block">{{ $refundCalc['days_until'] }} giorni alla partenza</span></span>
                            </label>
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="full" class="form-check-input mt-1">
                                <span class="small">Rimborso totale → <strong>€ {{ number_format($paidAmount, 2, ',', '.') }}</strong></span>
                            </label>
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="custom" class="form-check-input mt-1" id="cancelCustomRadio">
                                <span class="small flex-grow-1">Importo personalizzato
                                    <span class="input-group input-group-sm mt-1" style="max-width:200px">
                                        <span class="input-group-text">€</span>
                                        <input type="number" step="0.01" min="0" max="{{ number_format($paidAmount, 2, '.', '') }}"
                                               name="refund_amount" class="form-control" placeholder="0,00"
                                               onfocus="document.getElementById('cancelCustomRadio').checked=true">
                                    </span>
                                </span>
                            </label>
                            <label class="d-flex align-items-start gap-2">
                                <input type="radio" name="refund_mode" value="none" class="form-check-input mt-1">
                                <span class="small">Nessun rimborso</span>
                            </label>
                        </div>
                        <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Per i pagamenti con carta il rimborso è eseguito su Stripe; per i bonifici va effettuato manualmente.</p>
                    @else
                        <input type="hidden" name="refund_mode" value="none">
                        <p class="small text-muted mb-0">Nessun importo risulta versato: non verrà eseguito alcun rimborso.</p>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Indietro</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3 fw-semibold">
                        <i class="bi bi-x-lg me-2"></i>Annulla prenotazione
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal rimborso --}}
    <div class="modal fade" id="refundBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.bookings.refund', $booking) }}" method="POST" class="modal-content rounded-4 border-0">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Registra rimborso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Marca la prenotazione come rimborsata e invia una mail al cliente con i dettagli.
                        L'accredito sulla carta va effettuato a parte (gestionale Stripe / banca).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Importo rimborsato</label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input type="number" step="0.01" min="0" max="{{ number_format((float) $booking->total_amount, 2, '.', '') }}"
                                   name="amount" class="form-control" value="{{ number_format((float) $booking->total_amount, 2, '.', '') }}">
                        </div>
                        <small class="text-muted">Totale prenotazione: € {{ number_format((float) $booking->total_amount, 2, ',', '.') }}</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Nota (opzionale)</label>
                        <textarea name="note" rows="2" class="form-control rounded-3" maxlength="500"
                                  placeholder="Es. rimborso parziale per spese di gestione…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Indietro</button>
                    <button type="submit" class="btn btn-info text-white rounded-pill px-3 fw-semibold">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Registra rimborso
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
