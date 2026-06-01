@extends('layouts.admin')

@section('title', 'Tour')

@section('content')
    @php
        $totalActive = $tours->where('is_active', true)->count();
        $totalInactive = $tours->where('is_active', false)->count();
        $totalDepartures = $tours->sum('departures_count');
        $totalBookings = $tours->sum('bookings_count');
    @endphp

    {{-- Page header --}}
    <div class="dash-page-header">
        <div>
            <h1>Tour</h1>
            <p>Gestisci i pacchetti tour, prezzi per fascia d'età, immagini e partenze.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.tours.create') }}" class="btn btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-plus-lg me-2"></i>Nuovo tour
            </a>
        </div>
    </div>

    {{-- Mini stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-primary-subtle text-primary"><i class="bi bi-compass"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $tours->total() }}</div>
                    <div class="mini-stat-label">Totale tour</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $totalActive }}</div>
                    <div class="mini-stat-label">Attivi</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-info-subtle text-info"><i class="bi bi-calendar-event"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $totalDepartures }}</div>
                    <div class="mini-stat-label">Partenze totali</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-success-subtle text-success"><i class="bi bi-journal-bookmark"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $totalBookings }}</div>
                    <div class="mini-stat-label">Prenotazioni</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtri --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cerca per nome...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tutti gli stati</option>
                        <option value="active" @selected(request('status') === 'active')>Attivi</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Non attivi</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Filtra</button>
                    <a href="{{ route('admin.tours.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tours grid --}}
    <div class="row g-3 mb-4">
        @forelse($tours as $tour)
            @php $primary = $tour->images->first(); @endphp
            <div class="col-sm-6 col-xl-4">
                <div class="cat-card">
                    {{-- Media --}}
                    <a href="{{ route('admin.tours.show', $tour) }}" class="cat-card-media text-decoration-none">
                        @if($primary)
                            <img src="{{ $primary->url }}" alt="{{ $tour->name }}">
                        @else
                            <div class="cat-media-placeholder"><i class="bi bi-image"></i></div>
                        @endif

                        <span class="cat-status-badge {{ $tour->is_active ? 'active' : 'inactive' }}">
                            {{ $tour->is_active ? 'Attivo' : 'Inattivo' }}
                        </span>

                        @if(($tour->bookings_count ?? 0) > 0)
                            <span class="cat-bookings-badge">
                                <i class="bi bi-journal-bookmark me-1"></i>{{ $tour->bookings_count }} prenotazioni
                            </span>
                        @endif
                    </a>

                    {{-- Body --}}
                    <div class="cat-card-body">
                        <h3>
                            <a href="{{ route('admin.tours.show', $tour) }}" class="text-decoration-none text-reset">
                                {{ $tour->name }}
                            </a>
                        </h3>

                        @if($tour->description_short)
                            <p class="small text-muted mb-2 text-truncate" title="{{ $tour->description_short }}">
                                {{ $tour->description_short }}
                            </p>
                        @endif

                        <div class="cat-meta">
                            @if($tour->duration_hours)
                                <span><i class="bi bi-clock text-primary"></i>{{ $tour->duration_hours }}h</span>
                            @endif
                            <span><i class="bi bi-people text-primary"></i>{{ $tour->min_capacity }}–{{ $tour->max_capacity ?? '∞' }} posti</span>
                            <span><i class="bi bi-calendar-event text-primary"></i>{{ $tour->departures_count }} partenze</span>
                        </div>

                        <div class="cat-prices">
                            <div class="cat-price-block">
                                <div class="cat-price-label">A partire da</div>
                                <div class="cat-price-value">
                                    @if($tour->price_from !== null)
                                        €{{ number_format($tour->price_from, 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="cat-price-block">
                                <div class="cat-price-label">Slug</div>
                                <div class="cat-price-value small text-muted text-truncate" title="{{ $tour->slug }}">{{ Str::limit($tour->slug, 18) }}</div>
                            </div>
                        </div>

                        <div class="cat-card-actions">
                            <div class="d-inline-flex align-items-center gap-1">
                                <form action="{{ route('admin.tours.toggle', $tour) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="dash-icon-btn {{ $tour->is_active ? 'is-danger' : 'is-success' }}"
                                            title="{{ $tour->is_active ? 'Disattiva' : 'Attiva' }}"
                                            data-bs-toggle="tooltip">
                                        <i class="bi {{ $tour->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.tours.edit', $tour) }}"
                                   class="dash-icon-btn is-primary" title="Modifica" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.tours.destroy', $tour) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Eliminare questo tour?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dash-icon-btn is-danger" title="Elimina" data-bs-toggle="tooltip">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dash-card">
                    <div class="dash-card-body text-center py-5">
                        <i class="bi bi-compass display-3 text-primary opacity-50 d-block mb-3"></i>
                        <h3 class="fw-bold mb-2">Nessun tour configurato</h3>
                        <p class="text-muted mb-4">Crea il primo pacchetto tour per iniziare a programmare partenze e ricevere prenotazioni.</p>
                        <a href="{{ route('admin.tours.create') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="bi bi-plus-lg me-2"></i>Aggiungi tour
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($tours->hasPages())
        <div class="d-flex justify-content-between align-items-center small text-muted">
            <div>
                Mostrando <strong>{{ $tours->firstItem() }}–{{ $tours->lastItem() }}</strong>
                di <strong>{{ $tours->total() }}</strong>
            </div>
            <div>{{ $tours->links() }}</div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    });
</script>
@endpush

