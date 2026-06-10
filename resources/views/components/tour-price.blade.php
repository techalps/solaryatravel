@props([
    // Prezzo per persona da mostrare (numerico o null).
    'price' => null,
    // Suffisso mostrato dopo il prezzo (es. "/pers", "/Persona"). Vuoto = niente.
    'suffix' => '',
    // Testo mostrato quando il prezzo è 0 o non valorizzato.
    'onRequest' => 'Su richiesta',
    // Decimali per number_format.
    'decimals' => 0,
    // Mostra il simbolo €.
    'symbol' => true,
])

@php
    // Fallback "Su richiesta" centralizzato: vale ovunque sia usato questo componente.
    // Un prezzo nullo o <= 0 (es. SOLARYA PRIVATE CRUISE) non mostra mai "0 €".
    $value = is_numeric($price) ? (float) $price : null;
    $isOnRequest = $value === null || $value <= 0;
@endphp

@if($isOnRequest)
    <span {{ $attributes->merge(['class' => 'tour-price tour-price--on-request']) }}>{{ $onRequest }}</span>
@else
    <span {{ $attributes->merge(['class' => 'tour-price']) }}>{{ $symbol ? '€' : '' }}{{ number_format($value, $decimals, ',', '.') }}@if($suffix)<span class="tour-price-suffix">{{ $suffix }}</span>@endif</span>
@endif
