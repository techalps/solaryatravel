@extends('layouts.public')

@section('title', __('tours.meta.title_listing'))
@section('meta_description', __('tours.meta.description_listing'))

@section('content')

    {{-- ============= BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="mb-3 wow fadeInUp">{{ __('tours.listing.title') }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">{{ __('tours.breadcrumb.home') }}</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">{{ __('tours.breadcrumb.tours') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Search box (stesso della home) --}}
    <div class="pt-30 pb-20">
        @include('partials.public.tour-search')
    </div>

    {{-- ============= LISTING ============= --}}
    <div class="tg-listing-area pt-30 pb-100">
        <div class="container">

            @if($search['isSearch'])
                <p class="text-muted mb-3">
                    {!! __('tours.listing.results', ['count' => '<strong>'.e($search['results']).'</strong>']) !!}
                    @if($search['date']) {{ __('tours.listing.results_for_date', ['date' => locale_date($search['date'])]) }}@endif
                </p>
            @endif

            <div class="row">
                @forelse($tours as $i => $tour)
                    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6">
                        <x-tour-card :tour="$tour" :index="$i" />
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="fa-regular fa-face-frown fs-1 text-muted mb-3 d-block"></i>
                        <h4 class="text-muted">{{ __('tours.listing.empty_title') }}</h4>
                        <p class="text-muted">{{ __('tours.listing.empty_text') }}</p>
                        <a href="{{ route('tours.index') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('tours.listing.reset_filters') }}</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
