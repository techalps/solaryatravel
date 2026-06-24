<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protegge la sezione "Sistema" (log, deploy, migrazioni): accessibile SOLO
 * al ruolo tecnico system_admin. Il super_admin, pur potente sul gestionale,
 * NON può accedere.
 */
class SystemAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->isSystemAdmin()) {
            abort(403, 'Sezione riservata al ruolo tecnico (system admin).');
        }

        return $next($request);
    }
}
