{{-- Shared addon form. Uses $addon (model or null) --}}
@php $a = $addon ?? null; @endphp

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Basic info --}}
        <div class="dash-card mb-3">
            <div class="dash-card-header">
                <h3><i class="bi bi-info-circle me-2 text-primary"></i>Informazioni base</h3>
            </div>
            <div class="dash-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="name" class="form-label fw-semibold small">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" required
                               value="{{ old('name', $a->name ?? '') }}"
                               placeholder="es. Aperitivo al tramonto"
                               class="form-control form-control-lg @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="slug" class="form-label fw-semibold small">Slug</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-link-45deg"></i></span>
                            <input type="text" name="slug" id="slug"
                                   value="{{ old('slug', $a->slug ?? '') }}"
                                   placeholder="aperitivo-tramonto (auto)"
                                   class="form-control @error('slug') is-invalid @enderror">
                        </div>
                        <div class="form-text small">Lascia vuoto per generazione automatica.</div>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold small">Descrizione</label>
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Descrivi cosa include questo extra..."
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $a->description ?? '') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Immagine</label>
                        @if($a && $a->image_path)
                            <div class="mb-2 d-flex align-items-center gap-3 p-2 bg-light rounded-3">
                                <img src="{{ Storage::url($a->image_path) }}" alt=""
                                     class="rounded-3" style="width:64px; height:64px; object-fit:cover">
                                <div class="small text-muted">Immagine corrente. Caricane una nuova per sostituirla.</div>
                            </div>
                        @endif
                        <div class="cat-dropzone text-center">
                            <div class="mx-auto mb-2 rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                                 style="width:48px; height:48px">
                                <i class="bi bi-cloud-arrow-up fs-4"></i>
                            </div>
                            <input type="file" name="image" id="image"
                                   accept="image/jpeg,image/png,image/jpg,image/webp"
                                   class="form-control" style="max-width:340px; margin:0 auto">
                            <div class="form-text small mt-2">JPG, PNG o WebP – max 2MB.</div>
                            @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($a)
            {{-- Traduzioni: solo in modifica (in creazione i testi italiani non
                 sono ancora salvati, quindi non c'è nulla da affiancare). --}}
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-translate me-2 text-primary"></i>Traduzioni</h3>
                </div>
                <div class="dash-card-body">
                    @include('admin.partials._translations', [
                        'model' => $a,
                        'fields' => [
                            'name' => ['label' => 'Nome', 'type' => 'text'],
                            'description' => ['label' => 'Descrizione', 'type' => 'textarea', 'rows' => 3],
                        ],
                    ])
                </div>
            </div>
        @endif

        {{-- Pricing --}}
        <div class="dash-card mb-3">
            <div class="dash-card-header">
                <h3><i class="bi bi-currency-euro me-2 text-warning"></i>Prezzo</h3>
            </div>
            <div class="dash-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="price" class="form-label fw-semibold small">Prezzo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">€</span>
                            <input type="number" name="price" id="price" required step="0.01" min="0"
                                   value="{{ old('price', $a->price ?? '0') }}"
                                   class="form-control @error('price') is-invalid @enderror">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="price_type" class="form-label fw-semibold small">Tipo prezzo <span class="text-danger">*</span></label>
                        <select name="price_type" id="price_type" required class="form-select">
                            <option value="per_person" {{ old('price_type', $a->price_type ?? '') == 'per_person' ? 'selected' : '' }}>👤 Per persona</option>
                            <option value="per_booking" {{ old('price_type', $a->price_type ?? '') == 'per_booking' ? 'selected' : '' }}>🎫 Per prenotazione</option>
                            <option value="per_unit" {{ old('price_type', $a->price_type ?? '') == 'per_unit' ? 'selected' : '' }}>📦 Per unità</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="max_quantity" class="form-label fw-semibold small">Quantità massima</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-stack"></i></span>
                            <input type="number" name="max_quantity" id="max_quantity" min="1"
                                   value="{{ old('max_quantity', $a->max_quantity ?? '') }}"
                                   placeholder="Illimitata"
                                   class="form-control">
                        </div>
                        <div class="form-text small">Lascia vuoto per quantità illimitata.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="sort_order" class="form-label fw-semibold small">Ordine</label>
                        <input type="number" name="sort_order" id="sort_order" min="0"
                               value="{{ old('sort_order', $a->sort_order ?? 0) }}"
                               class="form-control">
                        <div class="form-text small">I valori più bassi appaiono per primi.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Advance booking --}}
        <div class="dash-card mb-3">
            <div class="dash-card-header">
                <h3><i class="bi bi-clock-history me-2 text-primary"></i>Prenotazione anticipata</h3>
            </div>
            <div class="dash-card-body">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                    <div>
                        <div class="fw-semibold mb-1">Richiede prenotazione anticipata</div>
                        <p class="small text-muted mb-0">Il cliente deve aggiungerlo con un certo anticipo rispetto alla partenza.</p>
                    </div>
                    <div class="form-check form-switch m-0" style="font-size:1.4rem">
                        <input type="hidden" name="requires_advance_booking" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="requires_advance_booking"
                               name="requires_advance_booking" value="1"
                               {{ old('requires_advance_booking', $a->requires_advance_booking ?? false) ? 'checked' : '' }}>
                    </div>
                </div>

                <div id="advance_hours_container"
                     class="{{ old('requires_advance_booking', $a->requires_advance_booking ?? false) ? '' : 'd-none' }}">
                    <label for="advance_hours" class="form-label fw-semibold small">Ore di anticipo richieste</label>
                    <div class="input-group" style="max-width:240px">
                        <input type="number" name="advance_hours" id="advance_hours" min="0"
                               value="{{ old('advance_hours', $a->advance_hours ?? 24) }}"
                               class="form-control">
                        <span class="input-group-text bg-light">ore</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Status --}}
        <div class="dash-card mb-3" style="position:sticky; top:1rem">
            <div class="dash-card-header">
                <h3><i class="bi bi-toggles me-2 text-primary"></i>Stato</h3>
            </div>
            <div class="dash-card-body">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                    <div>
                        <div class="fw-semibold mb-1">Extra attivo</div>
                        <p class="small text-muted mb-0">Visibile e selezionabile dal cliente.</p>
                    </div>
                    <div class="form-check form-switch m-0" style="font-size:1.4rem">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                               name="is_active" value="1"
                               {{ old('is_active', $a->is_active ?? true) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>
        </div>

        @if($a)
            <div class="dash-card mb-3">
                <div class="dash-card-header">
                    <h3><i class="bi bi-bar-chart me-2 text-primary"></i>Statistiche</h3>
                </div>
                <div class="dash-card-body">
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="small text-muted"><i class="bi bi-journal-bookmark me-2"></i>Prenotazioni</span>
                        <span class="fw-bold">{{ $a->bookings()->count() ?? 0 }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <span class="small text-muted"><i class="bi bi-clock me-2"></i>Creato</span>
                        <span class="fw-medium small">{{ $a->created_at?->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sw = document.getElementById('requires_advance_booking');
        const box = document.getElementById('advance_hours_container');
        if (sw && box) {
            sw.addEventListener('change', () => box.classList.toggle('d-none', !sw.checked));
        }
    });
</script>
@endpush
