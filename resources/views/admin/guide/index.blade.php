@extends('layouts.admin')

@section('title', 'Guida')

@section('content')
    <div class="dash-page-header">
        <div>
            <h1>Guida operativa</h1>
            <p>Come usare il gestionale Solarya: prenotazioni, catamarani, pagamenti e report.</p>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($topics as $slug => $t)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('admin.guide.show', $slug) }}" class="text-decoration-none">
                    <div class="dash-card h-100 guide-card">
                        <div class="dash-card-body d-flex gap-3">
                            <span class="guide-card-icon"><i class="bi {{ $t['icon'] }}"></i></span>
                            <div>
                                <h3 class="h6 fw-bold mb-1 text-dark">{{ $t['title'] }}</h3>
                                <p class="text-muted small mb-0">{{ $t['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <style>
        .guide-card { transition: transform .12s ease, box-shadow .12s ease; }
        .guide-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.5rem rgba(15,23,42,.10); }
        .guide-card-icon { width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: #fef9c3; color: #a16207; font-size: 1.25rem; }
    </style>
@endsection
