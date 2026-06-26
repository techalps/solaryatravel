@extends('layouts.admin')

@section('title', 'Utenti e ruoli')

@php
    $roleConfig = [
        'system_admin' => ['icon' => 'bi-cpu-fill',          'class' => 'bg-dark-subtle text-dark border-dark'],
        'super_admin' => ['icon' => 'bi-shield-fill-check', 'class' => 'bg-warning-subtle text-warning border-warning'],
        'admin'       => ['icon' => 'bi-shield-fill',       'class' => 'bg-info-subtle text-info border-info'],
        'b2b'         => ['icon' => 'bi-briefcase-fill',    'class' => 'bg-primary-subtle text-primary border-primary'],
        'customer'    => ['icon' => 'bi-person-fill',       'class' => 'bg-light text-secondary border'],
    ];
    $countByRole = $users->getCollection()->groupBy('role')->map->count();
    $verifiedCount = $users->getCollection()->whereNotNull('email_verified_at')->count();
@endphp

@section('content')
    {{-- Page header --}}
    <div class="dash-page-header">
        <div>
            <h1>Utenti e ruoli</h1>
            <p>{{ $users->total() }} {{ $users->total() === 1 ? 'utente registrato' : 'utenti registrati' }} sulla piattaforma.</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-person-plus me-2"></i>Nuovo utente
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Mini stats --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat is-active">
                <div class="dash-mini-stat-label"><i class="bi bi-people me-1"></i>Totale utenti</div>
                <div class="dash-mini-stat-value">{{ $users->total() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <div class="dash-mini-stat-label"><i class="bi bi-shield-fill-check me-1"></i>Super admin</div>
                <div class="dash-mini-stat-value text-warning">{{ $countByRole['super_admin'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <div class="dash-mini-stat-label"><i class="bi bi-shield-fill me-1"></i>Admin</div>
                <div class="dash-mini-stat-value text-info">{{ $countByRole['admin'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <div class="dash-mini-stat-label"><i class="bi bi-patch-check me-1"></i>Email verificate</div>
                <div class="dash-mini-stat-value text-success">{{ $verifiedCount }}</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="dash-filter-bar mb-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 w-100 align-items-center">
            <div class="col-12 col-md">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cerca per nome o email..."
                           class="form-control border-start-0">
                </div>
            </div>
            <div class="col-6 col-md-auto">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">Tutti i ruoli</option>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-funnel me-1"></i>Filtra
                </button>
                @if(request('search') || request('role'))
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-pill px-3">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="dash-card mb-4">
        <div class="table-responsive">
            <table class="dash-table mb-0">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Ruolo</th>
                        <th>Telefono</th>
                        <th>Registrato</th>
                        <th>Email</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php $rc = $roleConfig[$user->role] ?? $roleConfig['customer']; @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0 {{ $rc['class'] }}"
                                          style="width:42px; height:42px; border-width:2px; border-style:solid">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-dark">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                                <span class="badge bg-primary-subtle text-primary border-0 ms-1">Tu</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width:240px">
                                            <i class="bi bi-envelope me-1"></i>{{ $user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $rc['class'] }} border rounded-pill px-3 py-2 fw-semibold">
                                    <i class="bi {{ $rc['icon'] }} me-1"></i>{{ $roles[$user->role] ?? $user->role }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                @if($user->phone)
                                    <i class="bi bi-telephone me-1"></i>{{ $user->phone }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $user->created_at->locale('it')->isoFormat('D MMM YYYY') }}
                            </td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="status-pill s-confirmed"><i class="bi bi-patch-check-fill"></i>Verificata</span>
                                @else
                                    <span class="status-pill s-pending"><i class="bi bi-hourglass-split"></i>In attesa</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="dash-icon-btn is-primary" title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                                              onsubmit="return confirm('Eliminare l\'utente {{ addslashes($user->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dash-icon-btn is-danger" title="Elimina">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="dash-icon-btn opacity-25" title="Non puoi eliminare il tuo account">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="text-center py-5">
                                    <div class="mx-auto mb-3 rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                                         style="width:72px; height:72px">
                                        <i class="bi bi-people fs-2"></i>
                                    </div>
                                    <h3 class="h5 fw-bold mb-2">Nessun utente trovato</h3>
                                    <p class="text-muted mb-3">Prova a modificare i filtri o crea un nuovo utente.</p>
                                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                        <i class="bi bi-person-plus me-2"></i>Nuovo utente
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="dash-card-body">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
