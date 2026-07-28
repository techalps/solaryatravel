{{--
    Blocco "documento d'identità" per un singolo passeggero (obbligatorio).
    Riusato per intestatario, adulti aggiuntivi e bambini nel form pubblico/b2b/widget.

    Variabili attese:
      - $group   'adults' | 'children'   (prefisso del wire:model)
      - $idx     int                     (indice del passeggero nel gruppo)
    Contesto del componente Livewire:
      - $this->docTypes, $this->countries, $this->provinces, $this->minDocExpiry
      - $this->{$group}[$idx]['doc_country'|'doc_province']  (stato corrente per la cascata)
--}}
@php
    $base = $group.'.'.$idx;
    $row = ($this->{$group}[$idx] ?? []);
    $country = strtoupper((string) ($row['doc_country'] ?? 'IT'));
    $isItaly = $country === 'IT';
    $selProvince = (string) ($row['doc_province'] ?? '');
    $comuniList = ($isItaly && $selProvince !== '') ? \App\Support\Geo::comuniByProvince($selProvince) : [];
    $selPlace = (string) ($row['doc_place'] ?? '');
@endphp
<div class="bk-doc-block mt-2">
    <label class="bk-label d-block mb-1"><i class="fa-regular fa-id-card text-primary me-1"></i>{{ __('tours.document.title') }} <span class="text-danger">*</span></label>
    <div class="row g-2">
        <div class="col-6">
            <select wire:model.live="{{ $base }}.doc_type" class="bk-input">
                <option value="">{{ __('tours.document.type') }}</option>
                @foreach($this->docTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            @error($base.'.doc_type') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="col-6">
            <input type="text" wire:model.blur="{{ $base }}.doc_number" class="bk-input text-uppercase" placeholder="{{ __('tours.document.number') }}" maxlength="40">
            @error($base.'.doc_number') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="col-12">
            <label class="bk-label small text-muted mb-1">{{ __('tours.document.expiry') }} <span class="text-danger">*</span></label>
            <input type="date" wire:model.blur="{{ $base }}.doc_expiry" class="bk-input"
                   @if($this->minDocExpiry) min="{{ $this->minDocExpiry }}" @endif>
            @error($base.'.doc_expiry') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="col-12">
            <label class="bk-label small text-muted mb-1">{{ __('tours.document.issue_place') }} <span class="text-danger">*</span></label>
        </div>
        <div class="col-{{ $isItaly ? '4' : '12' }}">
            <select wire:model.live="{{ $base }}.doc_country" class="bk-input">
                @foreach($this->countries as $c)
                    <option value="{{ $c['code'] }}">{{ $c['name'] }}</option>
                @endforeach
            </select>
            @error($base.'.doc_country') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>
        @if($isItaly)
            <div class="col-4">
                <select wire:model.live="{{ $base }}.doc_province" class="bk-input">
                    <option value="">{{ __('tours.document.province') }}</option>
                    @foreach($this->provinces as $p)
                        <option value="{{ $p['sigla'] }}">{{ $p['name'] }} ({{ $p['sigla'] }})</option>
                    @endforeach
                </select>
                @error($base.'.doc_province') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="col-4">
                <select wire:model.blur="{{ $base }}.doc_place" class="bk-input" @disabled($selProvince === '')>
                    <option value="">{{ __('tours.document.municipality') }}</option>
                    @foreach($comuniList as $comune)
                        <option value="{{ $comune }}" @selected($comune === $selPlace)>{{ $comune }}</option>
                    @endforeach
                </select>
                @error($base.'.doc_place') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
            </div>
        @else
            <div class="col-12">
                <input type="text" wire:model.blur="{{ $base }}.doc_place" class="bk-input" placeholder="{{ __('tours.document.city_free') }}" maxlength="120">
                @error($base.'.doc_place') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
            </div>
        @endif
    </div>
</div>
