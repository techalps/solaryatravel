<?php

namespace App\Routing;

use Illuminate\Routing\UrlGenerator;

/**
 * UrlGenerator che risolve i nomi di route del frontend nella lingua attiva.
 *
 * Le pagine pubbliche sono registrate una volta per lingua (vedi
 * routes/web.php): nomi "nudi" per l'italiano (URL storiche senza prefisso),
 * nomi prefissati per le altre lingue ("en.tours.show" → /en/tour/{slug}).
 *
 * Grazie a questa sottoclasse le Blade e i controller continuano a scrivere
 * route('tours.show', $slug) e ottengono automaticamente l'URL della lingua
 * attiva: nessun URL costruito a mano, nessun helper da ricordare nelle view.
 *
 * Per forzare una lingua diversa da quella attiva (switcher, hreflang,
 * sitemap) si usa locale_route() in app/Helpers/locale.php, che passa qui
 * il nome già qualificato.
 */
class LocalizedUrlGenerator extends UrlGenerator
{
    /**
     * @param  array<mixed>  $parameters
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        return parent::route($this->localizedRouteName($name), $parameters, $absolute);
    }

    /**
     * Genera l'URL di una route SENZA rimappare il nome sulla lingua attiva.
     *
     * Serve a locale_route(), che ha già scelto la lingua di destinazione e
     * passa il nome definitivo: senza questo, chiedere l'italiano mentre il
     * locale attivo è l'inglese verrebbe "corretto" di nuovo in inglese.
     *
     * @param  array<mixed>  $parameters
     */
    public function routeExact(string $name, array $parameters = [], bool $absolute = true): string
    {
        return parent::route($name, $parameters, $absolute);
    }

    /**
     * Mappa un nome "nudo" sulla variante della lingua attiva, se esiste.
     *
     * Non tocca:
     *   - i nomi già qualificati con un prefisso di lingua (li passa così come
     *     sono: è il caso di locale_route(), che sceglie la lingua a mano);
     *   - le route fuori perimetro bilingue (admin, pagamenti, auth, area
     *     utente, API), che non hanno variante localizzata.
     */
    protected function localizedRouteName(mixed $name): mixed
    {
        if (! is_string($name) || $name === '') {
            return $name;
        }

        $default = (string) config('locales.default', 'it');
        $locale = (string) app()->getLocale();

        if ($locale === $default) {
            return $name;
        }

        // Nome già qualificato per una lingua: rispetta la scelta esplicita.
        foreach ((array) config('locales.supported', [$default]) as $supported) {
            if ($supported !== $default && str_starts_with($name, $supported.'.')) {
                return $name;
            }
        }

        $localized = $locale.'.'.$name;

        return $this->routes->hasNamedRoute($localized) ? $localized : $name;
    }
}
