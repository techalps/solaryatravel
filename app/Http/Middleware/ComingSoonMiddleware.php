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
 *  - le rotte di autenticazione (login/logout/reset password) necessarie
 *    per accedere all'area riservata;
 *  - il webhook Stripe (deve restare sempre raggiungibile).
 */
class ComingSoonMiddleware
{
    /**
     * Nomi di rotta sempre accessibili anche in coming soon.
     */
    private const ALLOWED_ROUTES = [
        'login',
        'logout',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'webhooks.stripe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Settings::comingSoon()) {
            return $next($request);
        }

        // Gli amministratori autenticati vedono il sito normalmente.
        // Leggo il guard web esplicitamente per non dipendere dal guard di default.
        $user = $request->user() ?? auth()->guard('web')->user();

        // Diagnostica temporanea: aggiungi ?cs-debug=1 all'URL per vedere cosa vede il server.
        if ($request->query('cs-debug') === '1') {
            return response()->json([
                'coming_soon_attivo' => Settings::comingSoon(),
                'request_user' => $request->user()?->email,
                'guard_web_user' => auth()->guard('web')->user()?->email,
                'auth_check' => auth()->check(),
                'is_admin' => $user?->isAdmin() ?? false,
                'session_id_presente' => $request->hasSession() ? (bool) $request->session()->getId() : false,
                'cookie_sessione_ricevuto' => $request->hasCookie(config('session.cookie')),
                'session_cookie_name' => config('session.cookie'),
                'session_domain' => config('session.domain'),
            ]);
        }

        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Consenti le rotte di autenticazione e il webhook.
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // Tutto il resto: pagina coming soon (503 = servizio temporaneamente non disponibile).
        return response()->view('coming-soon', [], Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600');
    }
}
