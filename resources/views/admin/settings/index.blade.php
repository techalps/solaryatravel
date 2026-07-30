@extends('layouts.admin')

@section('title', 'Impostazioni')

@section('content')
    {{-- Page header --}}
    <div class="dash-page-header">
        <div>
            <h1>Impostazioni</h1>
            <p><i class="bi bi-gear me-1"></i>Configurazione generale del sistema</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" form="settingsForm"
                    class="btn btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>Salva impostazioni
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 d-flex align-items-center gap-2 mb-3"
             style="background:rgba(16,185,129,.1); color:#059669">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
        @csrf

        <div class="row g-3">
            {{-- Side navigation --}}
            <div class="col-lg-3">
                <div class="position-sticky" style="top:1rem">
                    <div class="dash-card">
                        <div class="dash-card-body p-2">
                            <div class="nav nav-pills flex-column gap-1" role="tablist" id="settingsNav">
                                <a class="nav-link active d-flex align-items-center gap-2 fw-semibold"
                                   data-bs-toggle="pill" href="#sec-general" role="tab">
                                    <i class="bi bi-building"></i>Generale
                                </a>
                                <a class="nav-link d-flex align-items-center gap-2 fw-semibold"
                                   data-bs-toggle="pill" href="#sec-locales" role="tab">
                                    <i class="bi bi-translate"></i>Lingue
                                </a>
                                <a class="nav-link d-flex align-items-center gap-2 fw-semibold"
                                   data-bs-toggle="pill" href="#sec-booking" role="tab">
                                    <i class="bi bi-receipt"></i>Prenotazioni
                                </a>
                                <a class="nav-link d-flex align-items-center gap-2 fw-semibold"
                                   data-bs-toggle="pill" href="#sec-stripe" role="tab">
                                    <i class="bi bi-stripe"></i>Stripe
                                </a>
                                <a class="nav-link d-flex align-items-center gap-2 fw-semibold"
                                   data-bs-toggle="pill" href="#sec-email" role="tab">
                                    <i class="bi bi-envelope-at"></i>Email SMTP
                                </a>
                                <a class="nav-link d-flex align-items-center gap-2 fw-semibold"
                                   data-bs-toggle="pill" href="#sec-system" role="tab">
                                    <i class="bi bi-sliders"></i>Sistema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sections --}}
            <div class="col-lg-9">
                <div class="tab-content">
                    {{-- General --}}
                    <div class="tab-pane fade show active" id="sec-general" role="tabpanel">
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-building me-2 text-primary"></i>Informazioni generali</h3>
                            </div>
                            <div class="dash-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-globe me-1"></i>Nome sito
                                        </label>
                                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}"
                                               class="form-control @error('site_name') is-invalid @enderror" required>
                                        @error('site_name') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-envelope me-1"></i>Email
                                        </label>
                                        <input type="email" name="site_email" value="{{ old('site_email', $settings['site_email']) }}"
                                               class="form-control @error('site_email') is-invalid @enderror" required>
                                        @error('site_email') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-bell me-1"></i>Email notifiche admin
                                        </label>
                                        <input type="email" name="admin_notification_email" value="{{ old('admin_notification_email', $settings['admin_notification_email'] ?? '') }}"
                                               class="form-control @error('admin_notification_email') is-invalid @enderror"
                                               placeholder="Lascia vuoto per usare l'email del sito">
                                        <div class="small text-muted mt-1">Riceve avvisi di nuove prenotazioni, cancellazioni e rimborsi.</div>
                                        @error('admin_notification_email') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-telephone me-1"></i>Telefono
                                        </label>
                                        <input type="text" name="site_phone" value="{{ old('site_phone', $settings['site_phone'] ?? '') }}"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-geo-alt me-1"></i>Indirizzo
                                        </label>
                                        <input type="text" name="site_address" value="{{ old('site_address', $settings['site_address'] ?? '') }}"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-briefcase me-1"></i>Ragione sociale
                                        </label>
                                        <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-card-text me-1"></i>P.IVA
                                        </label>
                                        <input type="text" name="vat_number" value="{{ old('vat_number', $settings['vat_number'] ?? '') }}"
                                               class="form-control font-monospace">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Booking --}}
                    {{-- Lingue --}}
                    <div class="tab-pane fade" id="sec-locales" role="tabpanel">
                        {{-- Lingue del sito pubblico --}}
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-translate me-2 text-primary"></i>Lingue del sito</h3>
                            </div>
                            <div class="dash-card-body">
                                @php
                                    $defaultLocale = \App\Support\Locales::default();
                                    $activeLocales = old('active_locales', \App\Support\Locales::active());
                                @endphp
                                <p class="text-muted small mb-3">
                                    Le lingue attive compaiono nel selettore del sito. L'italiano è sempre
                                    attivo: è la lingua principale e quella mostrata quando una traduzione manca.
                                    I testi dei tour e degli extra si traducono dalle rispettive schede.
                                </p>
                                <div class="row g-2">
                                    @foreach (\App\Support\Locales::available() as $loc)
                                        @php
                                            $isDefault = $loc === $defaultLocale;
                                            $checked = $isDefault || in_array($loc, (array) $activeLocales, true);
                                            $hasUi = is_dir(lang_path($loc));
                                        @endphp
                                        <div class="col-md-6 col-lg-4">
                                            <label class="d-flex align-items-start gap-2 p-3 border rounded-3 h-100 {{ $checked ? 'border-primary' : '' }}"
                                                   style="cursor:{{ $isDefault ? 'default' : 'pointer' }};{{ $checked ? 'background:rgba(13,110,253,.04)' : '' }}">
                                                <input type="checkbox" name="active_locales[]" value="{{ $loc }}"
                                                       class="form-check-input mt-1 flex-shrink-0"
                                                       {{ $checked ? 'checked' : '' }}
                                                       {{ $isDefault ? 'disabled' : '' }}>
                                                {{-- La default è disabilitata ma va inviata: hidden dedicato. --}}
                                                @if ($isDefault)
                                                    <input type="hidden" name="active_locales[]" value="{{ $loc }}">
                                                @endif
                                                <span class="min-w-0">
                                                    <span class="fw-semibold d-block">
                                                        {{ \App\Support\Locales::name($loc) }}
                                                        <span class="text-muted small">({{ \App\Support\Locales::short($loc) }})</span>
                                                    </span>
                                                    @if ($isDefault)
                                                        <span class="badge bg-primary-subtle text-primary mt-1">Lingua principale</span>
                                                    @elseif (! $hasUi)
                                                        {{-- Senza il file di interfaccia menù e bottoni resterebbero
                                                             in italiano: meglio dirlo prima di attivarla. --}}
                                                        <span class="badge bg-warning-subtle text-warning mt-1">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>Interfaccia non tradotta
                                                        </span>
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="alert alert-light border mt-3 mb-0 small">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>
                                    Attivando una lingua senza interfaccia tradotta, i testi dei tour che avrai
                                    inserito appariranno nella nuova lingua, ma menù e bottoni resteranno in
                                    italiano. Segnalacelo e completiamo la traduzione dell'interfaccia.
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="tab-pane fade" id="sec-booking" role="tabpanel">
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-receipt me-2 text-primary"></i>Impostazioni prenotazioni</h3>
                            </div>
                            <div class="dash-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-currency-exchange me-1"></i>Valuta
                                        </label>
                                        <select name="currency" class="form-select">
                                            <option value="EUR" {{ old('currency', $settings['currency']) === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                            <option value="USD" {{ old('currency', $settings['currency']) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                            <option value="GBP" {{ old('currency', $settings['currency']) === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-percent me-1"></i>Aliquota IVA
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="tax_rate" step="0.01" min="0" max="100"
                                                   value="{{ old('tax_rate', $settings['tax_rate']) }}"
                                                   class="form-control" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-people me-1"></i>Posti default
                                        </label>
                                        <input type="number" name="default_seats" min="1" max="50"
                                               value="{{ old('default_seats', $settings['default_seats']) }}"
                                               class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-calendar-plus me-1"></i>Prenotazione anticipata
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="booking_advance_days" min="0" max="365"
                                                   value="{{ old('booking_advance_days', $settings['booking_advance_days']) }}"
                                                   class="form-control" required>
                                            <span class="input-group-text">giorni</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-x-circle me-1"></i>Cancellazione gratuita
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="cancellation_hours" min="0" max="168"
                                                   value="{{ old('cancellation_hours', $settings['cancellation_hours']) }}"
                                                   class="form-control" required>
                                            <span class="input-group-text">ore</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-hourglass-split me-1"></i>Scadenza pagamento carta
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="payment_deadline_minutes" min="5" max="1440"
                                                   value="{{ old('payment_deadline_minutes', $settings['payment_deadline_minutes']) }}"
                                                   class="form-control" required>
                                            <span class="input-group-text">min</span>
                                        </div>
                                        <small class="text-muted" style="font-size:.75rem">
                                            Minuti per pagare dall'apertura del checkout. Scaduti, la prenotazione
                                            si annulla e i posti tornano in vendita.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Penali di storno --}}
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-percent me-2 text-primary"></i>Penali di cancellazione</h3>
                            </div>
                            <div class="dash-card-body">
                                <p class="small text-muted mb-3">Percentuale di rimborso applicata quando il cliente annulla, in base ai giorni di anticipo rispetto alla partenza. Il rimborso è calcolato sull'importo effettivamente versato.</p>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Oltre N giorni prima → rimborso</label>
                                        <div class="input-group">
                                            <span class="input-group-text">oltre</span>
                                            <input type="number" name="cancel_penalty_days_1" min="0" max="365"
                                                   value="{{ old('cancel_penalty_days_1', $settings['cancel_penalty_days_1'] ?? 14) }}" class="form-control" required>
                                            <span class="input-group-text">gg →</span>
                                            <input type="number" name="cancel_penalty_refund_1" min="0" max="100"
                                                   value="{{ old('cancel_penalty_refund_1', $settings['cancel_penalty_refund_1'] ?? 70) }}" class="form-control" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Tra N e fascia successiva → rimborso</label>
                                        <div class="input-group">
                                            <span class="input-group-text">oltre</span>
                                            <input type="number" name="cancel_penalty_days_2" min="0" max="365"
                                                   value="{{ old('cancel_penalty_days_2', $settings['cancel_penalty_days_2'] ?? 7) }}" class="form-control" required>
                                            <span class="input-group-text">gg →</span>
                                            <input type="number" name="cancel_penalty_refund_2" min="0" max="100"
                                                   value="{{ old('cancel_penalty_refund_2', $settings['cancel_penalty_refund_2'] ?? 50) }}" class="form-control" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Sotto la soglia minima → rimborso</label>
                                        <div class="input-group">
                                            <input type="number" name="cancel_penalty_refund_under" min="0" max="100"
                                                   value="{{ old('cancel_penalty_refund_under', $settings['cancel_penalty_refund_under'] ?? 0) }}" class="form-control" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Acconto e bonifico --}}
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-cash-coin me-2 text-primary"></i>Modalità di pagamento</h3>
                            </div>
                            <div class="dash-card-body">
                                {{-- Toggle acconto --}}
                                <label class="d-flex align-items-start gap-3 p-3 border rounded-3 mb-3"
                                       style="background: rgba(124,55,255,.04); border-color: rgba(124,55,255,.2)!important">
                                    <div class="form-check form-switch m-0 pt-1">
                                        <input type="checkbox" name="deposit_enabled" value="1"
                                               class="form-check-input" role="switch" style="width:2.5rem; height:1.4rem"
                                               {{ old('deposit_enabled', $settings['deposit_enabled'] ?? false) ? 'checked' : '' }}>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark"><i class="bi bi-wallet2 me-1 text-primary"></i>Consenti pagamento con acconto</div>
                                        <div class="small text-muted">Il cliente può scegliere di versare un acconto per confermare la prenotazione, e saldare il resto entro le ore indicate prima della partenza.</div>
                                    </div>
                                </label>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-percent me-1"></i>Percentuale acconto</label>
                                        <div class="input-group">
                                            <input type="number" name="deposit_percentage" min="1" max="99"
                                                   value="{{ old('deposit_percentage', $settings['deposit_percentage'] ?? 50) }}" class="form-control" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-hourglass-bottom me-1"></i>Saldo entro</label>
                                        <div class="input-group">
                                            {{-- Settings::balanceDueDays() converte l'eventuale vecchio
                                                 valore in ore, così un'installazione già configurata
                                                 non perde l'impostazione al primo caricamento. --}}
                                            <input type="number" name="balance_due_days" min="1" max="90"
                                                   value="{{ old('balance_due_days', $settings['balance_due_days'] ?? \App\Support\Settings::balanceDueDays()) }}" class="form-control" required>
                                            <span class="input-group-text">giorni prima</span>
                                        </div>
                                        <div class="form-text small">Il cliente vede questo termine nell'avviso quando sceglie l'acconto.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-calendar-check me-1"></i>Acconto solo da</label>
                                        <div class="input-group">
                                            <input type="number" name="deposit_min_days" min="0" max="365"
                                                   value="{{ old('deposit_min_days', $settings['deposit_min_days'] ?? 7) }}" class="form-control" required>
                                            <span class="input-group-text">giorni prima</span>
                                        </div>
                                        <div class="form-text small">Sotto questa soglia il cliente paga l'intero importo: l'opzione acconto non viene mostrata. 0 = sempre disponibile.</div>
                                    </div>
                                </div>

                                {{-- Toggle bonifico istantaneo --}}
                                <label class="d-flex align-items-start gap-3 p-3 border rounded-3 mb-3"
                                       style="background: rgba(16,185,129,.04); border-color: rgba(16,185,129,.2)!important">
                                    <div class="form-check form-switch m-0 pt-1">
                                        <input type="checkbox" name="bank_transfer_enabled" value="1"
                                               class="form-check-input" role="switch" style="width:2.5rem; height:1.4rem"
                                               {{ old('bank_transfer_enabled', $settings['bank_transfer_enabled'] ?? false) ? 'checked' : '' }}>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark"><i class="bi bi-bank me-1 text-success"></i>Consenti pagamento con bonifico istantaneo</div>
                                        <div class="small text-muted">La prenotazione resta in attesa di pagamento (posti riservati) finché un amministratore non conferma l'incasso. Se non viene confermata entro la scadenza, scade automaticamente e i posti tornano disponibili.</div>
                                    </div>
                                </label>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-hourglass-split me-1"></i>Scadenza prenotazione</label>
                                        <div class="input-group">
                                            <input type="number" name="bank_transfer_expiry_hours" min="1" max="168"
                                                   value="{{ old('bank_transfer_expiry_hours', $settings['bank_transfer_expiry_hours'] ?? 24) }}" class="form-control" required>
                                            <span class="input-group-text">ore</span>
                                        </div>
                                        <small class="text-muted">Tempo per confermare l'incasso prima che i posti tornino disponibili.</small>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-card-text me-1"></i>Coordinate bancarie (IBAN, intestatario)</label>
                                    <textarea name="bank_transfer_details" rows="3" class="form-control"
                                              placeholder="Intestatario: Solarya Travel S.r.l.&#10;IBAN: IT00 X000 0000 0000 0000 0000 000&#10;Causale: numero prenotazione">{{ old('bank_transfer_details', $settings['bank_transfer_details'] ?? '') }}</textarea>
                                    <small class="text-muted">Mostrate al cliente che sceglie il bonifico istantaneo e incluse nell'email. La causale consigliata è il numero di prenotazione.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Minimo partecipanti --}}
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-people me-2 text-primary"></i>Minimo partecipanti</h3>
                            </div>
                            <div class="dash-card-body">
                                <p class="small text-muted">Avviso mostrato al cliente in pagina escursione, al checkout e nelle email di conferma.</p>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-person-check me-1"></i>Minimo partecipanti</label>
                                        <input type="number" name="min_participants" min="1" max="50"
                                               value="{{ old('min_participants', $settings['min_participants'] ?? 6) }}" class="form-control" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-clock-history me-1"></i>Termine di verifica</label>
                                        <input type="text" name="min_participants_deadline_label" maxlength="120"
                                               value="{{ old('min_participants_deadline_label', $settings['min_participants_deadline_label'] ?? '48 ore prima della partenza') }}"
                                               class="form-control" placeholder="48 ore prima della partenza">
                                    </div>
                                </div>
                                <div class="alert border-0 rounded-3 small mb-0" style="background:rgba(2,132,199,.08); color:#0369a1">
                                    <i class="bi bi-eye me-1"></i>Anteprima:
                                    <em>{{ \App\Support\Settings::minParticipantsNotice() }}</em>
                                </div>
                            </div>
                        </div>

                        {{-- Orario limite di prenotazione (globale) --}}
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-hourglass-split me-2 text-primary"></i>Orario limite di prenotazione</h3>
                            </div>
                            <div class="dash-card-body">
                                <p class="small text-muted">Fino a che orario del <strong>giorno precedente</strong> si può prenotare una partenza. Vale su sito, portale agenzie e widget. Questo è il valore <strong>globale</strong>; puoi sovrascriverlo per singolo tour qui sotto.</p>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-clock me-1"></i>Orario limite (giorno prima)</label>
                                        <input type="time" name="booking_cutoff_time"
                                               value="{{ old('booking_cutoff_time', $settings['booking_cutoff_time'] ?? '22:00') }}" class="form-control" required>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="alert border-0 rounded-3 small mb-0" style="background:rgba(2,132,199,.08); color:#0369a1">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Esempio: <strong>22:00</strong> → una partenza del 10/07 è prenotabile fino alle 22:00 del 09/07.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Orario limite per singolo tour (form separato: tourCutoffsForm) --}}
                        <div class="dash-card mb-3">
                            <div class="dash-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h3 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Orario limite per tour</h3>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="time" name="apply_all_time" form="tourCutoffsForm" class="form-control form-control-sm" style="width:auto" title="Orario da applicare a tutti">
                                    <button type="submit" form="tourCutoffsForm" class="btn btn-sm btn-outline-primary"
                                            onclick="return confirm('Applicare questo orario a TUTTI i tour attivi?')">
                                        <i class="bi bi-check2-all me-1"></i>Applica a tutti
                                    </button>
                                </div>
                            </div>
                            <div class="dash-card-body">
                                <p class="small text-muted">Lascia vuoto un tour per usare l'orario <strong>globale</strong> qui sopra. Un orario specifico lo sovrascrive solo per quel tour.</p>
                                @if($activeTours->isEmpty())
                                    <div class="alert alert-light border small mb-0">Nessun tour attivo.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-3">
                                            <thead class="table-light">
                                                <tr class="small text-uppercase text-muted">
                                                    <th>Tour</th>
                                                    <th style="width:200px">Orario limite (giorno prima)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($activeTours as $t)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $t->name }}</td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <input type="time" name="cutoff[{{ $t->id }}]" form="tourCutoffsForm"
                                                                       value="{{ $t->booking_cutoff_time ? substr($t->booking_cutoff_time, 0, 5) : '' }}"
                                                                       class="form-control" placeholder="usa globale">
                                                                <span class="input-group-text text-muted small">{{ $t->booking_cutoff_time ? 'specifico' : 'globale' }}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <button type="submit" form="tourCutoffsForm" class="btn btn-primary btn-sm">
                                            <i class="bi bi-save me-1"></i>Salva orari per tour
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stripe --}}
                    <div class="tab-pane fade" id="sec-stripe" role="tabpanel">
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-stripe me-2 text-primary"></i>Configurazione Stripe</h3>
                            </div>
                            <div class="dash-card-body">
                                <div class="alert border-0 rounded-3 d-flex gap-2 mb-3"
                                     style="background:rgba(2,132,199,.08); color:#0369a1">
                                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                                    <div class="small">
                                        Recupera le chiavi dal
                                        <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener" class="fw-semibold text-primary">
                                            Dashboard Stripe <i class="bi bi-box-arrow-up-right"></i>
                                        </a>.
                                        Le chiavi segrete non vengono mostrate per sicurezza.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary mb-1">
                                        <i class="bi bi-key me-1"></i>Chiave pubblica (Publishable Key)
                                    </label>
                                    <input type="text" name="stripe_public_key"
                                           value="{{ old('stripe_public_key', $settings['stripe_public_key'] ?? '') }}"
                                           placeholder="pk_live_..."
                                           class="form-control font-monospace small">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary mb-1">
                                        <i class="bi bi-shield-lock me-1"></i>Chiave segreta (Secret Key)
                                    </label>
                                    <div class="input-group">
                                        <input type="password" name="stripe_secret_key" id="stripeSecret"
                                               value="{{ old('stripe_secret_key', $settings['stripe_secret_key'] ?? '') }}"
                                               placeholder="sk_live_..."
                                               class="form-control font-monospace small">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('stripeSecret', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small fw-semibold text-secondary mb-1">
                                        <i class="bi bi-link-45deg me-1"></i>Webhook secret
                                    </label>
                                    <div class="input-group">
                                        <input type="password" name="stripe_webhook_secret" id="stripeWebhook"
                                               value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret'] ?? '') }}"
                                               placeholder="whsec_..."
                                               class="form-control font-monospace small">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('stripeWebhook', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Email SMTP --}}
                    <div class="tab-pane fade" id="sec-email" role="tabpanel">
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-envelope-at me-2 text-primary"></i>Configurazione email (SMTP)</h3>
                            </div>
                            <div class="dash-card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-hdd-network me-1"></i>Host SMTP
                                        </label>
                                        <input type="text" name="smtp_host"
                                               value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}"
                                               placeholder="smtp.example.com" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-ethernet me-1"></i>Porta
                                        </label>
                                        <input type="number" name="smtp_port" min="1" max="65535"
                                               value="{{ old('smtp_port', $settings['smtp_port'] ?? 587) }}"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-person me-1"></i>Username
                                        </label>
                                        <input type="text" name="smtp_username"
                                               value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-shield-lock me-1"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="smtp_password" id="smtpPwd"
                                                   value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}"
                                                   class="form-control">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('smtpPwd', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-shield-check me-1"></i>Crittografia
                                        </label>
                                        <select name="smtp_encryption" class="form-select">
                                            <option value="tls" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-pencil-square me-1"></i>Nome mittente
                                        </label>
                                        <input type="text" name="mail_from_name"
                                               value="{{ old('mail_from_name', $settings['mail_from_name'] ?? '') }}"
                                               placeholder="Solarya Travel" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">
                                            <i class="bi bi-at me-1"></i>Email mittente
                                        </label>
                                        <input type="email" name="mail_from_address"
                                               value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}"
                                               placeholder="noreply@solaryatravel.com" class="form-control">
                                    </div>
                                </div>

                                {{-- SMTP dedicato alle notifiche admin --}}
                                <div class="mt-4 pt-3 border-top">
                                    <h5 class="fw-bold mb-1"><i class="bi bi-bell me-2 text-primary"></i>SMTP notifiche admin</h5>
                                    <p class="small text-muted mb-3">SMTP separato per le email che il sistema invia agli amministratori (nuova prenotazione, cancellazione, rimborso). Se lasciato vuoto, vengono usate le impostazioni SMTP principali qui sopra.</p>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-hdd-network me-1"></i>Host SMTP</label>
                                            <input type="text" name="admin_smtp_host"
                                                   value="{{ old('admin_smtp_host', $settings['admin_smtp_host'] ?? '') }}"
                                                   placeholder="ssl0.ovh.net" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-ethernet me-1"></i>Porta</label>
                                            <input type="number" name="admin_smtp_port" min="1" max="65535"
                                                   value="{{ old('admin_smtp_port', $settings['admin_smtp_port'] ?? 587) }}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-person me-1"></i>Username</label>
                                            <input type="text" name="admin_smtp_username"
                                                   value="{{ old('admin_smtp_username', $settings['admin_smtp_username'] ?? '') }}"
                                                   placeholder="sistema@solaryatravel.com" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-key me-1"></i>Password</label>
                                            <input type="password" name="admin_smtp_password"
                                                   value="{{ old('admin_smtp_password', $settings['admin_smtp_password'] ?? '') }}"
                                                   class="form-control" autocomplete="new-password">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-shield-check me-1"></i>Crittografia</label>
                                            <select name="admin_smtp_encryption" class="form-select">
                                                <option value="tls" {{ old('admin_smtp_encryption', $settings['admin_smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ old('admin_smtp_encryption', $settings['admin_smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-pencil-square me-1"></i>Nome mittente (notifiche)</label>
                                            <input type="text" name="admin_mail_from_name"
                                                   value="{{ old('admin_mail_from_name', $settings['admin_mail_from_name'] ?? '') }}"
                                                   placeholder="Solarya Travel · Sistema" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-at me-1"></i>Email mittente (notifiche)</label>
                                            <input type="email" name="admin_mail_from_address"
                                                   value="{{ old('admin_mail_from_address', $settings['admin_mail_from_address'] ?? '') }}"
                                                   placeholder="sistema@solaryatravel.com" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top">
                                    <h5 class="fw-bold mb-2"><i class="bi bi-send-check me-2 text-success"></i>Test invio email</h5>
                                    <p class="small text-muted mb-3">Salva prima le impostazioni qui sopra (pulsante "Salva" in fondo), poi torna qui per inviare una mail di prova.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <input type="email" id="mailTestTo" class="form-control" style="max-width:320px"
                                               value="{{ auth()->user()->email }}" placeholder="destinatario@example.com">
                                        <button type="button" id="mailTestBtn" class="btn btn-success">
                                            <i class="bi bi-send me-1"></i>Invia mail di prova
                                        </button>
                                    </div>
                                    <div id="mailTestResult" class="mt-3 small"></div>
                                </div>

                                @push('scripts')
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const btn = document.getElementById('mailTestBtn');
                                        if (!btn) return;
                                        btn.addEventListener('click', async () => {
                                            const to = document.getElementById('mailTestTo').value.trim();
                                            const result = document.getElementById('mailTestResult');
                                            if (!to) {
                                                result.innerHTML = '<div class="alert alert-warning py-2 mb-0">Inserisci un indirizzo email.</div>';
                                                return;
                                            }
                                            btn.disabled = true;
                                            const original = btn.innerHTML;
                                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Invio…';
                                            result.innerHTML = '';
                                            try {
                                                const fd = new FormData();
                                                fd.append('to', to);
                                                fd.append('_token', '{{ csrf_token() }}');
                                                const r = await fetch('{{ route('admin.settings.mail-test') }}', {
                                                    method: 'POST',
                                                    body: fd,
                                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                                });
                                                const data = await r.json();
                                                if (r.ok && data.success) {
                                                    result.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i>' + data.message + '</div>';
                                                } else {
                                                    result.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-x-circle me-1"></i>' + (data.message || 'Errore sconosciuto') + '</div>';
                                                }
                                            } catch (e) {
                                                result.innerHTML = '<div class="alert alert-danger py-2 mb-0">Errore di rete: ' + e.message + '</div>';
                                            } finally {
                                                btn.disabled = false;
                                                btn.innerHTML = original;
                                            }
                                        });
                                    });
                                </script>
                                @endpush
                            </div>
                        </div>
                    </div>

                    {{-- System --}}
                    <div class="tab-pane fade" id="sec-system" role="tabpanel">
                        <div class="dash-card mb-3">
                            <div class="dash-card-header">
                                <h3><i class="bi bi-sliders me-2 text-primary"></i>Opzioni sistema</h3>
                            </div>
                            <div class="dash-card-body">
                                <label class="d-flex align-items-start gap-3 p-3 border rounded-3 mb-3"
                                       style="background: rgba(16,185,129,.04); border-color: rgba(16,185,129,.2)!important">
                                    <div class="form-check form-switch m-0 pt-1">
                                        <input type="checkbox" name="enable_notifications" value="1"
                                               class="form-check-input" role="switch" style="width:2.5rem; height:1.4rem"
                                               {{ old('enable_notifications', $settings['enable_notifications'] ?? true) ? 'checked' : '' }}>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            <i class="bi bi-bell-fill text-success me-1"></i>Abilita notifiche email
                                        </div>
                                        <div class="small text-muted">Invia email di conferma prenotazione e promemoria automatici ai clienti.</div>
                                    </div>
                                </label>

                                <label class="d-flex align-items-start gap-3 p-3 border rounded-3"
                                       style="background: rgba(239,68,68,.04); border-color: rgba(239,68,68,.2)!important">
                                    <div class="form-check form-switch m-0 pt-1">
                                        <input type="checkbox" name="maintenance_mode" value="1"
                                               class="form-check-input" role="switch" style="width:2.5rem; height:1.4rem"
                                               {{ old('maintenance_mode', $settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            <i class="bi bi-cone-striped text-danger me-1"></i>Modalità manutenzione
                                        </div>
                                        <div class="small text-muted">Il sito pubblico mostrerà una pagina di manutenzione. Solo gli amministratori potranno accedere.</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom actions --}}
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i>Salva impostazioni
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Form separato per gli orari limite per-tour (non annidabile nel form
         principale). Gli input stanno nel tab "Prenotazioni" via attributo form=. --}}
    <form action="{{ route('admin.settings.tour-cutoffs') }}" method="POST" id="tourCutoffsForm">
        @csrf
    </form>
@endsection

@push('scripts')
<script>
    function togglePwd(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    // Ricorda la scheda (tab) attiva tra un salvataggio e l'altro: dopo il POST
    // la pagina si ricarica e senza questo tornerebbe sempre alla prima scheda.
    (function () {
        var KEY = 'settingsActiveTab';
        var tabs = document.querySelectorAll('[data-bs-toggle="pill"][href^="#sec-"]');

        // Ripristina la scheda salvata al caricamento.
        var saved = sessionStorage.getItem(KEY);
        if (saved && document.querySelector(saved)) {
            tabs.forEach(function (t) {
                var isTarget = t.getAttribute('href') === saved;
                t.classList.toggle('active', isTarget);
                var pane = document.querySelector(t.getAttribute('href'));
                if (pane) pane.classList.toggle('show', isTarget), pane.classList.toggle('active', isTarget);
            });
            // Disattiva la prima scheda (che era active di default) se non è quella salvata.
            document.querySelectorAll('.tab-pane').forEach(function (p) {
                if ('#' + p.id !== saved) { p.classList.remove('show', 'active'); }
            });
        }

        // Memorizza la scheda quando l'utente la cambia.
        tabs.forEach(function (t) {
            t.addEventListener('shown.bs.tab', function () {
                sessionStorage.setItem(KEY, t.getAttribute('href'));
            });
            // Fallback: alcuni tema/versioni non emettono shown.bs.tab con gli <a>.
            t.addEventListener('click', function () {
                sessionStorage.setItem(KEY, t.getAttribute('href'));
            });
        });
    })();
</script>
@endpush
