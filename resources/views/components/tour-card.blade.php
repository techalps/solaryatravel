{{--
    Card di un tour nel listing pubblico (home + /tour).
    Estratta per non duplicare la stessa marcatura — e le stesse stringhe da
    tradurre — in due view.

    I contenuti che vengono dal DB (nome, punto di partenza) passano da tdb():
    in inglese usano il dizionario lang/en/db.php, con fallback all'italiano.
--}}
@props([
    // App\Models\Tour
    'tour',
    // Indice nel ciclo: sceglie l'immagine di fallback.
    'index' => 0,
])

<div class="tg-listing-card-item mb-30">
    <div class="tg-listing-card-thumb fix mb-15 p-relative">
        <a href="{{ route('tours.show', $tour->slug) }}">
            @if($tour->primaryImage)
                <img class="tg-card-border w-100" src="{{ $tour->primaryImage->url }}" alt="{{ tdb($tour, 'name') }}">
            @else
                <img class="tg-card-border w-100" src="{{ asset('assets/template/img/hero/hero-'.(($index % 5) + 1).'.jpg') }}" alt="{{ tdb($tour, 'name') }}">
            @endif
        </a>
    </div>
    <div class="tg-listing-card-content">
        <h4 class="tg-listing-card-title"><a href="{{ route('tours.show', $tour->slug) }}">{{ tdb($tour, 'name') }}</a></h4>
        <div class="tg-listing-card-duration-tour">
            <span class="tg-listing-card-duration-map mb-5">
                <i class="fa-solid fa-location-dot me-1"></i> {{ tdb($tour, 'departure_point') ?? '' }}
            </span>
            @if($tour->duration_hours)
                <span class="tg-listing-card-duration-time">
                    <i class="fa-regular fa-clock me-1"></i> {{ $tour->duration_hours }}h
                </span>
            @endif
        </div>
    </div>
    <div class="tg-listing-card-price d-flex align-items-center justify-content-between">
        <div class="tg-listing-card-price-wrap price-bg d-flex align-items-center">
            @if($tour->price_from && ! $tour->booking_on_request)
                <span class="tg-listing-card-currency-amount mr-5">
                    <span class="currency-symbol">€</span>{{ number_format($tour->price_from, 0, ',', '.') }}
                </span>
                <span class="tg-listing-card-activity-person">{{ __('tours.card.per_person') }}</span>
            @else
                <span class="tg-listing-card-currency-amount mr-5">{{ __('tours.card.on_request') }}</span>
            @endif
        </div>
        <a href="{{ route('tours.show', $tour->slug) }}" class="tg-card-tour-link" style="margin-right:24px">
            {{ __('tours.card.discover') }} <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>
