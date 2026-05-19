<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Solarya Travel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,600,700|inter:300,400,500,600,700" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body style="background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 50%, #ffffff 100%); min-height: 100vh;">
    <div class="d-flex flex-column align-items-center justify-content-center px-3 py-5 min-vh-100">

        <a href="{{ route('home') }}" class="text-decoration-none mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center bg-gradient-primary rounded-3 shadow" style="width:48px;height:48px">
                    <i class="bi bi-water text-white fs-4"></i>
                </span>
                <div class="lh-1">
                    <span class="d-block font-serif fs-3 fw-semibold text-navy">Solarya</span>
                    <small class="text-warning text-uppercase" style="letter-spacing:.2em;font-size:.7rem">Travel</small>
                </div>
            </div>
        </a>

        <div class="card shadow-lg border-0 w-100" style="max-width: 28rem; border-radius: 1rem;">
            <div class="card-body p-4 p-md-5">
                @yield('content')
                {{ $slot ?? '' }}
            </div>
        </div>

        <a href="{{ route('home') }}" class="mt-4 small text-muted text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-2"></i>Torna alla Home
        </a>
    </div>

    @livewireScripts
</body>
</html>
