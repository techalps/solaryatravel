<?php

/*
|--------------------------------------------------------------------------
| Helper di localizzazione: URL e formattazione date
|--------------------------------------------------------------------------
|
| Le pagine pubbliche del frontend sono registrate una volta per lingua con
| gli stessi URI (vedi routes/web.php):
|
|   italiano (default) → nessun prefisso, nomi "nudi"  (tours.show)
|   inglese            → prefisso /en,   nomi "en.*"   (en.tours.show)
|
| Le Blade chiamano sempre il nome "nudo" e questi helper risolvono la
| variante della lingua attiva. Nessuna Blade costruisce URL a mano.
|
| Autoloadato via composer.json ("autoload.files").
|
*/

use App\Routing\LocalizedUrlGenerator;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

if (! function_exists('locale_route_name')) {
    /**
     * Nome effettivo della route per la lingua indicata.
     *
     * Il nome della lingua di default resta "nudo" (URL storiche invariate);
     * le altre lingue hanno il prefisso del codice lingua. Se la variante
     * localizzata non esiste — è il caso di tutte le route fuori perimetro:
     * pagamenti, admin, auth, area utente — si ricade sul nome originale, che
     * è servito solo in italiano.
     */
    function locale_route_name(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $default = (string) config('locales.default', 'it');

        // Nome già qualificato (es. "en.tours.show"): riportalo alla forma nuda
        // prima di riqualificarlo, così locale_route_name('en.home', 'it') funziona.
        foreach ((array) config('locales.supported', ['it']) as $supported) {
            if ($supported !== $default && str_starts_with($name, $supported.'.')) {
                $name = substr($name, strlen($supported) + 1);
                break;
            }
        }

        if ($locale === $default) {
            return $name;
        }

        $localized = $locale.'.'.$name;

        return Route::has($localized) ? $localized : $name;
    }
}

if (! function_exists('locale_route')) {
    /**
     * Genera l'URL di una pagina del frontend nella lingua indicata
     * (default: quella attiva).
     *
     * @param  array<string, mixed>  $parameters
     */
    function locale_route(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $resolved = locale_route_name($name, $locale);
        $url = app('url');

        // routeExact() salta la rimappatura sulla lingua attiva: qui la lingua
        // di destinazione è già stata scelta (switcher, hreflang, sitemap).
        if ($url instanceof LocalizedUrlGenerator) {
            return $url->routeExact($resolved, $parameters, $absolute);
        }

        return route($resolved, $parameters, $absolute);
    }
}

if (! function_exists('locale_route_is_localized')) {
    /**
     * Vero se la route esiste in una variante localizzata, cioè se la pagina
     * è dentro il perimetro bilingue.
     */
    function locale_route_is_localized(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }

        $default = (string) config('locales.default', 'it');

        foreach ((array) config('locales.supported', ['it']) as $locale) {
            if ($locale === $default) {
                continue;
            }

            if (Route::has(locale_route_name($name, $locale)) && locale_route_name($name, $locale) !== locale_route_name($name, $default)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('locale_base_route_name')) {
    /**
     * Nome "nudo" (senza prefisso di lingua) della route corrente o passata.
     */
    function locale_base_route_name(?string $name = null): ?string
    {
        $name ??= request()->route()?->getName();

        if ($name === null) {
            return null;
        }

        return locale_route_name($name, (string) config('locales.default', 'it'));
    }
}

if (! function_exists('locale_route_is')) {
    /**
     * Equivalente di request()->routeIs() indifferente alla lingua:
     * locale_route_is('tours.*') è vero sia su /tour sia su /en/tour.
     */
    function locale_route_is(string ...$patterns): bool
    {
        $base = locale_base_route_name();

        if ($base === null) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $base)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('locale_current_url')) {
    /**
     * URL della pagina corrente nella lingua indicata.
     *
     * Riusa nome e parametri della route corrente (così lo switcher resta
     * sulla stessa pagina) e mantiene la query string. Se la pagina corrente
     * è fuori dal perimetro bilingue (o è un 404) ricade sulla home.
     */
    function locale_current_url(string $locale): string
    {
        $route = request()->route();
        $base = locale_base_route_name();

        if ($route === null || $base === null || ! locale_route_is_localized($base)) {
            return locale_route('home', [], $locale);
        }

        $parameters = $route->parameters();

        $url = locale_route($base, $parameters, $locale);

        $query = request()->getQueryString();

        return $query ? $url.'?'.$query : $url;
    }
}

if (! function_exists('locale_date')) {
    /**
     * Formatta una data nel locale attivo.
     *
     * Niente date costruite a mano nelle Blade: in italiano "01/06/2026",
     * in inglese "1 June 2026".
     */
    function locale_date(CarbonInterface|string|null $date, ?string $format = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        $locale = app()->getLocale();
        $format ??= $locale === 'it' ? 'DD/MM/YYYY' : 'D MMMM YYYY';

        return $date->copy()->locale($locale)->isoFormat($format);
    }
}

if (! function_exists('locale_date_range')) {
    /**
     * Intervallo di date leggibile nel locale attivo, usato per le fasce
     * stagionali delle tariffe (generate da date a DB, quindi NON traducibili
     * da dizionario).
     *
     *   IT  01/06/2026 – 30/06/2026
     *   EN  1 – 30 June 2026            (stesso mese)
     *   EN  25 May – 3 June 2026        (mesi diversi)
     *   EN  28 December 2026 – 3 January 2027 (anni diversi)
     */
    function locale_date_range(CarbonInterface|string|null $from, CarbonInterface|string|null $to): string
    {
        if ($from === null || $to === null) {
            return '';
        }

        $from = $from instanceof CarbonInterface ? $from : Carbon::parse($from);
        $to = $to instanceof CarbonInterface ? $to : Carbon::parse($to);

        $locale = app()->getLocale();

        if ($locale === 'it') {
            return locale_date($from).' – '.locale_date($to);
        }

        $from = $from->copy()->locale($locale);
        $to = $to->copy()->locale($locale);

        // Anni diversi: entrambe le date complete.
        if ($from->year !== $to->year) {
            return $from->isoFormat('D MMMM YYYY').' – '.$to->isoFormat('D MMMM YYYY');
        }

        // Stesso mese: mese e anno compaiono una sola volta ("1 – 30 June 2026").
        if ($from->month === $to->month) {
            return $from->isoFormat('D').' – '.$to->isoFormat('D MMMM YYYY');
        }

        // Mesi diversi, stesso anno ("25 May – 3 June 2026").
        return $from->isoFormat('D MMMM').' – '.$to->isoFormat('D MMMM YYYY');
    }
}
