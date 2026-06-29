@extends('layouts.widget')

@section('title', 'Prenota la tua crociera')

@section('content')
    <h5 class="mb-3">Scegli la tua crociera</h5>

    @if($tours->isEmpty())
        <div class="text-center py-4">
            <i class="fa-regular fa-face-frown fs-3 text-muted mb-2 d-block"></i>
            <p class="text-muted mb-0">Nessuna crociera disponibile al momento.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($tours as $tour)
                <div class="col-12 col-sm-6">
                    <a href="{{ route('widget.index', array_merge(['tour' => $tour->slug], request()->only('ref'))) }}"
                       class="d-block text-decoration-none text-reset h-100">
                        <div class="border rounded-3 overflow-hidden h-100 d-flex flex-column">
                            <div class="ratio ratio-16x9 bg-light">
                                @if($tour->primaryImage)
                                    <img src="{{ $tour->primaryImage->url }}" alt="{{ $tour->name }}"
                                         style="object-fit:cover" class="w-100 h-100">
                                @endif
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <h6 class="mb-1">{{ $tour->name }}</h6>
                                @if($tour->departure_point)
                                    <div class="small text-muted mb-2">
                                        <i class="fa-solid fa-location-dot me-1"></i>{{ $tour->departure_point }}
                                    </div>
                                @endif
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <span class="fw-bold text-primary">
                                        da €{{ number_format($tour->price_from, 0, ',', '.') }}
                                    </span>
                                    <span class="tg-card-tour-link" style="font-size:13px">
                                        Prenota <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
@endsection
