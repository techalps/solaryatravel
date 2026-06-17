@extends('layouts.admin')

@section('title', 'Guida · ' . $meta['title'])

@section('content')
    <div class="dash-page-header">
        <div>
            <h1><i class="bi {{ $meta['icon'] }} me-2 text-warning"></i>{{ $meta['title'] }}</h1>
            <p>{{ $meta['desc'] }}</p>
        </div>
        <a href="{{ route('admin.guide.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Tutti i capitoli
        </a>
    </div>

    <div class="row g-3">
        {{-- Sotto-menu capitoli --}}
        <div class="col-lg-3">
            <div class="dash-card guide-nav sticky-top" style="top: 90px;">
                <div class="list-group list-group-flush">
                    @foreach ($topics as $slug => $t)
                        <a href="{{ route('admin.guide.show', $slug) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $current === $slug ? 'active' : '' }}">
                            <i class="bi {{ $t['icon'] }}"></i>
                            <span class="small">{{ $t['title'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Contenuto --}}
        <div class="col-lg-9">
            <div class="dash-card">
                <div class="dash-card-body guide-content">
                    @include('admin.guide.pages.' . $current)
                </div>
            </div>
        </div>
    </div>

    <style>
        .guide-nav .list-group-item { border: 0; border-radius: 10px; color: #475569; }
        .guide-nav .list-group-item.active { background: #fef9c3; color: #854d0e; font-weight: 600; }
        .guide-content { line-height: 1.65; color: #334155; }
        .guide-content h2 { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 1.5rem 0 .6rem; }
        .guide-content h2:first-child { margin-top: 0; }
        .guide-content h3 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 1.1rem 0 .4rem; }
        .guide-content p { margin-bottom: .8rem; }
        .guide-content ul, .guide-content ol { margin-bottom: .9rem; padding-left: 1.2rem; }
        .guide-content li { margin-bottom: .35rem; }
        .guide-content .guide-tip { background: #ecfdf5; border-left: 4px solid #10b981; padding: .7rem 1rem; border-radius: 8px; margin: 1rem 0; }
        .guide-content .guide-warn { background: #fff7ed; border-left: 4px solid #f59e0b; padding: .7rem 1rem; border-radius: 8px; margin: 1rem 0; }
        .guide-content .guide-step { display: flex; gap: .75rem; margin-bottom: .8rem; }
        .guide-content .guide-step-num { flex-shrink: 0; width: 26px; height: 26px; border-radius: 50%; background: #facc15; color: #0f172a; font-weight: 700; font-size: .85rem; display: inline-flex; align-items: center; justify-content: center; }
        .guide-content code { background: #f1f5f9; padding: .1rem .4rem; border-radius: 5px; font-size: .85em; color: #0f172a; }
    </style>
@endsection
