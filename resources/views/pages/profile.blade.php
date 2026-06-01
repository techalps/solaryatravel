@extends('layouts.public')

@section('title', 'Il mio profilo – Solarya Travel')

@section('content')

{{-- HERO --}}
<div class="tg-breadcrumb-area pt-150 pb-90 p-relative"
     style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 text-white">
                <h1 class="mb-1 wow fadeInUp">Il mio profilo</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Profilo</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="py-5" style="background:#f8fafc;min-height:50vh">
    <div class="container">
        <div class="row g-4" margin:0 auto">

            {{-- SIDEBAR --}}
            <div class="col-lg-3">
                <div class="pf-sidebar">
                    {{-- Avatar --}}
                    <div class="pf-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="pf-sidebar-name">{{ auth()->user()->name ?? 'Utente' }}</div>
                    <div class="pf-sidebar-email">{{ auth()->user()->email }}</div>

                    <nav class="pf-sidebar-nav">
                        <a href="{{ route('profile') }}" class="pf-nav-link pf-nav-active">
                            <i class="fa-regular fa-user"></i>Dati personali
                        </a>
                        <a href="{{ route('bookings.my') }}" class="pf-nav-link">
                            <i class="fa-regular fa-calendar-check"></i>Prenotazioni
                        </a>
                        <a href="{{ route('profile') }}#sicurezza" class="pf-nav-link">
                            <i class="fa-solid fa-lock"></i>Sicurezza
                        </a>
                    </nav>
                </div>
            </div>

            {{-- MAIN --}}
            <div class="col-lg-9">

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="pf-alert pf-alert-success mb-4">
                        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if(session('success_password'))
                    <div class="pf-alert pf-alert-success mb-4">
                        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success_password') }}
                    </div>
                @endif
                @if($errors->any() && !$errors->has('current_password') && !$errors->has('password'))
                    <div class="pf-alert pf-alert-danger mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Controlla i dati inseriti: {{ $errors->first() }}
                    </div>
                @endif

                {{-- DATI PERSONALI --}}
                <div class="pf-card mb-4">
                    <div class="pf-card-header">
                        <div class="pf-card-icon"><i class="fa-regular fa-user"></i></div>
                        <div>
                            <h2 class="pf-card-title">Dati personali</h2>
                            <p class="pf-card-subtitle">Nome, email e recapiti associati al tuo account.</p>
                        </div>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="pf-label" for="pf-name">Nome completo <span class="text-danger">*</span></label>
                                <input type="text" id="pf-name" name="name" class="pf-input @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="pf-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="pf-label" for="pf-email">Email <span class="text-danger">*</span></label>
                                <input type="email" id="pf-email" name="email" class="pf-input @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="pf-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="pf-label" for="pf-phone">Telefono</label>
                                <input type="tel" id="pf-phone" name="phone" class="pf-input @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}" placeholder="+39 000 000 0000">
                                @error('phone')
                                    <div class="pf-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="pf-label" for="pf-dob">Data di nascita</label>
                                <input type="date" id="pf-dob" name="date_of_birth" class="pf-input @error('date_of_birth') is-invalid @enderror"
                                       value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                       max="{{ now()->subDay()->format('Y-m-d') }}">
                                @error('date_of_birth')
                                    <div class="pf-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <button type="submit" class="pf-btn pf-btn-primary">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>Salva modifiche
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- SICUREZZA --}}
                <div class="pf-card" id="sicurezza">
                    <div class="pf-card-header">
                        <div class="pf-card-icon pf-card-icon-secondary"><i class="fa-solid fa-lock"></i></div>
                        <div>
                            <h2 class="pf-card-title">Sicurezza</h2>
                            <p class="pf-card-subtitle">Aggiorna la tua password per mantenere l'account al sicuro.</p>
                        </div>
                    </div>

                    @if($errors->has('current_password') || $errors->has('password'))
                        <div class="pf-alert pf-alert-danger mb-4">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('profile.password') }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="pf-label" for="pf-current">Password attuale <span class="text-danger">*</span></label>
                                <input type="password" id="pf-current" name="current_password" class="pf-input @error('current_password') is-invalid @enderror"
                                       autocomplete="current-password" required>
                                @error('current_password')
                                    <div class="pf-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="pf-label" for="pf-new">Nuova password <span class="text-danger">*</span></label>
                                <input type="password" id="pf-new" name="password" class="pf-input @error('password') is-invalid @enderror"
                                       autocomplete="new-password" required>
                                <small class="pf-hint">Minimo 8 caratteri.</small>
                                @error('password')
                                    <div class="pf-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="pf-label" for="pf-confirm">Conferma nuova password <span class="text-danger">*</span></label>
                                <input type="password" id="pf-confirm" name="password_confirmation" class="pf-input"
                                       autocomplete="new-password" required>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="pf-btn pf-btn-secondary">
                                    <i class="fa-solid fa-key me-2"></i>Aggiorna password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>

