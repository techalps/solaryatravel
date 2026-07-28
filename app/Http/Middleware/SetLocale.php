<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Determina la lingua del frontend pubblico e la applica ad App, Carbon e
 * alla generazione delle URL.
 *
 * Precedenza:
 *   1. segmento di URL (/en/... → en)  — vince sempre, così i link condivisi
 *      e i crawler ottengono la lingua che l'URL promette;
 *   2. preferenza salvata in sessione (scelta manuale dello switcher);
 *   3. Accept-Language del browser, SOLO al primo accesso e solo se il
 *      browser non chiede l'italiano;
 *   4. default (it).
 *
 * Qualsiasi valore non supportato ricade sul default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('locales.supported', ['it']);
        $default = (string) config('locales.default', 'it');

        $fromUrl = $this->localeFromUrl($request, $supported);

        if ($fromUrl !== null) {
            $locale = $fromUrl;
            // Una scelta esplicita via URL diventa la preferenza corrente.
            $request->session()->put('locale', $locale);
        } elseif (($stored = $request->session()->get('locale')) && in_array($stored, $supported, true)) {
            $locale = $stored;
        } elseif ($this->shouldSniffBrowser($request)
            && ($detected = $this->localeFromBrowser($request, $supported, $default)) !== null) {
            // Rilevamento automatico SOLO al primo accesso e SOLO sulla home
            // (vedi shouldSniffBrowser): un deep link condiviso o indicizzato
            // deve rendere la lingua che la sua URL promette, non quella del
            // browser di chi lo apre. Da qui in avanti vale la sessione,
            // quindi una scelta manuale non viene mai sovrascritta.
            $locale = $detected;
            $request->session()->put('locale', $locale);
        } else {
            $locale = $default;
        }

        // FUORI PERIMETRO → sempre italiano. Widget agenzie, pagamenti, area
        // utente, check-in, admin e auth esistono in una sola versione: la
        // preferenza di lingua del visitatore non deve arrivarci, altrimenti
        // avremmo <html lang="en"> (e date/valute inglesi) su pagine con testo
        // interamente italiano.
        if ($fromUrl === null && ! $this->isLocalizedRequest($request)) {
            $locale = $default;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);
        setlocale(LC_TIME, $locale);

        // La generazione delle URL nella lingua attiva è gestita da
        // App\Routing\LocalizedUrlGenerator: route('tours.show', $slug)
        // risolve automaticamente sulla variante "en.*" quando il locale è en.

        $response = $next($request);

        // UNA URL PER LINGUA: se siamo su una URL senza prefisso (canonica
        // italiana) ma la lingua attiva non è l'italiano — perché salvata in
        // sessione o rilevata dal browser — mandiamo alla versione prefissata
        // invece di servire l'inglese sotto la URL italiana. Senza questo
        // avremmo contenuto duplicato (due lingue sulla stessa URL) e un
        // canonical che non corrisponde al contenuto servito.
        //
        // Il controllo è DOPO $next() di proposito: gli altri middleware del
        // gruppo web (HostGate che rimanda le agenzie al portale b2b,
        // ComingSoon, auth) devono poter rispondere per primi. Reindirizziamo
        // solo se la richiesta è arrivata fino a una pagina renderizzata (200),
        // così non scavalchiamo mai il redirect o il blocco di qualcun altro.
        if ($fromUrl === null
            && $locale !== $default
            && $response->getStatusCode() === 200
        ) {
            $redirect = $this->prefixedUrlFor($request, $locale);

            if ($redirect !== null) {
                return redirect($redirect);
            }
        }

        return $response;
    }

    /**
     * Vero se la richiesta punta a una pagina del perimetro bilingue.
     *
     * Le richieste Livewire fanno eccezione: girano tutte sullo stesso endpoint
     * (/livewire/update) e devono conservare la lingua della pagina che le ha
     * generate, altrimenti il form di prenotazione tornerebbe in italiano al
     * primo aggiornamento del componente.
     */
    protected function isLocalizedRequest(Request $request): bool
    {
        if ($request->hasHeader('X-Livewire') || $request->is('livewire/*')) {
            return true;
        }

        return locale_route_is_localized($request->route()?->getName());
    }

    /**
     * Vero se è lecito dedurre la lingua da Accept-Language.
     *
     * Condizioni (dalle specifiche): l'utente arriva sulla HOME senza una
     * preferenza già salvata. Fuori dalla home il rilevamento è disattivato,
     * così una URL condivisa o indicizzata rende sempre la lingua del proprio
     * prefisso, indipendentemente dal browser di chi la apre.
     */
    protected function shouldSniffBrowser(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        return locale_base_route_name($request->route()?->getName()) === 'home';
    }

    /**
     * URL prefissata equivalente alla richiesta corrente, o null se non è il
     * caso di reindirizzare.
     *
     * Reindirizza SOLO le GET su pagine che esistono davvero nella lingua
     * scelta (le route del perimetro bilingue). Restano intatte:
     *   - POST/PUT/DELETE — un redirect perderebbe il body del form;
     *   - le route fuori perimetro (pagamenti, area utente, admin, auth, API,
     *     widget, webhook), servite solo in italiano;
     *   - le richieste Livewire (stesso endpoint per tutte le lingue).
     */
    protected function prefixedUrlFor(Request $request, string $locale): ?string
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return null;
        }

        $name = $request->route()?->getName();

        if ($name === null || ! locale_route_is_localized($name)) {
            return null;
        }

        $parameters = $request->route()->parameters();

        $url = locale_route(locale_base_route_name($name), $parameters, $locale);

        $query = $request->getQueryString();

        return $query ? $url.'?'.$query : $url;
    }

    /**
     * Locale dal primo segmento di path (/en, /en/tour/...).
     *
     * @param  array<int, string>  $supported
     */
    protected function localeFromUrl(Request $request, array $supported): ?string
    {
        $segment = $request->segment(1);

        if ($segment === null) {
            return null;
        }

        $segment = strtolower($segment);

        return in_array($segment, $supported, true) ? $segment : null;
    }

    /**
     * Locale negoziato da Accept-Language.
     *
     * Restituisce null se il browser preferisce l'italiano (o se non esprime
     * una preferenza utile): in quel caso vale il default e non salviamo
     * nulla in sessione.
     *
     * @param  array<int, string>  $supported
     */
    protected function localeFromBrowser(Request $request, array $supported, string $default): ?string
    {
        $header = $request->header('Accept-Language');

        if (! $header) {
            return null;
        }

        $preferred = $request->getPreferredLanguage($this->acceptLanguageCandidates($supported));

        if ($preferred === null) {
            return null;
        }

        // getPreferredLanguage può restituire "en_GB": teniamo la lingua base.
        $locale = strtolower(substr(str_replace('-', '_', $preferred), 0, 2));

        if (! in_array($locale, $supported, true) || $locale === $default) {
            return null;
        }

        return $locale;
    }

    /**
     * Candidati passati a getPreferredLanguage: le lingue supportate più le
     * varianti regionali comuni, così "en-US"/"en-GB" matchano "en".
     *
     * @param  array<int, string>  $supported
     * @return array<int, string>
     */
    protected function acceptLanguageCandidates(array $supported): array
    {
        $candidates = [];

        foreach ($supported as $locale) {
            $candidates[] = $locale;
        }

        return $candidates;
    }
}
