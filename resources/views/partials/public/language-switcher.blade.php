{{--
    Selettore lingua IT/EN — segmented toggle.

    Un unico contenitore con le due lingue affiancate e la lingua attiva
    evidenziata: lo stato si legge senza aprire nulla e il cambio è un click.

    Punta alla route locale.switch, che salva la preferenza in sessione e
    reindirizza alla STESSA pagina nell'altra lingua (nome route + parametri +
    query string passati qui sotto), non alla home.

    Varianti:
      - 'inline'  header desktop (eredita fill e radius del bottone accanto)
      - 'stacked' offcanvas mobile / footer (su fondo scuro, più generoso)

    Parametri (è un @include, non un componente: niente $attributes):
      - $variant  'inline' | 'stacked'
      - $class    classi extra sul contenitore (es. utility di visibilità)
--}}
@php
    $variant = $variant ?? 'inline';
    $class = $class ?? '';
@endphp

@php
    $currentLocale = app()->getLocale();
    $locales = (array) config('locales.supported', ['it']);

    // Route corrente, in forma "nuda", per poter ricostruire la destinazione
    // nell'altra lingua. Se la pagina è fuori dal perimetro bilingue lo
    // switcher rimanda alla home della lingua scelta.
    $baseRoute = locale_base_route_name();
    $switchParams = locale_route_is_localized($baseRoute)
        ? ['route' => $baseRoute, 'params' => request()->route()?->parameters() ?? [], 'q' => request()->query()]
        : [];
@endphp

<div class="tg-lang tg-lang--{{ $variant }} {{ $class }}"
     role="group"
     aria-label="{{ __('common.a11y.language') }}">
    @foreach($locales as $locale)
        @php
            $short = config('locales.short.'.$locale, strtoupper($locale));
            $full = config('locales.names.'.$locale, strtoupper($locale));
            $flag = config('locales.flags.'.$locale);
            // La bandiera è decorativa: se il file manca, resta solo la sigla.
            $flagView = $flag ? 'partials.public.flags.'.$flag : null;
        @endphp

        @if($locale === $currentLocale)
            {{-- Lingua attiva: non è un link (non si "va" dove si è già). --}}
            <span class="tg-lang__item is-active" aria-current="true">
                @if($flagView && view()->exists($flagView))
                    @include($flagView)
                @endif
                <span class="tg-lang__code">{{ $short }}</span>
                <span class="visually-hidden">{{ $full }}</span>
            </span>
        @else
            <a class="tg-lang__item"
               href="{{ route('locale.switch', array_merge(['locale' => $locale], $switchParams)) }}"
               hreflang="{{ $locale }}"
               rel="alternate"
               lang="{{ $locale }}">
                @if($flagView && view()->exists($flagView))
                    @include($flagView)
                @endif
                <span class="tg-lang__code">{{ $short }}</span>
                <span class="visually-hidden">{{ $full }}</span>
            </a>
        @endif
    @endforeach
</div>
