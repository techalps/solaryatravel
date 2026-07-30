{{--
    Blocco "documento d'identità" per un singolo passeggero (obbligatorio).
    Riusato per intestatario, adulti aggiuntivi e bambini nel form pubblico/b2b/widget.

    Solo TIPO + NUMERO: si sceglie la tipologia e si compila il numero. Il luogo
    di rilascio (stato/provincia/comune), la data di scadenza e il codice fiscale
    NON si chiedono più in nessun canale di vendita, admin compreso: troppi campi
    in fase di prenotazione. Le colonne restano a database per lo storico.

    Variabili attese:
      - $group   'adults' | 'children'   (prefisso del wire:model)
      - $idx     int                     (indice del passeggero nel gruppo)
    Contesto del componente Livewire:
      - $this->docTypes
--}}
@php
    $base = $group.'.'.$idx;
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
    </div>
</div>
