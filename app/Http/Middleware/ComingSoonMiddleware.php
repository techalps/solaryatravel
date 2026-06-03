<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quando la modalità "coming soon" (maintenance_mode) è attiva, mostra una
 * pagina di attesa a tutti i visitatori, tranne:
 *  - gli amministratori autenticati (vedono il sito normalmente);
 *  - le pagine di autenticazione (login/logout/reset password), necessarie
 *    per accedere all'area riservata — sia in GET sia in POST;
 *  - il webhook Stripe (deve restare sempre raggiungibile).
 *
 * Il filtro è basato sui PATH e non sui nomi di rotta, perché alcune rotte
 * POST (es. l'invio del form di login su /accedi) non hanno un nome assegnato
 * e verrebbero altrimenti bloccate, impedendo l'accesso.
 */
class ComingSoonMiddleware
{
    /**
     * Path sempre accessibili anche in coming soon (match su prefisso).
     */
    private const ALLOWED_PATHS = [
        'accedi',
        'logout',
        'password-dimenticata',
        'reset-password',
        'reset-password/*',
        'verifica-email',
        'verifica-email/*',
        'email/verification-notification',
        'webhooks/stripe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Settings::comingSoon()) {
            return $next($request);
        }

        // Gli amministratori autenticati vedono il sito normalmente.
        if (($user = $request->user()) && $user->isAdmin()) {
            return $next($request);
        }

        // Consenti le pagine di autenticazione e il webhook (GET e POST).
        if ($request->is(self::ALLOWED_PATHS)) {
            return $next($request);
        }

        // Tutto il resto: pagina coming soon (503 = servizio temporaneamente non disponibile).
        return response()->view('coming-soon', [], Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600');
    }
}
