{{--
    Pannello traduzioni di un contenuto (tour, extra, …).

    Mostra una scheda per ogni lingua ATTIVA diversa dall'italiano, con un campo
    per ogni testo traducibile e, accanto, il testo italiano di riferimento: chi
    traduce non deve tenere a mente l'originale né aprire un'altra pagina.

    Un campo lasciato vuoto significa "usa l'italiano": il sito non mostra mai
    un buco. Svuotare un campo già tradotto lo riporta all'italiano.

    Variabili attese:
      - $model   modello che usa il trait HasTranslations
      - $fields  array campo => ['label' => '…', 'type' => 'text|textarea|list']
--}}
@php
    $translatableLocales = \App\Support\Locales::translatable();
    $fields = $fields ?? [];
@endphp

@if (empty($translatableLocales))
    <div class="alert alert-light border mb-0 small">
        <i class="bi bi-translate me-1 text-primary"></i>
        Nessuna lingua aggiuntiva attiva. Attivane una da
        <a href="{{ route('admin.settings') }}#sec-locales">Impostazioni → Lingue</a>
        per poter tradurre questi testi.
    </div>
@else
    <p class="text-muted small mb-3">
        Traduci i testi mostrati sul sito. <strong>Un campo vuoto usa l'italiano</strong>,
        quindi puoi tradurre solo ciò che ti serve e completare il resto in seguito.
    </p>

    <ul class="nav nav-pills gap-1 mb-3" role="tablist">
        @foreach ($translatableLocales as $i => $loc)
            @php $p = $model->translationProgress($loc); @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $i === 0 ? 'active' : '' }} d-flex align-items-center gap-2"
                        data-bs-toggle="pill" data-bs-target="#tr-{{ $loc }}" type="button" role="tab">
                    @php $flag = \App\Support\Locales::flag($loc); @endphp
                    @if ($flag && view()->exists('partials.public.flags.'.$flag))
                        <span style="width:18px;display:inline-flex">@include('partials.public.flags.'.$flag)</span>
                    @endif
                    {{ \App\Support\Locales::name($loc) }}
                    {{-- Indicatore di completamento: si vede a colpo d'occhio
                         quanto resta da tradurre, senza aprire la scheda. --}}
                    <span class="badge rounded-pill {{ $p['done'] >= $p['total'] && $p['total'] > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                        {{ $p['done'] }}/{{ $p['total'] }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($translatableLocales as $i => $loc)
            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="tr-{{ $loc }}" role="tabpanel">
                @foreach ($fields as $field => $meta)
                    @php
                        $type = $meta['type'] ?? 'text';
                        $original = $model->getAttribute($field);
                        // Campo vuoto in italiano: niente da tradurre, lo nascondiamo
                        // per non allungare il form con caselle inutili.
                        $hasOriginal = is_array($original)
                            ? ! empty(array_filter((array) $original))
                            : trim((string) $original) !== '';
                        if (! $hasOriginal) { continue; }

                        $inputName = "translations[{$loc}][{$field}]";
                        $current = old("translations.{$loc}.{$field}", $model->translationFor($loc, $field));
                    @endphp

                    <div class="mb-3 pb-3 border-bottom">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            {{ $meta['label'] ?? $field }}
                        </label>

                        {{-- Testo italiano di riferimento, sempre visibile. --}}
                        <div class="small text-muted mb-2 p-2 rounded-2" style="background:#f8f9fa">
                            <span class="fw-semibold">IT:</span>
                            @if ($type === 'list')
                                {{ collect((array) $original)->filter()->implode(' · ') }}
                            @else
                                {{ $original }}
                            @endif
                        </div>

                        @if ($type === 'textarea')
                            <textarea name="{{ $inputName }}" rows="{{ $meta['rows'] ?? 4 }}"
                                      class="form-control"
                                      placeholder="Lascia vuoto per usare l'italiano">{{ $current }}</textarea>
                        @elseif ($type === 'list')
                            {{-- Lista (incluso/escluso): una riga per voce, stesso
                                 numero di righe dell'italiano così l'ordine è chiaro. --}}
                            @php
                                $originalItems = array_values(array_filter((array) $original, fn ($v) => trim((string) $v) !== ''));
                                $currentItems = (array) ($current ?? []);
                            @endphp
                            @foreach ($originalItems as $idx => $itItem)
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text text-muted" style="min-width:190px;max-width:260px">
                                        <span class="text-truncate" title="{{ $itItem }}">{{ $itItem }}</span>
                                    </span>
                                    <input type="text" name="translations[{{ $loc }}][{{ $field }}][]"
                                           class="form-control"
                                           value="{{ $currentItems[$idx] ?? '' }}"
                                           placeholder="Traduzione (vuoto = italiano)">
                                </div>
                            @endforeach
                        @else
                            <input type="text" name="{{ $inputName }}" class="form-control"
                                   value="{{ $current }}"
                                   maxlength="{{ $meta['maxlength'] ?? 255 }}"
                                   placeholder="Lascia vuoto per usare l'italiano">
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
