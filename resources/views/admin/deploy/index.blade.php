@extends('layouts.admin')

@section('title', 'Deploy & Migrazioni')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1 fw-bold">Deploy & Migrazioni</h1>
        <p class="text-muted small mb-0">Gestisci le migrazioni del database e i comandi di deployment.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge rounded-pill {{ $env === 'production' ? 'bg-danger' : 'bg-success' }} px-3 py-2">
            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;vertical-align:middle"></i>
            {{ strtoupper($env) }}
        </span>
        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis px-3 py-2">
            PHP {{ $phpVersion }}
        </span>
        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis px-3 py-2">
            Laravel {{ $laravelVersion }}
        </span>
    </div>
</div>

{{-- Flash messages --}}
@if(session('deploy_success'))
    <div class="alert alert-success alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
        <div class="small">{!! session('deploy_success') !!}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('deploy_error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div class="small">{{ session('deploy_error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- ── MIGRAZIONI ── --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-database-fill-gear text-primary"></i>
                    <span class="fw-bold">Migrazioni Database</span>
                </div>
                @if($pendingCount > 0)
                    <span class="badge bg-warning text-dark rounded-pill">{{ $pendingCount }} pendenti</span>
                @else
                    <span class="badge bg-success rounded-pill">Tutto aggiornato</span>
                @endif
            </div>

            {{-- Run button --}}
            @if($pendingCount > 0)
                <div class="card-body pb-0">
                    <form method="POST" action="{{ route('admin.deploy.migrate') }}"
                          onsubmit="return confirm('Esegui {{ $pendingCount }} migrazione/i pendenti? Assicurati di avere un backup del database.')">
                        @csrf
                        <button type="submit" class="btn btn-warning fw-semibold w-100 mb-3">
                            <i class="bi bi-play-fill me-1"></i>
                            Esegui {{ $pendingCount }} migrazione/i pendenti
                        </button>
                    </form>
                </div>
            @endif

            {{-- Migration list --}}
            <div class="card-body pt-0" style="max-height:480px;overflow-y:auto">
                <table class="table table-sm table-hover align-middle mb-0">
                    <tbody>
                        @foreach($migrations as $m)
                            <tr>
                                <td style="width:32px">
                                    @if($m['ran'])
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-clock-fill text-warning"></i>
                                    @endif
                                </td>
                                <td class="font-monospace small text-truncate" style="max-width:360px" title="{{ $m['name'] }}">
                                    {{ $m['name'] }}
                                </td>
                                <td class="text-end">
                                    @if($m['ran'])
                                        <span class="badge bg-success-subtle text-success-emphasis">Eseguita</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Pendente</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── COMANDI ARTISAN ── --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                <i class="bi bi-terminal-fill text-primary"></i>
                <span class="fw-bold">Comandi Artisan</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Esegui i comandi più usati in fase di deploy.</p>

                <div class="d-flex flex-column gap-2">
                    @foreach($commands as $cmd => $desc)
                        @php
                            $isDestructive = str_ends_with($cmd, ':clear') || $cmd === 'optimize:clear';
                            $icon = match(true) {
                                str_starts_with($cmd, 'cache')    => 'bi-lightning-fill',
                                str_starts_with($cmd, 'config')   => 'bi-sliders',
                                str_starts_with($cmd, 'route')    => 'bi-signpost-split-fill',
                                str_starts_with($cmd, 'view')     => 'bi-eye-fill',
                                str_starts_with($cmd, 'optimize') => 'bi-rocket-takeoff-fill',
                                default => 'bi-terminal',
                            };
                        @endphp
                        <form method="POST" action="{{ route('admin.deploy.artisan') }}">
                            @csrf
                            <input type="hidden" name="command" value="{{ $cmd }}">
                            <button type="submit"
                                    class="btn btn-sm w-100 text-start d-flex align-items-center gap-2 {{ $isDestructive ? 'btn-outline-warning' : 'btn-outline-primary' }}">
                                <i class="bi {{ $icon }} flex-shrink-0"></i>
                                <div class="min-w-0">
                                    <div class="fw-semibold font-monospace" style="font-size:.8rem">{{ $cmd }}</div>
                                    <div class="text-muted" style="font-size:.75rem;font-weight:400">{{ $desc }}</div>
                                </div>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
