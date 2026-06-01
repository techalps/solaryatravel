@extends('layouts.admin')

@section('title', 'Catamarani')

@section('content')
    @php
        $totalActive = $catamarans->where('is_active', true)->count();
        $totalInactive = $catamarans->where('is_active', false)->count();
        $totalCapacity = $catamarans->sum('capacity');
        $totalBookings = $catamarans->sum('bookings_count');
    @endphp

    {{-- Page header --}}
    <div class="dash-page-header">
        <div>
            <h1>Catamarani</h1>
            <p>Gestisci la flotta, prezzi, immagini e disponibilità.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.catamarans.create') }}" class="btn btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-plus-lg me-2"></i>Nuovo catamarano
            </a>
        </div>
    </div>

    {{-- Mini stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-primary-subtle text-primary"><i class="bi bi-water"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $catamarans->total() }}</div>
                    <div class="mini-stat-label">Totale flotta</div>
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
                <span class="mini-stat-icon bg-warning-subtle text-warning"><i class="bi bi-people-fill"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $totalCapacity }}</div>
                    <div class="mini-stat-label">Posti totali</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-info-subtle text-info"><i class="bi bi-journal-bookmark"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $totalBookings }}</div>
                    <div class="mini-stat-label">Prenotazioni</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Catamarans grid --}}
    <div class="row g-3 mb-4">
        @forelse($catamarans as $catamaran)
            <div class="col-sm-6 col-xl-4">
                <div class="cat-card">
                    {{-- Media --}}
                    <a href="{{ route('admin.catamarans.show', $catamaran) }}" class="cat-card-media text-decoration-none">
                        @if($catamaran->images->first())
                            <img src="{{ $catamaran->images->first()->url }}" alt="{{ $catamaran->name }}">
                        @else
                            <div class="cat-media-placeholder"><i class="bi bi-image"></i></div>
                        @endif

                        <span class="cat-status-badge {{ $catamaran->is_active ? 'active' : 'inactive' }}">
                            {{ $catamaran->is_active ? 'Attivo' : 'Inattivo' }}
                        </span>

                        @if(($catamaran->bookings_count ?? 0) > 0)
                            <span class="cat-bookings-badge">
                                <i class="bi bi-journal-bookmark me-1"></i>{{ $catamaran->bookings_count }} prenotazioni
                            </span>
                        @endif
                    </a>

                    {{-- Body --}}
                    <div class="cat-card-body">
                        <h3>
                            <a href="{{ route('admin.catamarans.show', $catamaran) }}" class="text-decoration-none text-reset stretched-link-disabled">
                                {{ $catamaran->name }}
                            </a>
                        </h3>

                        @if($catamaran->description_short)
                            <p class="small text-muted mb-2 text-truncate" title="{{ $catamaran->description_short }}">
                                {{ $catamaran->description_short }}
                            </p>
                        @endif

                        <div class="cat-meta">
                            <span><i class="bi bi-people text-primary"></i>{{ $catamaran->capacity }} posti</span>
                            @if($catamaran->length_meters)
                                <span><i class="bi bi-rulers text-primary"></i>{{ $catamaran->length_meters }} m</span>
                            @endif
                            @if($catamaran->slug)
                                <span class="text-muted"><i class="bi bi-link-45deg"></i>{{ Str::limit($catamaran->slug, 18) }}</span>
                            @endif
                        </div>

                        <div class="cat-prices">
                            <div class="cat-price-block">
                                <div class="cat-price-label">Mezza giornata</div>
                                <div class="cat-price-value">€{{ number_format($catamaran->base_price_half_day, 0, ',', '.') }}</div>
                            </div>
                            <div class="cat-price-block">
                                <div class="cat-price-label">Giornata intera</div>
                                <div class="cat-price-value">€{{ number_format($catamaran->base_price_full_day, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <div class="cat-card-actions">
                            <a href="{{ route('admin.catamarans.show', $catamaran) }}"
                               class="btn btn-sm btn-light rounded-pill border fw-medium">
                                <i class="bi bi-eye me-1"></i>Dettagli
                            </a>
                            <div class="d-inline-flex align-items-center gap-1">
                                <form action="{{ route('admin.catamarans.toggle', $catamaran) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="dash-icon-btn {{ $catamaran->is_active ? 'is-danger' : 'is-success' }}"
                                            title="{{ $catamaran->is_active ? 'Disattiva' : 'Attiva' }}"
                                            data-bs-toggle="tooltip">
                                        <i class="bi {{ $catamaran->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.catamarans.edit', $catamaran) }}"
                                   class="dash-icon-btn is-primary" title="Modifica" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="dash-card">
                    <div class="dash-card-body text-center py-5">
                        <i class="bi bi-water display-3 text-primary opacity-50 d-block mb-3"></i>
                        <h3 class="fw-bold mb-2">Nessun catamarano nella flotta</h3>
                        <p class="text-muted mb-4">Inizia ad aggiungere il primo catamarano per iniziare a ricevere prenotazioni.</p>
                        <a href="{{ route('admin.catamarans.create') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="bi bi-plus-lg me-2"></i>Aggiungi catamarano
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($catamarans->hasPages())
        <div class="d-flex justify-content-between align-items-center small text-muted">
            <div>
                Mostrando <strong>{{ $catamarans->firstItem() }}–{{ $catamarans->lastItem() }}</strong>
                di <strong>{{ $catamarans->total() }}</strong>
            </div>
            <div>{{ $catamarans->links() }}</div>
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
