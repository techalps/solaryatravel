<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Contesto del Portale Agenzie: chi è l'"agenzia effettiva" della sessione.
 *
 * Due tipi di utente possono operare nel portale:
 *  - un utente con ruolo b2b → l'agenzia è sé stesso;
 *  - un admin con poteri gestionali (super_admin/system_admin) → opera "per conto
 *    di" un'agenzia che sceglie e può cambiare in qualsiasi momento
 *    (impersonificazione). L'agenzia impersonata è salvata in sessione.
 *
 * TUTTE le pagine/azioni del portale devono passare da actor()/actingAgency()
 * così l'impersonificazione è trasparente: le prenotazioni risultano dell'agenzia
 * impersonata, con la sua commissione, come se le avesse fatte lei. Chi ha
 * materialmente creato la prenotazione (l'admin) viene tracciato a parte.
 */
class B2bContext
{
    public const SESSION_KEY = 'b2b_impersonate_agency_id';

    /** L'utente loggato può accedere al portale (agenzia o admin gestionale)? */
    public static function canAccess(?User $user = null): bool
    {
        $user ??= Auth::user();
        return $user !== null && ($user->isB2b() || $user->hasSuperAdminPowers());
    }

    /** L'utente loggato è un admin che impersona (non un'agenzia reale)? */
    public static function isImpersonator(?User $user = null): bool
    {
        $user ??= Auth::user();
        return $user !== null && ! $user->isB2b() && $user->hasSuperAdminPowers();
    }

    /**
     * L'agenzia effettiva della sessione, o null se un admin non ne ha ancora
     * scelta una. Per un utente b2b è sempre sé stesso.
     */
    public static function actingAgency(): ?User
    {
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        if ($user->isB2b()) {
            return $user;
        }

        if (self::isImpersonator($user)) {
            $id = session(self::SESSION_KEY);
            if ($id) {
                return User::where('role', 'b2b')->find($id);
            }
        }

        return null;
    }

    /** Imposta l'agenzia impersonata (solo per admin gestionali). */
    public static function impersonate(User $agency): void
    {
        session([self::SESSION_KEY => $agency->getKey()]);
    }

    /** Esce dall'impersonificazione (resta loggato come admin nel portale). */
    public static function stopImpersonating(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
