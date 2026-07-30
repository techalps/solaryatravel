@extends('layouts.admin')

@section('title', 'Modifica '.$user->name)

@php
    $roleConfig = [
        'system_admin' => ['icon' => 'bi-cpu-fill', 'class' => 'bg-dark-subtle text-dark border-dark', 'desc' => 'Tecnico: poteri super admin + log, deploy e migrazioni', 'color' => 'dark'],
        'super_admin' => ['icon' => 'bi-shield-fill-check', 'class' => 'bg-warning-subtle text-warning border-warning', 'desc' => 'Accesso completo a tutte le funzionalità', 'color' => 'warning'],
        'admin'       => ['icon' => 'bi-shield-fill',       'class' => 'bg-info-subtle text-info border-info',         'desc' => 'Gestisce prenotazioni e contenuti', 'color' => 'info'],
        'b2b'         => ['icon' => 'bi-briefcase-fill',    'class' => 'bg-primary-subtle text-primary border-primary', 'desc' => 'Agenzia rivenditrice: prenota dal portale, riceve una provvigione', 'color' => 'primary'],
        'skipper'     => ['icon' => 'bi-qr-code-scan',      'class' => 'bg-success-subtle text-success border-success', 'desc' => 'Solo sezione Imbarco: scansiona i QR dei biglietti a bordo', 'color' => 'success'],
        'customer'    => ['icon' => 'bi-person-fill',       'class' => 'bg-light text-secondary border',               'desc' => 'Cliente standard della piattaforma', 'color' => 'secondary'],
    ];
    $rc = $roleConfig[$user->role] ?? $roleConfig['customer'];
@endphp

