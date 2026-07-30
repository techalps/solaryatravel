{{--
    Form condiviso create/edit tour.
    Variabili attese: $tour (Tour, può essere nuova istanza), $catamarans (Collection),
                      $selectedCatamarans (array di id).
--}}
@php
    $isEdit = $tour->exists;
    $included = old('included', $tour->included ?? []);
    $excluded = old('excluded', $tour->excluded ?? []);

    // Costruisce array periodi (con relative fasce d'età) da DB
    $periodsFromDb = $isEdit ? $tour->periods->map(fn($p) => [
        'id' => $p->id,
        'label' => $p->label,
        'start_date' => optional($p->start_date)->format('Y-m-d'),
        'end_date' => optional($p->end_date)->format('Y-m-d'),
        'base_price' => $p->base_price,
        'weekdays' => $p->weekdays ?? [1,2,3,4,5,6,7],
        'times' => $p->times ?? ['10:00'],
        'brackets' => $p->ageBrackets->map(fn($b) => [
            'id' => $b->id,
            'label' => $b->label,
            'min_age' => $b->min_age,
            'max_age' => $b->max_age,
            'price' => $b->price,
            'counts_as_seat' => $b->counts_as_seat,
        ])->values()->toArray(),
    ])->values()->toArray() : [];

    $periodsOld = old('periods', $periodsFromDb);

    $blocksFromDb = $isEdit ? $tour->catamaranBlocks->map(fn($b) => [
        'id' => $b->id,
        'catamaran_id' => $b->catamaran_id,
        'start_date' => optional($b->start_date)->format('Y-m-d'),
        'end_date' => optional($b->end_date)->format('Y-m-d'),
        'reason' => $b->reason,
    ])->values()->toArray() : [];
    $blocksOld = old('catamaran_blocks', $blocksFromDb);

    $hasPricingErrors = collect($errors->keys())->contains(fn($k) => str_starts_with($k, 'periods') || str_starts_with($k, 'age_brackets') || str_starts_with($k, 'catamaran_blocks'));
    $activeTab = old('_active_tab', $hasPricingErrors ? 'pricing' : 'details');
@endphp

@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Correggi i seguenti errori:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<input type="hidden" name="_active_tab" id="_active_tab" value="{{ $activeTab }}">

