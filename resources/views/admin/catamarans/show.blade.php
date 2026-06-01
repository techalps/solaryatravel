@extends('layouts.admin')

@section('title', $catamaran->name)

@php
    $features = $catamaran->features;
    if (is_string($features)) $features = json_decode($features, true) ?? [];
    $features = is_array($features) ? $features : [];

    $heroImage = $catamaran->images->first();
    $extraImages = $catamaran->images->skip(1);
@endphp

@section('content')
    {{-- Hero header card with overlay --}}
    <div class="cat-show-hero rounded-4 overflow-hidden mb-4 position-relative shadow-sm">
        @if($heroImage)
            <img src="{{ $heroImage->url }}" alt="{{ $catamaran->name }}"
                 class="cat-show-hero-img">
        @else
            <div class="cat-show-hero-placeholder">
                <i class="bi bi-water"></i>
            </div>
        @endif

        <div class="cat-show-hero-overlay"></div>

        <div class="cat-show-hero-content">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('admin.catamarans.index') }}"
                       class="dash-icon-btn bg-white" title="Torna alla flotta">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="text-white">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h1 class="h3 fw-bold mb-0 text-white">{{ $catamaran->name }}</h1>
                            @if($catamaran->is_active)
                                <span class="status-pill s-confirmed"><i class="bi bi-check-circle-fill"></i>Attivo</span>
                            @else
                                <span class="status-pill s-cancelled"><i class="bi bi-pause-circle-fill"></i>Inattivo</span>
                            @endif
                        </div>
                        @if($catamaran->description_short)
                            <p class="mb-0 text-white-75 small" style="max-width:60ch">
                                {{ $catamaran->description_short }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('tours.index') }}" target="_blank" rel="noopener"
                       class="btn btn-light rounded-pill px-3 fw-semibold border-0">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Vedi tour pubblici
                    </a>
                    <a href="{{ route('admin.catamarans.edit', $catamaran) }}"
                       class="btn btn-warning rounded-pill px-3 fw-semibold text-dark">
                        <i class="bi bi-pencil-square me-2"></i>Modifica
                    </a>
                </div>
            </div>

            {{-- Quick stats inside hero --}}
            <div class="cat-show-hero-stats">
                <div class="cat-hero-stat">
                    <i class="bi bi-people"></i>
                    <div>
                        <div class="cat-hero-stat-value">{{ $catamaran->capacity }}</div>
                        <div class="cat-hero-stat-label">Posti</div>
                    </div>
                </div>
                @if($catamaran->length_meters)
                    <div class="cat-hero-stat">
                        <i class="bi bi-arrows-fullscreen"></i>
                        <div>
                            <div class="cat-hero-stat-value">{{ $catamaran->length_meters }}m</div>
                            <div class="cat-hero-stat-label">Lunghezza</div>
                        </div>
                    </div>
                @endif
                <div class="cat-hero-stat">
                    <i class="bi bi-journal-bookmark"></i>
                    <div>
                        <div class="cat-hero-stat-value">{{ $stats['total_bookings'] }}</div>
                        <div class="cat-hero-stat-label">Prenotazioni</div>
                    </div>
                </div>
                <div class="cat-hero-stat">
                    <i class="bi bi-cash-coin"></i>
                    <div>
                        <div class="cat-hero-stat-value">€{{ number_format($stats['total_revenue'], 0, ',', '.') }}</div>
                        <div class="cat-hero-stat-label">Ricavi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- LEFT --}}
        <div class="col-lg-8">
            {{-- Gallery thumbnails --}}
            @if($extraImages->count() > 0)
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-images me-2 text-primary"></i>Galleria</h3>
                        <span class="small text-muted">{{ $catamaran->images->count() }} immagini</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="row g-2">
                            @foreach($extraImages as $image)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="{{ $image->url }}" target="_blank" rel="noopener"
                                       class="d-block ratio ratio-1x1 rounded-3 overflow-hidden bg-light border">
                                        <img src="{{ $image->url }}" alt=""
                                             class="w-100 h-100" style="object-fit:cover">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Description --}}
            @if($catamaran->description)
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-file-text me-2 text-primary"></i>Descrizione</h3>
                    </div>
                    <div class="dash-card-body">
                        <div class="text-secondary lh-lg">{!! nl2br(e($catamaran->description)) !!}</div>
                    </div>
                </div>
            @endif

            {{-- Features --}}
            @if(count($features) > 0)
                <div class="dash-card mb-3">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-stars me-2 text-warning"></i>Caratteristiche</h3>
                    </div>
                    <div class="dash-card-body">
                        <div class="row g-2">
                            @foreach($features as $feature)
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0"
                                              style="width:28px;height:28px">
                                            <i class="bi bi-check-lg"></i>
                                        </span>
                                        <span class="small fw-medium text-dark">{{ $feature }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Recent bookings --}}
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-clock-history me-2 text-primary"></i>Prenotazioni recenti</h3>
                    <a href="{{ route('admin.bookings.index') }}?catamaran={{ $catamaran->id }}"
                       class="small fw-semibold text-primary text-decoration-none">
                        Vedi tutte <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="dash-card-body p-0">
                    @forelse($catamaran->bookings as $booking)
                        @php
                            $statusValue = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : $booking->status;
                        @endphp
                        <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-3 border-bottom">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                <div class="avatar-sm bg-primary-subtle text-primary fw-bold">
                                    {{ strtoupper(mb_substr($booking->customer_first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($booking->customer_last_name ?? '', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="fw-semibold text-dark text-decoration-none">
                                        #{{ $booking->booking_number }}
                                    </a>
                                    <div class="small text-muted text-truncate">
                                        {{ $booking->customer_first_name }} {{ $booking->customer_last_name }}
                                        · {{ \Carbon\Carbon::parse($booking->booking_date)->locale('it')->isoFormat('D MMM YYYY') }}
                                    </div>
                                </div>
                            </div>
                            <span class="status-pill s-{{ $statusValue }}">{{ ucfirst($statusValue) }}</span>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-0">Nessuna prenotazione recente</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-lg-4">
            {{-- Tours associati --}}
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-compass me-2 text-primary"></i>Tour assegnati</h3>
                    <span class="small text-muted">{{ $catamaran->tours->count() }}</span>
                </div>
                <div class="dash-card-body">
                    @forelse($catamaran->tours as $tour)
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <a href="{{ route('admin.tours.show', $tour) }}" class="text-decoration-none text-dark fw-medium small">
                                {{ $tour->name }}
                            </a>
                            @if($tour->is_active)
                                <span class="badge bg-success-subtle text-success">Attivo</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inattivo</span>
                            @endif
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Questo catamarano è disponibile per tutti i tour senza restrizioni.</p>
                    @endforelse
                </div>
            </div>

            {{-- Stats detail --}}
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-bar-chart me-2 text-primary"></i>Statistiche</h3>
                </div>
                <div class="dash-card-body">
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="small text-muted"><i class="bi bi-journal-bookmark me-2"></i>Totali</span>
                        <span class="fw-bold">{{ $stats['total_bookings'] }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="small text-muted"><i class="bi bi-calendar-event me-2"></i>Prossime</span>
                        <span class="fw-bold text-primary">{{ $stats['upcoming_bookings'] }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <span class="small text-muted"><i class="bi bi-cash-coin me-2"></i>Ricavi</span>
                        <span class="fw-bold text-success">€{{ number_format($stats['total_revenue'], 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-lightning-charge me-2 text-warning"></i>Azioni rapide</h3>
                </div>
                <div class="dash-card-body d-flex flex-column gap-2">
                    <form action="{{ route('admin.catamarans.toggle', $catamaran) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="btn w-100 rounded-pill fw-semibold {{ $catamaran->is_active ? 'btn-outline-warning' : 'btn-success' }}">
                            @if($catamaran->is_active)
                                <i class="bi bi-pause-circle me-2"></i>Disattiva catamarano
                            @else
                                <i class="bi bi-play-circle me-2"></i>Attiva catamarano
                            @endif
                        </button>
                    </form>
                    <a href="{{ route('admin.catamarans.edit', $catamaran) }}"
                       class="btn btn-primary rounded-pill fw-semibold">
                        <i class="bi bi-pencil-square me-2"></i>Modifica dettagli
                    </a>
                </div>
            </div>

            {{-- Slug / URL --}}
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-link-45deg me-2 text-primary"></i>URL pubblico</h3>
                </div>
                <div class="dash-card-body">
                    <div class="bg-light rounded-3 p-2 small font-monospace text-muted text-truncate">
                        /catamarani/{{ $catamaran->slug }} <span class="text-warning">→ /tour</span>
                    </div>
                    <small class="text-muted">Le pagine pubbliche dei singoli catamarani sono state sostituite dalla selezione dei <strong>Tour</strong>.</small>
                </div>
            </div>
        </div>
    </div>
@endsection
