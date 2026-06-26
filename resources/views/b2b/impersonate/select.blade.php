<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scegli agenzia — Solarya Agenzie</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Google+Sans+Display:wght@400;500;700&family=Google+Sans+Text:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <style>
        body { background: #0f172a; }
        .imp-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:48px 16px;
            background: radial-gradient(1200px 600px at 50% -10%, rgba(250,204,21,.10), transparent 60%), #0f172a; }
        .imp-card { width:100%; max-width:560px; background:#fff; border-radius:18px; padding:36px 32px; box-shadow:0 24px 64px rgba(0,0,0,.45); }
        .imp-logo { display:block; height:24px; width:auto; margin:0 auto 6px; }
        .imp-badge { display:block; text-align:center; text-transform:uppercase; letter-spacing:.18em; font-size:.65rem; font-weight:600; color:#b45309; margin-bottom:20px; }
        .imp-agency { display:flex; align-items:center; gap:12px; width:100%; text-align:left; padding:14px 16px; border:1.5px solid #e2e8f0; border-radius:12px; background:#f8fafc; cursor:pointer; transition:border-color .15s, background .15s; }
        .imp-agency:hover { border-color:#eab308; background:#fffbeb; }
        .imp-avatar { width:42px; height:42px; flex-shrink:0; border-radius:10px; background:linear-gradient(135deg,#facc15,#eab308); color:#0f172a; font-weight:700; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body>
<div class="imp-wrap">
    <div class="imp-card">
        <img src="{{ asset('images/logo_black.svg') }}" alt="Solarya Travel" class="imp-logo">
        <span class="imp-badge">Portale Agenzie · Modalità Admin</span>

        <h2 class="h4 fw-bold text-center mb-1" style="color:#0f172a">Per conto di quale agenzia operi?</h2>
        <p class="text-center text-muted small mb-4">
            Sei {{ auth()->user()->name }} ({{ auth()->user()->role === 'system_admin' ? 'System Admin' : 'Super Admin' }}).
            Le prenotazioni create risulteranno dell'agenzia selezionata.
        </p>

        @if($agencies->isEmpty())
            <div class="alert alert-warning small">Nessuna agenzia (ruolo b2b) presente. Creane una dall'area admin.</div>
        @else
            <div class="d-flex flex-column gap-2" style="max-height:50vh; overflow:auto">
                @foreach($agencies as $agency)
                    <form method="POST" action="{{ route('b2b.impersonate.store') }}">
                        @csrf
                        <input type="hidden" name="agency_id" value="{{ $agency->id }}">
                        <button type="submit" class="imp-agency">
                            <span class="imp-avatar">{{ strtoupper(substr($agency->agency_name ?: $agency->name, 0, 1)) }}</span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="d-block fw-semibold text-truncate" style="color:#0f172a">{{ $agency->agency_name ?: $agency->name }}</span>
                                <span class="d-block small text-muted text-truncate">{{ $agency->email }} · commissione {{ rtrim(rtrim(number_format($agency->commission_rate, 2), '0'), '.') }}%</span>
                            </span>
                            <i class="bi bi-arrow-right-circle text-warning fs-5"></i>
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

        <div class="text-center mt-4">
            <form method="POST" action="{{ route('b2b.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted text-decoration-none">
                    <i class="bi bi-box-arrow-right me-1"></i>Esci dal portale
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
