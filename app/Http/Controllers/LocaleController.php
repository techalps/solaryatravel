<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switcher di lingua del frontend pubblico.
 *
 * Salva la preferenza in sessione (una scelta manuale vince sempre sul
 * rilevamento da Accept-Language, che agisce solo al primo accesso) e
 * reindirizza alla STESSA pagina nell'altra lingua, non alla home.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $supported = \App\Support\Locales::active();

        if (! in_array($locale, $supported, true)) {
            $locale = (string) config('locales.default', 'it');
        }

        $request->session()->put('locale', $locale);

        return redirect($this->destination($request, $locale));
    }

    /**
     * Ricostruisce la pagina di provenienza nella lingua scelta.
     *
     * Lo switcher passa la route corrente (nome + parametri) in query string:
     * così funziona anche su pagine con parametri (es. /tour/{slug}) senza
     * dover parsare il Referer.
     */
    protected function destination(Request $request, string $locale): string
    {
        $name = (string) $request->query('route', '');

        if ($name !== '' && locale_route_is_localized($name)) {
            /** @var array<string, mixed> $parameters */
            $parameters = (array) $request->query('params', []);
            unset($parameters['locale']);

            // La query string della pagina di partenza (filtri di ricerca dei
            // tour, ecc.) va preservata attraverso il cambio lingua.
            $query = (array) $request->query('q', []);

            try {
                $url = locale_route($name, $parameters, $locale);

                return $query ? $url.'?'.http_build_query($query) : $url;
            } catch (\Throwable) {
                // Nome route o parametri non validi: cadiamo sulla home.
            }
        }

        return locale_route('home', [], $locale);
    }
}
