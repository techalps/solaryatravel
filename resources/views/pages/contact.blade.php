@extends('layouts.app')

@section('title', 'Contatti - Solarya Travel')

@section('content')
    <section class="bg-gradient-navy text-white py-5">
        <div class="container py-4">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3 font-serif">Contattaci</h1>
                <p class="lead text-white-50 mb-0">
                    Siamo qui per rispondere a tutte le tue domande.
                    Contattaci e ti risponderemo al più presto.
                </p>
            </div>
        </div>
    </section>

    <section class="py-5 bg-sand-50">
        <div class="container py-4">
            <div class="row g-4">
                {{-- Form --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                        <h2 class="h3 fw-bold text-navy mb-4 font-serif">Inviaci un Messaggio</h2>

                        @if(session('success'))
                            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
                        @endif

                        <form action="{{ route('contact.send') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <x-input name="name" label="Nome Completo" required :value="old('name')" />
                                </div>
                                <div class="col-md-6">
                                    <x-input type="email" name="email" label="Email" required :value="old('email')" />
                                </div>
                                <div class="col-md-6">
                                    <x-input type="tel" name="phone" label="Telefono (opzionale)" :value="old('phone')" />
                                </div>
                                <div class="col-md-6">
                                    <x-input name="subject" label="Oggetto" required :value="old('subject')" />
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label fw-medium">Messaggio <span class="text-danger">*</span></label>
                                <textarea id="message" name="message" rows="5" required
                                    class="form-control rounded-3 @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <x-button type="primary" class="w-100">Invia Messaggio</x-button>
                        </form>
                    </div>
                </div>

                {{-- Info --}}
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4">
                        <h2 class="h3 fw-bold text-navy mb-4 font-serif">Informazioni di Contatto</h2>

                        @php
                            $contacts = [
                                ['icon' => 'geo-alt', 'title' => 'Indirizzo', 'html' => 'Via Toscanini 9/C<br>07026 Olbia (SS)'],
                                ['icon' => 'telephone', 'title' => 'Telefono', 'html' => '<a href="tel:+393450884743" class="text-secondary text-decoration-none">+39 345 088 4743</a>'],
                                ['icon' => 'envelope', 'title' => 'Email', 'html' => '<a href="mailto:info@solaryatravel.com" class="text-secondary text-decoration-none">info@solaryatravel.com</a>'],
                                ['icon' => 'clock', 'title' => 'Orari', 'html' => 'Lun - Sab: 9:00 - 18:00<br>Domenica: su appuntamento'],
                            ];
                        @endphp

                        @foreach($contacts as $c)
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-3 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-{{ $c['icon'] }} fs-5"></i>
                                </div>
                                <div>
                                    <h3 class="h6 fw-semibold text-navy mb-1">{{ $c['title'] }}</h3>
                                    <p class="text-secondary mb-0">{!! $c['html'] !!}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 bg-light text-center" style="height:240px;">
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                            <i class="bi bi-map fs-1 mb-2"></i>
                            <p class="mb-0">Mappa interattiva</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
