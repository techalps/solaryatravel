@extends('layouts.admin')

@section('title', 'Nuovo utente')

@section('content')
    <div class="dash-page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.users.index') }}" class="dash-icon-btn" title="Torna agli utenti">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="mb-0">Nuovo utente</h1>
                <p class="mt-1 mb-0">Crea un nuovo account utente e assegna un ruolo.</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 mb-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Si sono verificati alcuni errori:</strong>
            </div>
            <ul class="mb-0 ps-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="row g-3">
            {{-- LEFT --}}
            <div class="col-lg-8">
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-person-vcard me-2 text-primary"></i>Dati personali</h3>
                    </div>
                    <div class="dash-card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                       class="form-control border-start-0 @error('name') is-invalid @enderror"
                                       placeholder="es. Mario Rossi">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="nome@esempio.com">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label for="phone" class="form-label fw-semibold">Telefono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           placeholder="+39 ...">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label for="tax_code" class="form-label fw-semibold">Codice fiscale</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                    <input type="text" id="tax_code" name="tax_code" maxlength="16" value="{{ old('tax_code') }}"
                                           class="form-control @error('tax_code') is-invalid @enderror" style="text-transform:uppercase"
                                           placeholder="RSSMRA80A01H501U">
                                    @error('tax_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label for="date_of_birth" class="form-label fw-semibold">Data di nascita</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                           max="{{ now()->subDay()->format('Y-m-d') }}"
                                           class="form-control @error('date_of_birth') is-invalid @enderror">
                                    @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-key me-2 text-primary"></i>Password</h3>
                    </div>
                    <div class="dash-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" name="password" required minlength="8"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Min. 8 caratteri">
                                    <button type="button" class="btn btn-light border" onclick="togglePwd('password', this)" title="Mostra/nascondi">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold">Conferma password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                                           class="form-control" placeholder="Ripeti password">
                                </div>
                            </div>
                        </div>
                        <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Lunghezza minima: 8 caratteri.</div>
                    </div>
                </div>
            </div>

            {{-- RIGHT --}}
            <div class="col-lg-4">
                <div style="position:sticky; top:1rem">
                    <div class="dash-card mb-3">
                        <div class="dash-card-header">
                            <h3><i class="bi bi-shield-lock me-2 text-primary"></i>Ruolo</h3>
                        </div>
                        <div class="dash-card-body">
                            <div class="d-flex flex-column gap-2">
                                @php
                                    $roleConfig = [
                                        'system_admin' => ['icon' => 'bi-cpu-fill', 'desc' => 'Tecnico: poteri super admin + log, deploy e migrazioni', 'color' => 'dark'],
                                        'super_admin' => ['icon' => 'bi-shield-fill-check', 'desc' => 'Accesso completo a tutte le funzionalità', 'color' => 'warning'],
                                        'admin'       => ['icon' => 'bi-shield-fill',       'desc' => 'Gestisce prenotazioni e contenuti', 'color' => 'info'],
                                        'b2b'         => ['icon' => 'bi-briefcase-fill',    'desc' => 'Agenzia rivenditrice: prenota dal portale, riceve una provvigione', 'color' => 'primary'],
                                        'skipper'     => ['icon' => 'bi-qr-code-scan',   'desc' => 'Solo sezione Imbarco: scansiona i QR dei biglietti a bordo', 'color' => 'success'],
                                        'customer'    => ['icon' => 'bi-person-fill',       'desc' => 'Cliente standard della piattaforma', 'color' => 'secondary'],
                                    ];
                                @endphp
                                @foreach($roles as $value => $label)
                                    @php $rc = $roleConfig[$value] ?? ['icon' => 'bi-person', 'desc' => '', 'color' => 'secondary']; @endphp
                                    <label class="border rounded-3 p-3 d-flex gap-2 align-items-start"
                                           style="cursor:pointer; transition:all .15s"
                                           onmouseover="this.style.background='rgba(2,132,199,.04)'"
                                           onmouseout="this.style.background=''">
                                        <input type="radio" name="role" value="{{ $value }}"
                                               class="form-check-input mt-1 flex-shrink-0"
                                               {{ old('role', 'customer') === $value ? 'checked' : '' }} required>
                                        <span class="rounded-circle bg-{{ $rc['color'] }}-subtle text-{{ $rc['color'] }} d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                              style="width:36px; height:36px">
                                            <i class="bi {{ $rc['icon'] }}"></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="fw-semibold d-block">{{ $label }}</span>
                                            <span class="small text-muted">{{ $rc['desc'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('role') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Dati agenzia: visibili solo per il ruolo b2b --}}
                    <div class="dash-card mb-3" id="b2bFields" style="{{ old('role') === 'b2b' ? '' : 'display:none' }}">
                        <div class="dash-card-header">
                            <h3><i class="bi bi-briefcase me-2 text-primary"></i>Dati agenzia</h3>
                        </div>
                        <div class="dash-card-body">
                            <div class="mb-3">
                                <label for="agency_name" class="form-label fw-semibold">Ragione sociale <span class="text-danger">*</span></label>
                                <input type="text" id="agency_name" name="agency_name" value="{{ old('agency_name') }}"
                                       class="form-control @error('agency_name') is-invalid @enderror" placeholder="es. Valerio Agency">
                                @error('agency_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-1">
                                <label for="commission_rate" class="form-label fw-semibold">Provvigione % <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="commission_rate" name="commission_rate" value="{{ old('commission_rate') }}"
                                           step="0.01" min="0" max="100"
                                           class="form-control @error('commission_rate') is-invalid @enderror" placeholder="es. 20">
                                    <span class="input-group-text">%</span>
                                    @error('commission_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text"><i class="bi bi-info-circle me-1"></i>% sul totale (IVA inclusa) di ogni prenotazione dell'agenzia.</div>
                            </div>

                            {{-- Vedi edit.blade.php: concessione per singola agenzia. --}}
                            <hr class="my-3">
                            <div class="mb-1">
                                <input type="hidden" name="can_book_complimentary" value="0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" role="switch"
                                           id="can_book_complimentary" name="can_book_complimentary" value="1"
                                           {{ old('can_book_complimentary') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="can_book_complimentary">
                                        Prenotazioni a 0€ (posti omaggio)
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>Se attivo, l'agenzia può registrare ospiti
                                    a 0€ dal proprio portale. I posti occupano la barca e non generano provvigioni.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-card mt-3">
            <div class="dash-card-body d-flex justify-content-end gap-2 flex-wrap">
                <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-pill px-4 fw-semibold">
                    Annulla
                </a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                    <i class="bi bi-person-plus me-2"></i>Crea utente
                </button>
            </div>
        </div>
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

    // Mostra i campi agenzia solo quando si seleziona il ruolo b2b.
    (function () {
        const box = document.getElementById('b2bFields');
        if (!box) return;
        document.querySelectorAll('input[name="role"]').forEach(r => {
            r.addEventListener('change', () => {
                box.style.display = (document.querySelector('input[name="role"]:checked')?.value === 'b2b') ? '' : 'none';
            });
        });
    })();
</script>
@endpush
