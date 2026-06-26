<?php

namespace App\Http\Middleware;

use App\Support\B2bContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gating dell'area B2B (dominio b2b.solaryatravel.com).
 *
 * Possono accedere:
 *  - gli utenti con ruolo b2b (l'agenzia è sé stesso);
 *  - gli admin con poteri gestionali (super_admin/system_admin), che operano
 *    "per conto di" un'agenzia scelta (impersonificazione).
 *
 * Un admin che non ha ancora scelto l'agenzia viene mandato alla schermata di
 * selezione: senza un'agenzia attiva non può vedere dati né prenotare.
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

        if (! B2bContext::canAccess()) {
            abort(403, 'Area riservata alle agenzie.');
        }

        // Admin senza agenzia selezionata → schermata di scelta.
        if (B2bContext::isImpersonator() && B2bContext::actingAgency() === null
            && ! $request->routeIs('b2b.impersonate.*')) {
            return redirect()->route('b2b.impersonate.select');
        }

        return $next($request);
    }
}
