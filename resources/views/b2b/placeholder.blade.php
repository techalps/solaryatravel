@extends('layouts.b2b')

@section('title', $titolo)

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-5 text-center">
            <i class="bi bi-cone-striped text-warning" style="font-size:2.5rem"></i>
            <h2 class="h4 fw-bold mt-3 mb-1">{{ $titolo }}</h2>
            <p class="text-muted mb-0">Questa sezione del Portale Agenzie è in costruzione.</p>
        </div>
    </div>
@endsection
