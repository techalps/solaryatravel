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
     * Valore di frame-ancestors. Per ora generico ('*' più gli host noti del
     * progetto). La restrizione ai domini autorizzati dell'agenzia (via token)
     * arriverà con la pagina di gestione domini.
     */
    private function frameAncestors(Request $request): string
    {
        // '*' permette l'embedding su qualunque dominio (le agenzie sono molte
        // e i loro domini non sono ancora registrati). Lo stringeremo per-agenzia
        // nella fase di gestione domini consentiti.
        return '*';
    }
}