@section('content')
    <div class="dash-page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.users.index') }}" class="dash-icon-btn" title="Torna agli utenti">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0 {{ $rc['class'] }} d-none d-md-inline-flex"
                      style="width:52px; height:52px; border-width:2px; border-style:solid; font-size:1.25rem">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div>
                    <h1 class="mb-0">{{ $user->name }}</h1>
                    <p class="mt-1 mb-0"><i class="bi bi-envelope me-1"></i>{{ $user->email }}</p>
                </div>
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

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

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
                                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                       class="form-control border-start-0 @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                           class="form-control @error('email') is-invalid @enderror">
                                    @if($user->email_verified_at)
                                        <span class="input-group-text bg-success-subtle text-success border-success-subtle" title="Email verificata">
                                            <i class="bi bi-patch-check-fill"></i>
                                        </span>
                                    @endif
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label for="phone" class="form-label fw-semibold">Telefono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                           class="form-control @error('phone') is-invalid @enderror">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label for="tax_code" class="form-label fw-semibold">Codice fiscale</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                    <input type="text" id="tax_code" name="tax_code" maxlength="16"
                                           value="{{ old('tax_code', $user->tax_code) }}"
                                           class="form-control @error('tax_code') is-invalid @enderror" style="text-transform:uppercase">
                                    @error('tax_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label for="date_of_birth" class="form-label fw-semibold">Data di nascita</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                    <input type="date" id="date_of_birth" name="date_of_birth"
                                           value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
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
                        <h3><i class="bi bi-key me-2 text-primary"></i>Cambia password</h3>
                        <span class="small text-muted">Opzionale</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="alert alert-info border-0 d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-info-circle-fill"></i>
                            <small class="mb-0">Lascia vuoto per mantenere la password attuale.</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Nuova password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" name="password" minlength="8"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Min. 8 caratteri">
                                    <button type="button" class="btn btn-light border" onclick="togglePwd('password', this)" title="Mostra/nascondi">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold">Conferma password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                                           class="form-control" placeholder="Ripeti password">
                                </div>
                            </div>
                        </div>
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
                                @foreach($roles as $value => $label)
                                    @php $rcr = $roleConfig[$value] ?? ['icon' => 'bi-person', 'desc' => '', 'color' => 'secondary']; @endphp
                                    <label class="border rounded-3 p-3 d-flex gap-2 align-items-start"
                                           style="cursor:pointer; transition:all .15s"
                                           onmouseover="this.style.background='rgba(2,132,199,.04)'"
                                           onmouseout="this.style.background=''">
                                        <input type="radio" name="role" value="{{ $value }}"
                                               class="form-check-input mt-1 flex-shrink-0"
                                               {{ old('role', $user->role) === $value ? 'checked' : '' }} required
                                               {{ $user->id === auth()->id() && $user->role === 'super_admin' && $value !== 'super_admin' ? 'disabled' : '' }}>
                                        <span class="rounded-circle bg-{{ $rcr['color'] }}-subtle text-{{ $rcr['color'] }} d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                              style="width:36px; height:36px">
                                            <i class="bi {{ $rcr['icon'] }}"></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="fw-semibold d-block">{{ $label }}</span>
                                            <span class="small text-muted">{{ $rcr['desc'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('role') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Dati agenzia: visibili solo per il ruolo b2b --}}
                    <div class="dash-card mb-3" id="b2bFields" style="{{ old('role', $user->role) === 'b2b' ? '' : 'display:none' }}">
                        <div class="dash-card-header">
                            <h3><i class="bi bi-briefcase me-2 text-primary"></i>Dati agenzia</h3>
                        </div>
                        <div class="dash-card-body">
                            <div class="mb-3">
                                <label for="agency_name" class="form-label fw-semibold">Ragione sociale <span class="text-danger">*</span></label>
                                <input type="text" id="agency_name" name="agency_name" value="{{ old('agency_name', $user->agency_name) }}"
                                       class="form-control @error('agency_name') is-invalid @enderror" placeholder="es. Valerio Agency">
                                @error('agency_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-1">
                                <label for="commission_rate" class="form-label fw-semibold">Provvigione % <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="commission_rate" name="commission_rate"
                                           value="{{ old('commission_rate', $user->commission_rate !== null ? rtrim(rtrim(number_format((float) $user->commission_rate, 2, '.', ''), '0'), '.') : '') }}"
                                           step="0.01" min="0" max="100"
                                           class="form-control @error('commission_rate') is-invalid @enderror" placeholder="es. 20">
                                    <span class="input-group-text">%</span>
                                    @error('commission_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text"><i class="bi bi-info-circle me-1"></i>% sul totale (IVA inclusa). Modificarla NON cambia le commissioni già maturate (snapshot sulla prenotazione).</div>
                            </div>

                            {{-- Prenotazioni a 0€: concessione per SINGOLA agenzia, non
                                 un diritto del ruolo b2b. Stessa funzione dei posti
                                 omaggio dell'admin, ma va abilitata qui una per una. --}}
                            <hr class="my-3">
                            <div class="mb-1">
                                <input type="hidden" name="can_book_complimentary" value="0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" role="switch"
                                           id="can_book_complimentary" name="can_book_complimentary" value="1"
                                           {{ old('can_book_complimentary', $user->can_book_complimentary) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="can_book_complimentary">
                                        Prenotazioni a 0€ (posti omaggio)
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>Se attivo, questa agenzia può registrare
                                    ospiti a 0€ dal proprio portale, come fa l'admin. I posti occupano comunque la
                                    barca e su di essi non maturano provvigioni.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dash-card mb-3">
                        <div class="dash-card-header">
                            <h3><i class="bi bi-info-circle me-2 text-primary"></i>Informazioni</h3>
                        </div>
                        <div class="dash-card-body">
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted"><i class="bi bi-calendar-plus me-2"></i>Registrato</span>
                                <span class="text-dark">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted"><i class="bi bi-calendar-check me-2"></i>Aggiornato</span>
                                <span class="text-dark">{{ $user->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 small">
                                <span class="text-muted"><i class="bi bi-patch-check me-2"></i>Email</span>
                                @if($user->email_verified_at)
                                    <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Verificata</span>
                                @else
                                    <span class="text-warning fw-semibold"><i class="bi bi-hourglass-split me-1"></i>In attesa</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-card mt-3">
            <div class="dash-card-body d-flex justify-content-between gap-2 flex-wrap">
                @if($user->id !== auth()->id())
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 fw-semibold"
                            data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                        <i class="bi bi-trash me-2"></i>Elimina utente
                    </button>
                @else
                    <span></span>
                @endif
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-pill px-4 fw-semibold">
                        Annulla
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check2 me-2"></i>Salva modifiche
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if($user->id !== auth()->id())
        {{-- Delete modal --}}
        <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius:1rem">
                    <div class="modal-body text-center p-4">
                        <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center"
                             style="width:72px; height:72px">
                            <i class="bi bi-exclamation-triangle fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Eliminare {{ $user->name }}?</h4>
                        <p class="text-muted mb-0">L'operazione è irreversibile. Tutti i dati associati verranno persi.</p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 border" data-bs-dismiss="modal">Annulla</button>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">
                                <i class="bi bi-trash me-2"></i>Elimina definitivamente
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
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
