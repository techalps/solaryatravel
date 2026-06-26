<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gating dell'area B2B (dominio b2b.solaryatravel.com).
 *
 * Consente l'accesso SOLO agli utenti con ruolo b2b. Un utente con altro ruolo
 * (customer/admin/...) loggato sul dominio b2b viene negato: i due canali hanno
 * sessioni separate (cookie host-only), quindi qui di norma non c'è sessione.
 *
 * La coppia speculare è HostGateMiddleware, che impedisce all'utente b2b di
 * operare sul dominio principale/admin.
 */
class B2bMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('b2b.login');
        }

        if (! auth()->user()->isB2b()) {
            abort(403, 'Area riservata alle agenzie.');
        }

        return $next($request);
    }
}
