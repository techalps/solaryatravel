@extends('layouts.widget')

@section('title', 'Prenota — ' . $tour->name)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">{{ $tour->name }}</h5>
        <a href="{{ route('widget.index', request()->only('ref')) }}" class="tg-card-tour-link" style="font-size:13px">
            <i class="fa-solid fa-arrow-left me-1"></i> Altre crociere
        </a>
    </div>

    <livewire:public.booking-form :tour="$tour" :available-dates="$availableDates" :widget-mode="true" />
@endsection
