<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accesso Agenzie — Solarya Travel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Google+Sans+Display:wght@400;500;700&family=Google+Sans+Text:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <style>
        body { background: #0f172a; }
        .b2b-login-wrap {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 48px 16px;
            background: radial-gradient(1200px 600px at 50% -10%, rgba(250,204,21,.10), transparent 60%), #0f172a;
        }
        .b2b-login-card {
            width: 100%; max-width: 420px;
            background: #fff; border-radius: 18px;
            padding: 40px 36px 32px;
            box-shadow: 0 24px 64px rgba(0,0,0,.45);
        }
        .b2b-login-logo { display:block; height: 26px; width:auto; margin: 0 auto 8px; }
        .b2b-login-badge {
            display:block; text-align:center; text-transform:uppercase;
            letter-spacing:.18em; font-size:.65rem; font-weight:600;
            color:#b45309; margin-bottom: 24px;
        }
    </style>
</head>
<body>
<div class="b2b-login-wrap">
    <div class="b2b-login-card">
        <img src="{{ asset('images/logo_black.svg') }}" alt="Solarya Travel" class="b2b-login-logo">
        <span class="b2b-login-badge">Portale Agenzie</span>

        <h2 class="h4 fw-bold text-center mb-1" style="color:#0f172a">Accesso riservato</h2>
        <p class="text-center text-muted small mb-4">Area dedicata alle agenzie partner</p>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 small">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('b2b.login.attempt') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                       required autofocus autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Password</label>
                <input type="password" name="password" class="form-control"
                       required autocomplete="current-password">
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small text-secondary" for="remember">Ricordami</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Accedi <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
    </div>
</div>
</body>
</html>
