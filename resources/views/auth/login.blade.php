@extends('layouts.auth')

@section('title', 'Accedi — Solarya Travel')

@push('head')
<style>
    .auth-wrap {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 16px 60px;
        background: linear-gradient(rgba(0,0,0,.52), rgba(0,0,0,.52)),
                    url('{{ asset('assets/template/img/hero/hero-1.jpg') }}') center/cover no-repeat;
        background-attachment: fixed;
    }

    .auth-card {
        width: 100%;
        max-width: 440px;
        background: #fff;
        border-radius: 20px;
        padding: 44px 40px 36px;
        box-shadow: 0 24px 64px rgba(0,0,0,.18);
    }

    @media (max-width: 480px) {
        .auth-card { padding: 36px 24px 28px; }
    }

    .auth-card-logo {
        display: block;
        margin: 0 auto 28px;
        height: 30px;
        width: auto;
    }

    .auth-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 22px 0;
    }
    .auth-divider::before,
    .auth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }
    .auth-divider span { font-size: .78rem; color: #94a3b8; white-space: nowrap; }

    .auth-field {
        position: relative;
        margin-bottom: 16px;
    }
    .auth-field-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
    }
    .auth-field input {
        width: 100%;
        padding: 13px 16px 13px 42px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: .95rem;
        color: #0E1B33;
        background: #f8fafc;
        outline: none;
        transition: border-color .2s, background .2s;
    }
    .auth-field input:focus {
        border-color: var(--tg-theme-primary);
        background: #fff;
    }
    .auth-field input::placeholder { color: #b0bec5; }

    .auth-submit {
        width: 100%;
        padding: 14px;
        background: var(--tg-theme-primary);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: .01em;
        cursor: pointer;
        transition: background .2s, transform .1s;
        margin-top: 4px;
    }
    .auth-submit:hover { background: var(--tg-theme-secondary); }
    .auth-submit:active { transform: scale(.98); }

    .auth-guest-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        text-decoration: none;
        color: #0E1B33;
        font-size: .9rem;
        font-weight: 600;
        transition: border-color .2s, color .2s;
    }
    .auth-guest-link:hover {
        border-color: var(--tg-theme-secondary);
        color: var(--tg-theme-secondary);
    }

    .auth-alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        border-radius: 10px;
        padding: 11px 15px;
        margin-bottom: 18px;
        font-size: .88rem;
    }
    .auth-alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        border-radius: 10px;
        padding: 11px 15px;
        margin-bottom: 18px;
        font-size: .88rem;
    }
</style>
@endpush

@section('content')
<div class="auth-wrap">
    <a href="{{ route('home') }}" style="position:fixed;top:24px;left:24px;z-index:10;display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.3);border-radius:999px;color:#fff;text-decoration:none;font-size:.88rem;font-weight:600;transition:background .2s" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
        <i class="fa-solid fa-arrow-left"></i> Torna al sito
    </a>
    <div class="auth-card">

        <a href="{{ route('home') }}">
            <img src="{{ asset('images/logo_black.svg') }}" alt="Solarya Travel" class="auth-card-logo">
        </a>

        <h2 style="font-size:1.55rem;font-weight:800;color:#0E1B33;margin-bottom:4px;text-align:center">Bentornato</h2>
        <p style="color:#64748b;font-size:.92rem;text-align:center;margin-bottom:26px">Accedi al tuo account Solarya</p>

        @if(session('status'))
            <div class="auth-alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="auth-alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="auth-field">
                <i class="fa-regular fa-envelope auth-field-icon"></i>
                <input type="email" name="email" placeholder="Indirizzo email"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="auth-field">
                <i class="fa-solid fa-lock auth-field-icon"></i>
                <input type="password" name="password" placeholder="Password"
                       required autocomplete="current-password">
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.88rem;color:#64748b">
                    <input type="checkbox" name="remember"
                           style="width:15px;height:15px;accent-color:var(--tg-theme-primary);cursor:pointer">
                    Ricordami
                </label>
                <a href="{{ route('password.request') }}"
                   style="font-size:.88rem;color:var(--tg-theme-primary);text-decoration:none;font-weight:600">
                    Password dimenticata?
                </a>
            </div>

            <button type="submit" class="auth-submit">
                Accedi &nbsp;<i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <p style="text-align:center;font-size:.88rem;color:#64748b;margin-top:20px;margin-bottom:0">
            Non hai un account?
            <a href="{{ route('register') }}"
               style="color:var(--tg-theme-primary);font-weight:700;text-decoration:none">
                Registrati
            </a>
        </p>

        <div class="auth-divider"><span>oppure</span></div>

        <a href="{{ route('booking.start') }}" class="auth-guest-link">
            <i class="fa-regular fa-calendar-check" style="color:var(--tg-theme-secondary)"></i>
            Prenota senza registrazione
        </a>

    </div>
</div>
@endsection
