@extends('layouts.b2b')

@section('title', 'Nuova prenotazione')

@section('content')

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-1">Nuova prenotazione</h2>
        <p class="text-muted mb-0">Scegli il tour da prenotare per il tuo cliente.</p>
    </div>

    @if($tours->isEmpty())
        <div class="alert alert-warning">Nessun tour disponibile al momento.</div>
    @else
        <div class="row g-3">
            @foreach($tours as $tour)
                @php $img = $tour->images->first(); @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ route('b2b.bookings.start', ['tour' => $tour->id]) }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 overflow-hidden">
                            @if($img)
                                <img src="{{ $img->url ?? asset('storage/'.$img->path) }}" alt="{{ $tour->name }}"
                                     style="height:160px;object-fit:cover" class="w-100">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height:160px">
                                    <i class="bi bi-water fs-1 text-muted opacity-50"></i>
                                </div>
                            @endif
                            <div class="card-body">
                                <h3 class="h6 fw-bold mb-1 text-dark">{{ $tour->name }}</h3>
                                <div class="d-flex align-items-center text-primary small fw-semibold">
                                    Prenota <i class="bi bi-arrow-right ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

@endsection