@endsection

@push('head')
<style>
    /* Sidebar */
    .pf-sidebar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 28px 20px 22px;
        text-align: center;
        position: sticky;
        top: 100px;
    }
    .pf-avatar {
        width: 72px; height: 72px;
        border-radius: 50%;
        background: var(--tg-theme-primary);
        color: #fff;
        font-size: 1.8rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }
    .pf-sidebar-name { font-weight: 700; font-size: 1rem; color: #0E1B33; margin-bottom: 2px; }
    .pf-sidebar-email { font-size: .8rem; color: #64748b; margin-bottom: 20px; word-break: break-all; }

    .pf-sidebar-nav { display: flex; flex-direction: column; gap: 4px; text-align: left; }
    .pf-nav-link {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 14px;
        border-radius: 10px;
        font-size: .88rem;
        font-weight: 600;
        text-decoration: none;
        color: #475569;
        transition: background .15s, color .15s;
    }
    .pf-nav-link:hover { background: #f1f5f9; color: #0E1B33; }
    .pf-nav-link.pf-nav-active { background: color-mix(in srgb, var(--tg-theme-primary) 10%, #fff); color: var(--tg-theme-primary); }
    .pf-nav-link i { width: 16px; text-align: center; flex-shrink: 0; }

    /* Cards */
    .pf-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 28px 28px 24px;
    }
    .pf-card-header {
        display: flex; align-items: flex-start; gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
    }
    .pf-card-icon {
        width: 44px; height: 44px; flex-shrink: 0;
        border-radius: 12px;
        background: color-mix(in srgb, var(--tg-theme-primary) 12%, #fff);
        color: var(--tg-theme-primary);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .pf-card-icon-secondary {
        background: color-mix(in srgb, var(--tg-theme-secondary) 12%, #fff);
        color: var(--tg-theme-secondary);
    }
    .pf-card-title { font-size: 1.1rem; font-weight: 700; color: #0E1B33; margin: 0 0 3px; }
    .pf-card-subtitle { font-size: .84rem; color: #64748b; margin: 0; }

    /* Form */
    .pf-label { font-size: .8rem; font-weight: 600; color: #0E1B33; margin-bottom: 6px; display: block; }
    .pf-input {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: 50px;
        padding: .55rem 1rem;
        font-size: .9rem;
        background: #fff;
        color: #0E1B33;
        transition: border-color .15s, box-shadow .15s;
        outline: none;
    }
    .pf-input:focus {
        border-color: var(--tg-theme-primary);
        box-shadow: 0 0 0 3px rgba(0,69,96,.12);
    }
    .pf-input.is-invalid { border-color: #dc3545; }
    .pf-input[type="date"] { border-radius: 14px; }
    .pf-field-error { font-size: .78rem; color: #dc3545; margin-top: 4px; }
    .pf-hint { font-size: .77rem; color: #94a3b8; display: block; margin-top: 4px; }

    /* Buttons */
    .pf-btn {
        display: inline-flex; align-items: center;
        padding: 10px 24px;
        border-radius: 50px;
        font-size: .9rem;
        font-weight: 600;
        border: 0;
        cursor: pointer;
        transition: filter .15s;
        text-decoration: none;
    }
    .pf-btn-primary { background: var(--tg-theme-primary); color: #fff; }
    .pf-btn-primary:hover { filter: brightness(.88); color: #fff; }
    .pf-btn-secondary { background: var(--tg-theme-secondary); color: #fff; }
    .pf-btn-secondary:hover { filter: brightness(.88); color: #fff; }

    /* Alerts */
    .pf-alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: .88rem;
        font-weight: 500;
    }
    .pf-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .pf-alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
</style>
@endpush
