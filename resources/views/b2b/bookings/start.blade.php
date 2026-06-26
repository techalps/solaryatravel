@extends('layouts.b2b')

@section('title', 'Prenota · ' . $tour->name)

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1">Prenota: {{ $tour->name }}</h2>
            <p class="text-muted mb-0">
                Stai prenotando per conto di <strong>{{ $agency->agency_name ?: $agency->name }}</strong>.
                I dati richiesti sono quelli del cliente finale.
            </p>
        </div>
        <a href="{{ route('b2b.bookings.create') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Cambia tour
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <livewire:public.booking-form :tour="$tour" :departure="$departure" :b2bMode="true" />
                </div>
            </div>
        </div>
    </div>

@endsection
