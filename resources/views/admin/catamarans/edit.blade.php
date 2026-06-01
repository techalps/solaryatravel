@extends('layouts.admin')

@section('title', 'Modifica ' . $catamaran->name)

@section('content')
    {{-- Page header --}}
    <div class="dash-page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.catamarans.index') }}" class="dash-icon-btn" title="Torna alla flotta">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1>Modifica <span class="text-primary">{{ $catamaran->name }}</span></h1>
                <p>Aggiorna informazioni, prezzi, caratteristiche e immagini del catamarano.</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.catamarans.show', $catamaran) }}" class="btn btn-light rounded-pill px-3 fw-semibold border">
                <i class="bi bi-eye me-2"></i>Visualizza
            </a>
        </div>
    </div>

    {{-- Images section --}}
    <div class="dash-card mb-3">
        <div class="dash-card-header">
            <h3><i class="bi bi-images me-2 text-primary"></i>Galleria immagini</h3>
            <span class="small text-muted">{{ $catamaran->images->count() }} immagini</span>
        </div>
        <div class="dash-card-body">
            @if($catamaran->images->count() > 0)
                <div class="row g-3 mb-3">
                    @foreach($catamaran->images as $image)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="cat-image-tile position-relative ratio ratio-1x1 rounded-3 overflow-hidden bg-light">
                                <img src="{{ $image->url }}" alt="{{ $image->filename ?? '' }}"
                                     class="w-100 h-100" style="object-fit:cover">
                                <div class="cat-image-overlay">
                                    <form action="{{ route('admin.catamarans.images.delete', [$catamaran, $image]) }}"
                                          method="POST" onsubmit="return confirm('Eliminare questa immagine?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger rounded-circle"
                                                title="Elimina" style="width:42px; height:42px; padding:0">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.catamarans.images.upload', $catamaran) }}"
                  method="POST" enctype="multipart/form-data" class="cat-dropzone text-center">
                @csrf
                <div class="mx-auto mb-3 rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:64px; height:64px">
                    <i class="bi bi-cloud-arrow-up fs-2"></i>
                </div>
                <h4 class="h6 fw-bold mb-1">Aggiungi nuove immagini</h4>
                <p class="text-muted small mb-3">JPEG, PNG o WebP – fino a 5MB ciascuna</p>
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp"
                       class="d-none" id="images-input" onchange="this.form.submit()">
                <label for="images-input" class="btn btn-primary rounded-pill px-4 fw-semibold mb-0">
                    <i class="bi bi-folder2-open me-2"></i>Seleziona file
                </label>
            </form>
        </div>
    </div>

    {{-- Main update form --}}
    <form action="{{ route('admin.catamarans.update', $catamaran) }}" method="POST" id="catamaran-form">
        @csrf
        @method('PUT')

        @include('admin.catamarans._form', ['catamaran' => $catamaran])

        <div class="dash-card mb-4">
            <div class="dash-card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <button type="button" class="btn btn-outline-danger rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#deleteCatamaranModal">
                    <i class="bi bi-trash me-2"></i>Elimina catamarano
                </button>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.catamarans.show', $catamaran) }}" class="btn btn-light rounded-pill px-4 border fw-semibold">
                        Annulla
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check-lg me-2"></i>Salva modifiche
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Delete confirmation modal --}}
    <div class="modal fade" id="deleteCatamaranModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:1rem">
                <div class="modal-body text-center p-4">
                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width:72px;height:72px">
                        <i class="bi bi-exclamation-triangle fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Eliminare {{ $catamaran->name }}?</h4>
                    <p class="text-muted">L'azione è irreversibile e cancellerà anche le immagini associate.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 border" data-bs-dismiss="modal">Annulla</button>
                    <form action="{{ route('admin.catamarans.destroy', $catamaran) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">
                            <i class="bi bi-trash me-2"></i>Elimina definitivamente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