<div class="row g-3">
    {{-- LEFT: tabs --}}
    <div class="col-lg-8">
        <div class="dash-card mb-3 tour-tabs-card">
            <div class="dash-card-header p-0 border-0 bg-transparent">
                <ul class="nav nav-tabs tour-nav-tabs" id="tour-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'details' ? 'active' : '' }}" id="tab-details-btn" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab">
                            <i class="bi bi-info-circle me-2"></i>Dettagli
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'pricing' ? 'active' : '' }}" id="tab-pricing-btn" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button" role="tab">
                            <i class="bi bi-cash-coin me-2"></i>Prezzi e periodi
                        </button>
                    </li>
                    @if ($tour->exists)
                        {{-- Traduzioni: solo in modifica. In creazione i testi italiani
                             non sono ancora salvati, quindi non ci sarebbe nulla da
                             affiancare come riferimento. --}}
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'translations' ? 'active' : '' }}" id="tab-translations-btn" data-bs-toggle="tab" data-bs-target="#tab-translations" type="button" role="tab">
                                <i class="bi bi-translate me-2"></i>Traduzioni
                                @php $trLocales = \App\Support\Locales::translatable(); @endphp
                                @if (count($trLocales))
                                    @php
                                        $trDone = collect($trLocales)->sum(fn ($l) => $tour->translationProgress($l)['done']);
                                        $trTotal = collect($trLocales)->sum(fn ($l) => $tour->translationProgress($l)['total']);
                                    @endphp
                                    <span class="badge rounded-pill {{ $trTotal > 0 && $trDone >= $trTotal ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} ms-1">
                                        {{ $trDone }}/{{ $trTotal }}
                                    </span>
                                @endif
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
            <div class="dash-card-body">
                <div class="tab-content">
                    {{-- TAB 1: DETTAGLI --}}
                    <div class="tab-pane fade {{ $activeTab === 'details' ? 'show active' : '' }}" id="tab-details" role="tabpanel">
                        <h5 class="form-section-title"><i class="bi bi-card-text me-2 text-primary"></i>Informazioni base</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-semibold small">Nome tour <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       value="{{ old('name', $tour->name) }}"
                                       class="form-control form-control-lg @error('name') is-invalid @enderror">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="sort_order" class="form-label fw-semibold small">Ordine</label>
                                <input type="number" name="sort_order" id="sort_order" min="0"
                                       value="{{ old('sort_order', $tour->sort_order ?? 0) }}"
                                       class="form-control form-control-lg">
                            </div>
                            <div class="col-12">
                                <label for="slug" class="form-label fw-semibold small">Slug URL</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">/tours/</span>
                                    <input type="text" name="slug" id="slug"
                                           value="{{ old('slug', $tour->slug) }}"
                                           placeholder="generato-automaticamente"
                                           class="form-control @error('slug') is-invalid @enderror">
                                </div>
                                @error('slug')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Descrizione breve <span class="text-muted fw-normal">(max 500 caratteri)</span></label>
                                <textarea name="description_short" rows="2" maxlength="500" class="form-control">{{ old('description_short', $tour->description_short) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Descrizione completa</label>
                                <textarea name="description" rows="5" class="form-control">{{ old('description', $tour->description) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Itinerario</label>
                                <textarea name="itinerary" rows="4" class="form-control" placeholder="Descrivi le tappe del tour...">{{ old('itinerary', $tour->itinerary) }}</textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="form-section-title"><i class="bi bi-gear me-2 text-primary"></i>Dettagli operativi</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Durata</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                                    <input type="number" step="0.5" min="0" max="48" name="duration_hours" class="form-control" value="{{ old('duration_hours', $tour->duration_hours) }}">
                                    <span class="input-group-text bg-light">h</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Capacità min</label>
                                <input type="number" min="1" name="min_capacity" class="form-control" value="{{ old('min_capacity', $tour->min_capacity ?? 1) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Capacità max</label>
                                <input type="number" min="1" name="max_capacity" class="form-control" value="{{ old('max_capacity', $tour->max_capacity) }}" placeholder="auto">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Punto di partenza</label>
                                <input type="text" name="departure_point" class="form-control" value="{{ old('departure_point', $tour->departure_point) }}" placeholder="es. Marina di Salivoli">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Inizio stagione</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="season_start" class="form-control" value="{{ old('season_start', optional($tour->season_start)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Fine stagione</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                                    <input type="date" name="season_end" class="form-control" value="{{ old('season_end', optional($tour->season_end)->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="form-section-title"><i class="bi bi-check2-square me-2 text-primary"></i>Cosa è incluso / escluso</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-check-circle me-1 text-success"></i>Incluso</span>
                                    <button type="button" class="btn btn-sm btn-light rounded-pill border fw-medium" onclick="addItem('included-list', 'included[]')">
                                        <i class="bi bi-plus-lg me-1"></i>Aggiungi
                                    </button>
                                </label>
                                <div id="included-list" class="d-flex flex-column gap-2">
                                    @forelse ($included as $item)
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-check2 text-success"></i></span>
                                            <input type="text" name="included[]" class="form-control" value="{{ $item }}">
                                            <button type="button" class="btn btn-light border" onclick="this.closest('.input-group').remove()" title="Rimuovi">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-x-circle me-1 text-danger"></i>Escluso</span>
                                    <button type="button" class="btn btn-sm btn-light rounded-pill border fw-medium" onclick="addItem('excluded-list', 'excluded[]')">
                                        <i class="bi bi-plus-lg me-1"></i>Aggiungi
                                    </button>
                                </label>
                                <div id="excluded-list" class="d-flex flex-column gap-2">
                                    @forelse ($excluded as $item)
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-x text-danger"></i></span>
                                            <input type="text" name="excluded[]" class="form-control" value="{{ $item }}">
                                            <button type="button" class="btn btn-light border" onclick="this.closest('.input-group').remove()" title="Rimuovi">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="form-section-title">
                            <i class="bi bi-images me-2 text-primary"></i>Immagini
                            @if ($isEdit && $tour->images->count())
                                <span class="badge text-bg-light ms-2">{{ $tour->images->count() }}</span>
                            @endif
                        </h5>
                        <div class="mb-4">
                            @if ($isEdit && $tour->images->count())
                                <div class="row g-3 mb-3">
                                    @foreach ($tour->images as $img)
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <div class="position-relative ratio ratio-4x3 rounded-3 overflow-hidden bg-light">
                                                <img src="{{ $img->url }}" class="w-100 h-100" style="object-fit:cover;">
                                                <div class="position-absolute top-0 end-0 p-2 d-flex flex-column gap-1">
                                                    @if (!$img->is_primary)
                                                        <button type="button" class="btn btn-sm btn-light border rounded-circle" style="width:34px;height:34px;padding:0" title="Imposta come principale" onclick="document.getElementById('img-primary-{{ $img->id }}').submit()">
                                                            <i class="bi bi-star"></i>
                                                        </button>
                                                    @else
                                                        <span class="badge text-bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0" title="Principale">
                                                            <i class="bi bi-star-fill"></i>
                                                        </span>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-light border rounded-circle" style="width:34px;height:34px;padding:0" title="Elimina" onclick="if(confirm('Eliminare questa immagine?')) document.getElementById('img-del-{{ $img->id }}').submit()">
                                                        <i class="bi bi-trash text-danger"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <label class="form-label fw-semibold small">{{ $isEdit ? 'Carica nuove immagini' : 'Carica immagini' }}</label>
                            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                            <div class="form-text small">
                                @if ($isEdit)
                                    Max 5MB ciascuna. Formati supportati: JPG, PNG, WebP.
                                @else
                                    Le immagini possono essere caricate anche dopo la creazione. Max 5MB ciascuna.
                                @endif
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="form-section-title"><i class="bi bi-search me-2 text-primary"></i>SEO</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Meta title</label>
                                <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $tour->meta_title) }}" maxlength="255">
                                <div class="form-text small">Massimo 60 caratteri raccomandati per Google.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Meta description</label>
                                <textarea name="meta_description" rows="2" class="form-control" maxlength="500">{{ old('meta_description', $tour->meta_description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: PREZZI E PERIODI --}}
                    <div class="tab-pane fade {{ $activeTab === 'pricing' ? 'show active' : '' }}" id="tab-pricing" role="tabpanel">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <h5 class="form-section-title mb-1"><i class="bi bi-calendar-range me-2 text-primary"></i>Periodi e prezzi</h5>
                                <p class="text-muted small mb-0">Definisci uno o più periodi (es. bassa/media/alta stagione). Ogni periodo ha il suo <strong>prezzo adulto</strong> e le eventuali <strong>riduzioni</strong> per fascia d'età dei bambini.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-medium" onclick="addPeriod()">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi periodo
                            </button>
                        </div>

                        <div id="periods-container" class="d-flex flex-column gap-3">
                            @foreach ($periodsOld as $pi => $p)
                                @include('admin.tours._period_card', ['pi' => $pi, 'p' => $p])
                            @endforeach
                        </div>

                        @if (empty($periodsOld))
                            <div id="periods-empty" class="alert alert-light border d-flex align-items-start gap-2 mb-0">
                                <i class="bi bi-info-circle mt-1 text-primary"></i>
                                <div class="small">
                                    Nessun periodo definito. Clicca <strong>Aggiungi periodo</strong> per iniziare.
                                </div>
                            </div>
                        @endif

                        <hr class="my-4">

                        {{-- BLOCCHI CATAMARANI --}}
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <h5 class="form-section-title mb-1"><i class="bi bi-slash-circle me-2 text-primary"></i>Blocchi catamarano</h5>
                                <p class="text-muted small mb-0">Indica le finestre temporali in cui un catamarano specifico <strong>non</strong> può essere prenotato per questo tour (manutenzione, charter privato, ecc.).</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-medium" onclick="addCatamaranBlock()">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi blocco
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle bracket-table mb-0" id="cat-blocks-table">
                                <thead>
                                    <tr>
                                        <th style="width:28%">Catamarano <span class="text-danger">*</span></th>
                                        <th style="width:18%">Inizio <span class="text-danger">*</span></th>
                                        <th style="width:18%">Fine <span class="text-danger">*</span></th>
                                        <th>Motivo</th>
                                        <th style="width:60px" class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="cat-blocks-body">
                                    @foreach ($blocksOld as $bi => $bk)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="catamaran_blocks[{{ $bi }}][id]" value="{{ $bk['id'] ?? '' }}">
                                                <select name="catamaran_blocks[{{ $bi }}][catamaran_id]" class="form-select form-select-sm" required>
                                                    <option value="">— Seleziona —</option>
                                                    @foreach ($catamarans as $cat)
                                                        <option value="{{ $cat->id }}" @selected((int)($bk['catamaran_id'] ?? 0) === (int)$cat->id)>{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="date" name="catamaran_blocks[{{ $bi }}][start_date]" class="form-control form-control-sm" value="{{ $bk['start_date'] ?? '' }}" required></td>
                                            <td><input type="date" name="catamaran_blocks[{{ $bi }}][end_date]" class="form-control form-control-sm" value="{{ $bk['end_date'] ?? '' }}" required></td>
                                            <td><input type="text" name="catamaran_blocks[{{ $bi }}][reason]" class="form-control form-control-sm" value="{{ $bk['reason'] ?? '' }}" placeholder="es. Manutenzione"></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-light border" onclick="this.closest('tr').remove()" title="Rimuovi">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (empty($blocksOld))
                            <div id="cat-blocks-empty" class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i>Nessun blocco. Tutti i catamarani assegnati al tour sono disponibili nei periodi sopra.
                            </div>
                        @endif
                    </div>

                    {{-- TAB 3: TRADUZIONI --}}
                    @if ($tour->exists)
                        <div class="tab-pane fade {{ $activeTab === 'translations' ? 'show active' : '' }}" id="tab-translations" role="tabpanel">
                            <h5 class="form-section-title mb-1"><i class="bi bi-translate me-2 text-primary"></i>Traduzioni dei testi</h5>
                            @include('admin.partials._translations', [
                                'model' => $tour,
                                'fields' => [
                                    'name' => ['label' => 'Nome del tour', 'type' => 'text'],
                                    'description_short' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'rows' => 2],
                                    'description' => ['label' => 'Descrizione', 'type' => 'textarea', 'rows' => 6],
                                    'itinerary' => ['label' => 'Itinerario', 'type' => 'textarea', 'rows' => 5],
                                    'departure_point' => ['label' => 'Punto di partenza', 'type' => 'text'],
                                    'included' => ['label' => "Cosa è incluso", 'type' => 'list'],
                                    'excluded' => ['label' => "Cosa è escluso", 'type' => 'list'],
                                    'meta_title' => ['label' => 'Meta title (SEO)', 'type' => 'text'],
                                    'meta_description' => ['label' => 'Meta description (SEO)', 'type' => 'textarea', 'rows' => 2],
                                ],
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: sidebar --}}
    <div class="col-lg-4">
        <div class="dash-card mb-3" style="position:sticky; top:1rem">
            <div class="dash-card-header">
                <h3><i class="bi bi-toggles me-2 text-primary"></i>Pubblicazione</h3>
            </div>
            <div class="dash-card-body">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                    <div>
                        <div class="fw-semibold mb-1">Tour attivo</div>
                        <p class="small text-muted mb-0">Visibile sul sito pubblico e prenotabile.</p>
                    </div>
                    <div class="form-check form-switch m-0" style="font-size:1.4rem">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                               name="is_active" value="1"
                               @checked(old('is_active', $tour->is_active ?? true))>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                    <div>
                        <div class="fw-semibold mb-1">Su richiesta</div>
                        <p class="small text-muted mb-0">Mostra contatti al posto del modulo di prenotazione.</p>
                    </div>
                    <div class="form-check form-switch m-0" style="font-size:1.4rem">
                        <input type="hidden" name="booking_on_request" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="booking_on_request"
                               name="booking_on_request" value="1"
                               @checked(old('booking_on_request', $tour->booking_on_request ?? false))>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold">
                    <i class="bi bi-save me-2"></i>{{ $isEdit ? 'Aggiorna tour' : 'Crea tour' }}
                </button>
                <a href="{{ route('admin.tours.index') }}" class="btn btn-light border w-100 mt-2 rounded-pill fw-medium">
                    Annulla
                </a>
            </div>
        </div>

        <div class="dash-card mb-3">
            <div class="dash-card-header">
                <h3><i class="bi bi-water me-2 text-primary"></i>Catamarani assegnati</h3>
            </div>
            <div class="dash-card-body">
                <p class="text-muted small mb-3">Se non selezioni nessun catamarano, il tour può operare con qualsiasi catamarano attivo.</p>
                <div class="d-flex flex-column gap-2">
                    @foreach ($catamarans as $cat)
                        @php $isChecked = in_array($cat->id, old('catamarans', $selectedCatamarans)); @endphp
                        <label class="cat-pick {{ $isChecked ? 'is-checked' : '' }}" for="cat-{{ $cat->id }}">
                            <input class="form-check-input" type="checkbox" name="catamarans[]" value="{{ $cat->id }}" id="cat-{{ $cat->id }}" @checked($isChecked)>
                            <span class="cat-pick-body">
                                <span class="fw-semibold">{{ $cat->name }}</span>
                                <span class="text-muted small d-block"><i class="bi bi-people me-1"></i>{{ $cat->capacity }} posti</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Form esterni per azioni immagini (necessari fuori dal form principale per evitare nested forms) --}}
@if ($isEdit)
    @push('end-of-body')
        @foreach ($tour->images as $img)
            <form id="img-del-{{ $img->id }}" method="POST" action="{{ route('admin.tours.images.delete', [$tour, $img]) }}" class="d-none">
                @csrf @method('DELETE')
            </form>
            <form id="img-primary-{{ $img->id }}" method="POST" action="{{ route('admin.tours.images.primary', [$tour, $img]) }}" class="d-none">
                @csrf
            </form>
        @endforeach
    @endpush
@endif

@push('styles')
<style>
    .form-section-title {
        font-size: .95rem;
        font-weight: 700;
        color: #344054;
        margin-bottom: 1rem;
        letter-spacing: .01em;
    }
    .tour-tabs-card .dash-card-body { padding-top: 1.25rem; }
    .tour-nav-tabs {
        border-bottom: 1px solid #e5e7eb;
        padding: .5rem .75rem 0 .75rem;
        gap: .25rem;
        flex-wrap: wrap;
    }
    .tour-nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #6b7280;
        font-weight: 600;
        padding: .85rem 1.1rem;
        transition: color .15s, border-color .15s, background .15s;
        white-space: nowrap;
    }
    .tour-nav-tabs .nav-link:hover {
        color: #1f2937;
        background: #f9fafb;
    }
    .tour-nav-tabs .nav-link.active {
        color: var(--bs-primary, #0d6efd);
        background: transparent;
        border-bottom-color: var(--bs-primary, #0d6efd);
    }
    .bracket-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: .65rem;
        overflow: hidden;
    }
    .bracket-table { margin-bottom: 0; }
    .bracket-table thead th {
        background: #f9fafb;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #6b7280;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
    }
    .bracket-table tbody td { vertical-align: middle; }
    .cat-pick {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .85rem;
        border: 1px solid #e5e7eb;
        border-radius: .65rem;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        margin: 0;
    }
    .cat-pick:hover { background: #f9fafb; }
    .cat-pick.is-checked {
        border-color: var(--bs-primary, #0d6efd);
        background: rgba(13,110,253,.04);
    }
    .cat-pick .form-check-input { margin: 0; flex: 0 0 auto; }
    .cat-pick-body { flex: 1 1 auto; min-width: 0; }

    .period-card {
        border: 1px solid #e5e7eb;
        border-radius: .75rem;
        background: #fff;
        overflow: hidden;
    }
    .period-card-header {
        display: flex;
        align-items: flex-end;
        gap: .5rem;
        padding: 1rem 1rem .85rem;
        background: linear-gradient(180deg, #f9fafb 0%, #fff 100%);
        border-bottom: 1px solid #eef0f3;
    }
    .period-card-body { padding: 1rem; }
    .weekday-chip { cursor: pointer; padding: .35rem .85rem; border: 1.5px solid #eef0f3; border-radius: 50px; font-size: .8rem; font-weight: 600; color: #6c757d; background: #fff; user-select: none; transition: all .15s; }
    .weekday-chip:hover { border-color: #d0c2f7; }
    .weekday-chip.active { background: #7C37FF; color: #fff; border-color: #7C37FF; }
    .period-time .form-control { border-right: 0; }
</style>
@endpush

@push('scripts')
<script>
function addItem(listId, name) {
    const list = document.getElementById(listId);
    const div = document.createElement('div');
    div.className = 'input-group';
    const iconClass = listId === 'included-list' ? 'bi-check2 text-success' : 'bi-x text-danger';
    div.innerHTML = `<span class="input-group-text bg-light"><i class="bi ${iconClass}"></i></span><input type="text" name="${name}" class="form-control"><button type="button" class="btn btn-light border" onclick="this.closest('.input-group').remove()" title="Rimuovi"><i class="bi bi-trash text-danger"></i></button>`;
    list.appendChild(div);
}

let periodIndex = {{ count($periodsOld ?? []) }};
const periodBracketCounts = {!! json_encode(array_map(fn($p) => count($p['brackets'] ?? []), $periodsOld ?? [])) !!};

function buildBracketRow(periodIdx, bracketIdx) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="periods[${periodIdx}][brackets][${bracketIdx}][id]" value="">
            <input type="text" name="periods[${periodIdx}][brackets][${bracketIdx}][label]" class="form-control form-control-sm" placeholder="es. Bambini 3–11" required>
        </td>
        <td><input type="number" min="0" max="120" name="periods[${periodIdx}][brackets][${bracketIdx}][min_age]" class="form-control form-control-sm" value="0"></td>
        <td><input type="number" min="0" max="120" name="periods[${periodIdx}][brackets][${bracketIdx}][max_age]" class="form-control form-control-sm" placeholder="∞"></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light">€</span>
                <input type="number" step="0.01" min="0" name="periods[${periodIdx}][brackets][${bracketIdx}][price]" class="form-control" value="0" required>
            </div>
        </td>
        <td>
            <div class="form-check form-switch m-0">
                <input type="hidden" name="periods[${periodIdx}][brackets][${bracketIdx}][counts_as_seat]" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="periods[${periodIdx}][brackets][${bracketIdx}][counts_as_seat]" value="1" checked>
            </div>
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-light border" onclick="this.closest('tr').remove()" title="Rimuovi">
                <i class="bi bi-trash text-danger"></i>
            </button>
        </td>
    `;
    return tr;
}

function addBracketToPeriod(periodIdx) {
    const tbody = document.querySelector(`.period-brackets-body[data-period-index="${periodIdx}"]`);
    if (!tbody) return;
    if (typeof periodBracketCounts[periodIdx] !== 'number') periodBracketCounts[periodIdx] = 0;
    const bIdx = periodBracketCounts[periodIdx]++;
    tbody.appendChild(buildBracketRow(periodIdx, bIdx));
}

function addPeriod() {
    const container = document.getElementById('periods-container');
    const empty = document.getElementById('periods-empty');
    if (empty) empty.remove();
    const i = periodIndex++;
    periodBracketCounts[i] = 0;

    const card = document.createElement('div');
    card.className = 'period-card';
    card.dataset.periodIndex = i;
    card.innerHTML = `
        <div class="period-card-header">
            <div class="row g-2 align-items-end flex-grow-1">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Etichetta</label>
                    <input type="hidden" name="periods[${i}][id]" value="">
                    <input type="text" name="periods[${i}][label]" class="form-control form-control-sm" placeholder="es. Alta stagione">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Inizio <span class="text-danger">*</span></label>
                    <input type="date" name="periods[${i}][start_date]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Fine <span class="text-danger">*</span></label>
                    <input type="date" name="periods[${i}][end_date]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Prezzo adulto <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">€</span>
                        <input type="number" step="0.01" min="0" name="periods[${i}][base_price]" class="form-control" value="0" required>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-light border ms-2 align-self-end period-remove" title="Rimuovi periodo" onclick="removePeriod(this)">
                <i class="bi bi-trash text-danger"></i>
            </button>
        </div>
        <div class="period-card-body">
            <div class="row g-3 mb-3 pb-3 border-bottom">
                <div class="col-md-7">
                    <div class="text-uppercase text-muted small fw-semibold mb-2"><i class="bi bi-calendar-week me-1"></i>Giorni operativi</div>
                    <div class="d-flex flex-wrap gap-2 weekday-chips" data-period-index="${i}">
                        ${[1,2,3,4,5,6,7].map(d => {
                            const lbl = ['','Lun','Mar','Mer','Gio','Ven','Sab','Dom'][d];
                            return `<label class="weekday-chip active"><input type="checkbox" name="periods[${i}][weekdays][]" value="${d}" checked hidden><span>${lbl}</span></label>`;
                        }).join('')}
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-uppercase text-muted small fw-semibold"><i class="bi bi-clock me-1"></i>Orari di partenza</div>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill fw-medium" onclick="addTimeToPeriod(${i})"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 period-times-wrap" data-period-index="${i}">
                        <div class="input-group input-group-sm period-time" style="width:auto">
                            <input type="time" name="periods[${i}][times][]" class="form-control form-control-sm" value="10:00" required style="width:110px">
                            <button type="button" class="btn btn-sm btn-light border" onclick="this.closest('.period-time').remove()" title="Rimuovi"><i class="bi bi-x"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div class="text-uppercase text-muted small fw-semibold"><i class="bi bi-people me-1"></i>Fasce d'età</div>
                <button type="button" class="btn btn-sm btn-light border rounded-pill fw-medium" onclick="addBracketToPeriod(${i})">
                    <i class="bi bi-plus-lg me-1"></i>Aggiungi fascia
                </button>
            </div>
            <div class="table-responsive bracket-table-wrap">
                <table class="table align-middle bracket-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:25%">Etichetta <span class="text-danger">*</span></th>
                            <th style="width:13%">Età min</th>
                            <th style="width:13%">Età max</th>
                            <th style="width:18%">Prezzo (€) <span class="text-danger">*</span></th>
                            <th style="width:18%">Conta come posto</th>
                            <th style="width:13%" class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="period-brackets-body" data-period-index="${i}"></tbody>
                </table>
            </div>
            <div class="form-text small mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Disabilita "Conta come posto" per i bambini in braccio (es. infanti 0-2 anni).
            </div>
        </div>
    `;
    container.appendChild(card);
    // aggiunge una fascia "Adulto" di default
    addBracketToPeriod(i);
}

function removePeriod(btn) {
    if (!confirm('Rimuovere questo periodo e tutte le sue fasce?')) return;
    btn.closest('.period-card').remove();
}

function addTimeToPeriod(periodIdx) {
    const wrap = document.querySelector(`.period-times-wrap[data-period-index="${periodIdx}"]`);
    if (!wrap) return;
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm period-time';
    div.style.width = 'auto';
    div.innerHTML = `<input type="time" name="periods[${periodIdx}][times][]" class="form-control form-control-sm" value="10:00" required style="width:110px"><button type="button" class="btn btn-sm btn-light border" onclick="this.closest('.period-time').remove()" title="Rimuovi"><i class="bi bi-x"></i></button>`;
    wrap.appendChild(div);
}

const catamaransList = {!! json_encode($catamarans->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()) !!};
let catBlockIndex = {{ count($blocksOld ?? []) }};
function addCatamaranBlock() {
    const tbody = document.getElementById('cat-blocks-body');
    const empty = document.getElementById('cat-blocks-empty');
    if (empty) empty.remove();
    const i = catBlockIndex++;
    const opts = ['<option value="">— Seleziona —</option>']
        .concat(catamaransList.map(c => `<option value="${c.id}">${c.name}</option>`))
        .join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="catamaran_blocks[${i}][id]" value="">
            <select name="catamaran_blocks[${i}][catamaran_id]" class="form-select form-select-sm" required>${opts}</select>
        </td>
        <td><input type="date" name="catamaran_blocks[${i}][start_date]" class="form-control form-control-sm" required></td>
        <td><input type="date" name="catamaran_blocks[${i}][end_date]" class="form-control form-control-sm" required></td>
        <td><input type="text" name="catamaran_blocks[${i}][reason]" class="form-control form-control-sm" placeholder="es. Manutenzione"></td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-light border" onclick="this.closest('tr').remove()" title="Rimuovi">
                <i class="bi bi-trash text-danger"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

document.addEventListener('DOMContentLoaded', function () {
    // Mantiene la tab attiva tra submit/redirect
    const tabsEl = document.querySelectorAll('#tour-tabs button[data-bs-toggle="tab"]');
    const activeInput = document.getElementById('_active_tab');
    if (activeInput) {
        tabsEl.forEach(btn => {
            btn.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                activeInput.value = target === '#tab-pricing' ? 'pricing' : 'details';
            });
        });
    }
    // Highlight delle catamarani-card al cambio checkbox
    document.querySelectorAll('.cat-pick input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', () => {
            cb.closest('.cat-pick').classList.toggle('is-checked', cb.checked);
        });
    });

    // Toggle giorni della settimana (chip)
    document.addEventListener('click', (e) => {
        const chip = e.target.closest('.weekday-chip');
        if (!chip) return;
        e.preventDefault();
        const cb = chip.querySelector('input[type="checkbox"]');
        if (!cb) return;
        cb.checked = !cb.checked;
        chip.classList.toggle('active', cb.checked);
    });
});
</script>
@endpush
