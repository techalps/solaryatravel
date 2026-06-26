<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\B2bContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Impersonificazione agenzia per gli admin gestionali nel Portale Agenzie.
 *
 * Un super_admin/system_admin sceglie un'agenzia e da quel momento opera per
 * conto sua: prenotazioni e commissioni risultano dell'agenzia scelta. Può
 * cambiare agenzia in qualsiasi momento.
 *
 * Riservato a chi NON è un'agenzia reale ma ha poteri gestionali: l'utente b2b
 * non impersona nessuno (è già sé stesso).
 */
class ImpersonateController extends Controller
{
    private function guard(): void
    {
        abort_unless(B2bContext::isImpersonator(), 403);
    }

    public function select(Request $request): View
    {
        $this->guard();

        $agencies = User::where('role', 'b2b')
            ->orderBy('agency_name')
            ->get(['id', 'name', 'agency_name', 'email', 'commission_rate']);

        return view('b2b.impersonate.select', [
            'agencies' => $agencies,
            'current' => B2bContext::actingAgency(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $data = $request->validate([
            'agency_id' => ['required', 'exists:users,id'],
        ]);

        $agency = User::where('role', 'b2b')->findOrFail($data['agency_id']);
        B2bContext::impersonate($agency);

        return redirect()->route('b2b.dashboard')
            ->with('success', 'Stai operando come «'.($agency->agency_name ?: $agency->name).'».');
    }

    /** Cambia agenzia: torna alla schermata di selezione. */
    public function stop(Request $request): RedirectResponse
    {
        $this->guard();
        B2bContext::stopImpersonating();

        return redirect()->route('b2b.impersonate.select');
    }
}
