<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lega host ↔ canale. È applicato a tutto il gruppo "web" (sito cliente + admin),
 * NON alle route b2b (quelle vivono sotto Route::domain(b2b.host) + middleware b2b).
 *
 * Due regole:
 *  1) Le route del sito cliente/admin NON devono rispondere sul dominio b2b: la
 *     stessa app serve entrambi gli host, quindi senza questo gate
 *     b2b.solaryatravel.com/tour mostrerebbe il sito cliente. → 404.
 *  2) Un utente con ruolo b2b che naviga sul dominio principale viene rimandato
 *     al suo portale: non deve usare il sito cliente né l'admin.
 *
 * Le sessioni sono host-only (SESSION_DOMAIN vuoto), quindi la regola 2 scatta di
 * fatto solo se un b2b si autentica sul dominio main: difesa in profondità.
 */
class HostGateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Confronto sull'host SENZA porta (come fa il routing di Laravel).
        $isB2bHost = $request->getHost() === config('b2b.domain');

        // Regola 1: sul dominio b2b il sito cliente/admin non esiste.
        if ($isB2bHost) {
            abort(404);
        }

        // Regola 2: utente b2b sul dominio principale → al suo portale.
        if (auth()->check() && auth()->user()->isB2b()) {
            return redirect()->away($this->b2bUrl());
        }

        return $next($request);
    }

    /** URL assoluto del portale b2b (rispetta lo schema della richiesta corrente). */
    private function b2bUrl(): string
    {
        $scheme = request()->getScheme();
        return $scheme.'://'.config('b2b.host');
    }
}
