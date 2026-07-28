@extends('layouts.public')

@section('title', __('tours.booking.page_title').' — '.tdb($tour->name))

@section('content')

    {{-- HERO --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="
        @if($tour->primaryImage)
            background: linear-gradient(rgba(0,0,0,.55),rgba(0,0,0,.55)), url('{{ $tour->primaryImage->url }}') center/cover;
        @else
            background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);
        @endif
    ">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">{{ __('tours.breadcrumb.home') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tours.show', $tour->slug) }}" class="text-white-50 text-decoration-none">{{ tdb($tour->name) }}</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">{{ __('tours.breadcrumb.book') }}</li>
                        </ol>
                    </nav>
                    <h1 class="mb-2 wow fadeInUp">{{ __('tours.booking.page_title') }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8">
                    <livewire:public.booking-form :tour="$tour" :departure="$departure" />
                    <p class="text-center small text-muted mt-3">
                        {{ __('tours.booking.change_date') }}
                        <a href="{{ route('tours.show', $tour->slug) }}">{{ __('tours.booking.back_to_tour') }}</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('head')
<style>
    .breadcrumb-item.active { color: #fff !important; }
    .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.6); }
</style>
@endpush
