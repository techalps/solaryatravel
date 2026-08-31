@extends('layouts.public')

@section('title', 'Termini e Condizioni')
@section('meta_description', 'Termini e Condizioni di servizio e informativa sul diritto di recesso di Solarya Travel S.r.l.')

@section('content')

    {{-- ============= BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="mb-3 wow fadeInUp">Termini e Condizioni</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Termini e Condizioni</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="py-130" style="padding-top:80px;padding-bottom:90px;background:#fff">
        <div class="container">
            <div class="mx-auto legal-page" style="max-width:820px">
                @include('pages.partials._terms-body')
            </div>
        </div>
    </section>

    @include('partials.public.legal-styles')
@endsection
