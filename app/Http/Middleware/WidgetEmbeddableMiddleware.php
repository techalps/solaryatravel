<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rende le route del widget incorporabili in sicurezza dentro un <iframe> su
 * domini di terze parti (i siti delle agenzie).
 *
 * Due problemi tipici del contesto "third-party iframe" risolti qui:
 *
 *  1) Cookie SameSite. Il sito usa SameSite=Lax (default), che NON invia i
 *     cookie in un iframe cross-site: il cookie b2b_ref (attribuzione agenzia)
 *     e la sessione Livewire andrebbero persi al primo POST. Qui forziamo
 *     SameSite=None; Secure SOLO sulle route widget, senza toccare il Lax del
 *     resto del sito. Lo facciamo sia sulla config sessione (prima che il
 *     cookie di sessione venga creato) sia riscrivendo i cookie già in coda.
 *
 *  2) Framing. Impostiamo Content-Security-Policy: frame-ancestors per
 *     permettere/limitare esplicitamente chi può incorniciare il widget, e
 *     rimuoviamo eventuali X-Frame-Options che bloccherebbero l'iframe.
 *
 * NB: SameSite=None richiede Secure (solo https). In locale https self-signed
 * va bene; in produzione il widget è servito in https.
 */
class WidgetEmbeddableMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Attivo solo in "contesto widget": la pagina /widget stessa oppure le
        // richieste figlie (Livewire update/upload) provenienti da essa, che
        // riconosciamo dal Referer. Fuori da questo contesto NON tocchiamo nulla,
        // così il resto del sito mantiene SameSite=Lax.
        if (! $this->isWidgetContext($request)) {
            return $next($request);
        }

        // (1a) Il cookie di sessione viene creato a fine richiesta leggendo la
        // config: la sovrascriviamo qui, prima, solo per questa richiesta.
        config([
            'session.same_site' => 'none',
            'session.secure' => true,
        ]);

        $response = $next($request);

        // (1b) Riscrive i cookie già accodati (es. b2b_ref dal
        // CaptureReferralMiddleware, che li crea con SameSite=Lax) in None+Secure.
        $this->relaxCookies($response);

        // (2) Framing: consenti l'embedding. La allowlist per-agenzia dei domini
        // verrà aggiunta in una fase successiva; per ora permettiamo il framing
        // generico (necessario perché alcuni browser bloccano senza un valore
        // esplicito) e togliamo X-Frame-Options.
        $response->headers->remove('X-Frame-Options');
        $response->headers->set(
            'Content-Security-Policy',
            'frame-ancestors '.$this->frameAncestors($request)
        );

        return $response;
    }

    /**
     * Vero se la richiesta appartiene al flusso widget: la pagina /widget o una
     * sua sotto-richiesta (Livewire update/upload, asset) il cui Referer è una
     * URL /widget. Così il middleware resta inerte su tutto il resto del sito.
     */
    private function isWidgetContext(Request $request): bool
    {
        if ($request->is('widget') || $request->is('widget/*')) {
            return true;
        }

        // Richieste figlie del componente Livewire montato nel widget.
        if ($request->is('livewire/*')) {
            $referer = (string) $request->headers->get('referer', '');
            if ($referer !== '') {
                $path = (string) parse_url($referer, PHP_URL_PATH);
                return $path === '/widget' || str_starts_with($path, '/widget');
            }
        }

        return false;
    }

    /**
     * Riscrive ogni cookie della response con SameSite=None; Secure,
     * preservando nome/valore/scadenza/path/dominio/httpOnly/raw.
     */
    private function relaxCookies(Response $response): void
    {
        $bag = $response->headers;
        $cookies = $bag->getCookies();
        if (empty($cookies)) {
            return;
        }

        // Rimuovi tutti i Set-Cookie e riaggiungili normalizzati.
        $bag->removeCookie('');
        foreach ($cookies as $c) {
            // removeCookie richiede nome+path+domain per azzerare quello giusto.
            $bag->removeCookie($c->getName(), $c->getPath(), $c->getDomain());
        }

        foreach ($cookies as $c) {
            $bag->setCookie(Cookie::create(
                $c->getName(),
                $c->getValue(),
                $c->getExpiresTime(),
                $c->getPath(),
                $c->getDomain(),
                true,              // secure (obbligatorio con SameSite=None)
                $c->isHttpOnly(),
                $c->isRaw(),
                Cookie::SAMESITE_NONE
            ));
        }
    }

    /**
     * Valore di frame-ancestors, ricavato dai domini autorizzati dell'agenzia
     * referenziata (?ref=TOKEN sulla pagina /widget, oppure cookie b2b_ref sulle
     * sotto-richieste Livewire).
     *
     * Sicurezza opt-in: se l'agenzia non ha impostato alcun dominio (lista vuota)
     * → '*' (il widget funziona ovunque). Appena imposta almeno un dominio →
     * si restringe a quelli, così solo i suoi siti possono incorniciarlo.
     */
    private function frameAncestors(Request $request): string
    {
        $domains = $this->allowedDomainsForRequest($request);
        if (empty($domains)) {
            return '*';
        }

        // Ogni dominio diventa una source CSP https://dominio (più eventuale
        // sottodominio www implicito se l'agenzia lo elenca a parte).
        $sources = [];
        foreach ($domains as $d) {
            $host = $this->normalizeDomain($d);
            if ($host === '') {
                continue;
            }
            $sources[] = 'https://'.$host;
        }

        // 'self' permette l'anteprima nel portale/sito Solarya stesso.
        $sources[] = "'self'";

        return empty($sources) ? '*' : implode(' ', array_unique($sources));
    }

    /**
     * Domini autorizzati dell'agenzia di questa richiesta widget, o [] se nessuna
     * agenzia/nessun dominio. L'agenzia si risolve dal token referral.
     */
    private function allowedDomainsForRequest(Request $request): array
    {
        $token = $request->query('ref');
        if (! is_string($token) || $token === '') {
            // Sotto-richiesta Livewire: il token è nel cookie b2b_ref (id agenzia).
            $agencyId = $request->cookie(CaptureReferralMiddleware::COOKIE);
            if (! $agencyId) {
                return [];
            }
            $agency = \App\Models\User::where('role', 'b2b')->find($agencyId);
        } else {
            $agency = \App\Models\User::where('role', 'b2b')
                ->where('referral_token', $token)
                ->first();
        }

        if (! $agency) {
            return [];
        }

        $domains = $agency->widget_allowed_domains;
        return is_array($domains) ? array_filter($domains) : [];
    }

    /** Estrae l'host pulito (senza schema/path/porta) da un dominio inserito a mano. */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }
        // Se manca lo schema, parse_url non trova l'host: aggiungilo.
        if (! str_contains($domain, '://')) {
            $domain = 'https://'.$domain;
        }
        $host = parse_url($domain, PHP_URL_HOST);
        return is_string($host) ? strtolower($host) : '';
    }
}
