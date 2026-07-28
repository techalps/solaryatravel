<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confina il ruolo "skipper" alla sola sezione Imbarco dell'area admin.
 *
 * Lo skipper è un ruolo operativo di bordo: passa AdminMiddleware (deve poter
 * entrare in /admin) ma qui viene limitato alle sole rotte necessarie per
 * scansionare i QR dei biglietti. Gli altri ruoli admin non sono toccati.
 *
 * Logica DENY-BY-DEFAULT: è consentito solo ciò che è elencato in ALLOWED. Una
 * rotta admin aggiunta in futuro resta automaticamente vietata allo skipper,
 * invece di diventare accessibile per dimenticanza.
 */
class SkipperAreaMiddleware
{
    /**
     * Nomi di rotta (admin.*) consentiti allo skipper.
     *
     * Sono le rotte della sezione Imbarco: elenco partenze, dettaglio,
     * polling dello stato, scansione QR e toggle manuale del singolo posto.
     *
     * @var array<int, string>
     */
    protected const ALLOWED = [
        'admin.boarding.index',
        'admin.boarding.show',
        'admin.boarding.state',
        'admin.boarding.scan',
        'admin.boarding.toggle',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Non è uno skipper: nessuna restrizione aggiuntiva qui.
        if (! $user || ! $user->isSkipper()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName !== null && in_array($routeName, self::ALLOWED, true)) {
            return $next($request);
        }

        // Chi arriva sulla dashboard (o su qualsiasi altra pagina admin) viene
        // portato alla sua unica sezione, invece di vedere un 403 secco: è il
        // comportamento atteso quando lo skipper apre /admin dopo il login.
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            return redirect()->route('admin.boarding.index');
        }

        abort(403, 'Il ruolo skipper può accedere solo alla sezione Imbarco.');
    }
}
