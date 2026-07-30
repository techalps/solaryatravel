{{--
    Selettore lingua — bandiera corrente + dropdown con le altre lingue attive.

    Sostituisce il vecchio toggle segmentato, che affiancava TUTTE le lingue: con
    due stava in piedi, ma bastano quattro o cinque lingue attive per farlo
    sbordare dall'header. Qui l'ingombro è costante qualunque sia il numero di
    lingue: si vede solo quella corrente, le altre stanno nel menu.
    Alla scelta, la bandiera visibile diventa quella appena selezionata (il
    cambio lingua ricarica la pagina, quindi è il render successivo a mostrarla).

    Punta alla route locale.switch, che salva la preferenza in sessione e
    reindirizza alla STESSA pagina nella nuova lingua (nome route + parametri +
    query string passati qui sotto), non alla home.

    Varianti:
      - 'inline'  header desktop (menu ancorato a destra, sotto il trigger)
      - 'stacked' offcanvas mobile / footer (su fondo scuro, target più generosi)

    Parametri (è un @include, non un componente: niente $attributes):
      - $variant  'inline' | 'stacked'
      - $class    classi extra sul contenitore (es. utility di visibilità)
--}}
@php
    $variant = $variant ?? 'inline';
    $class = $class ?? '';

    $currentLocale = app()->getLocale();
    $locales = \App\Support\Locales::active();

    // Route corrente, in forma "nuda", per poter ricostruire la destinazione
    // nell'altra lingua. Se la pagina è fuori dal perimetro bilingue lo
    // switcher rimanda alla home della lingua scelta.
    $baseRoute = locale_base_route_name();
    $switchParams = locale_route_is_localized($baseRoute)
        ? ['route' => $baseRoute, 'params' => request()->route()?->parameters() ?? [], 'q' => request()->query()]
        : [];

    // Le altre lingue: quelle attive meno la corrente. Se non ce ne sono
    // (un'unica lingua attiva) il selettore non ha senso e non lo stampiamo.
    $others = array_values(array_filter($locales, fn ($l) => $l !== $currentLocale));

    $flagViewFor = function (string $locale): ?string {
        $flag = config('locales.flags.'.$locale);
        $view = $flag ? 'partials.public.flags.'.$flag : null;

        // La bandiera è decorativa: se il file manca, resta solo la sigla.
        return $view && view()->exists($view) ? $view : null;
    };

    $currentShort = config('locales.short.'.$currentLocale, strtoupper($currentLocale));
    $currentFull = config('locales.names.'.$currentLocale, strtoupper($currentLocale));
    $currentFlag = $flagViewFor($currentLocale);

    // id univoco: il partial è incluso più volte nella stessa pagina (header
    // desktop, offcanvas, footer) e due menu non possono condividere l'id.
    $switcherId = 'tg-lang-'.$variant.'-'.uniqid();
@endphp

@if(count($others) > 0)
    <div class="tg-lang tg-lang--{{ $variant }} {{ $class }}">
        <button type="button"
                class="tg-lang__current"
                id="{{ $switcherId }}"
                data-bs-toggle="dropdown"
                data-bs-offset="0,6"
                aria-expanded="false"
                aria-label="{{ __('common.a11y.language') }}">
            @if($currentFlag)
                @include($currentFlag)
            @endif
            <span class="tg-lang__code">{{ $currentShort }}</span>
            <span class="visually-hidden">{{ $currentFull }}</span>
            <svg class="tg-lang__caret" viewBox="0 0 10 6" aria-hidden="true" focusable="false">
                <path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <ul class="dropdown-menu dropdown-menu-end tg-lang__menu" aria-labelledby="{{ $switcherId }}">
            @foreach($others as $locale)
                @php
                    $short = config('locales.short.'.$locale, strtoupper($locale));
                    $full = config('locales.names.'.$locale, strtoupper($locale));
                    $flagView = $flagViewFor($locale);
                @endphp
                <li>
                    <a class="dropdown-item tg-lang__option"
                       href="{{ route('locale.switch', array_merge(['locale' => $locale], $switchParams)) }}"
                       hreflang="{{ $locale }}"
                       rel="alternate"
                       lang="{{ $locale }}">
                        @if($flagView)
                            @include($flagView)
                        @endif
                        {{-- Nel menu c'è spazio: mostriamo il nome della lingua nella
                             lingua stessa (Deutsch, non Tedesco), che è ciò che chi la
                             parla riconosce al volo. La sigla resta come ancora visiva. --}}
                        <span class="tg-lang__name">{{ $full }}</span>
                        <span class="tg-lang__code tg-lang__code--muted">{{ $short }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
