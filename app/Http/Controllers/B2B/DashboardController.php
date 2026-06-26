<?php

namespace App\Http\Controllers\B2B;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Support\B2bContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Home del Portale Agenzie. Mostra SOLO i dati dell'agenzia effettiva della
 * sessione (B2bContext::actingAgency): vale sia per l'agenzia reale sia per
 * l'admin che la impersona. Isolamento dati garantito dal vincolo b2b_user_id.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $agency */
        $agency = B2bContext::actingAgency();

        // Base query: solo le prenotazioni attribuite a questa agenzia.
        $scoped = fn (): Builder => Booking::query()->where('b2b_user_id', $agency->getKey());

        // Incassato generato: totale (IVA incl.) delle prenotazioni in stati di ricavo.
        $incassatoGenerato = $scoped()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->sum('total_amount');

        // Commissione: maturata = non stornata; pagata vs da ricevere.
        $commissioneMaturata = $scoped()->where('commission_status', '!=', 'reversed')
            ->whereNotNull('commission_status')->sum('commission_amount');
        $commissionePagata = $scoped()->where('commission_paid', true)->sum('commission_amount');
        $commissioneDaRicevere = max(0, $commissioneMaturata - $commissionePagata);

        $totalePrenotazioni = $scoped()->count();

        $ultime = $scoped()->with(['tour', 'departure'])
            ->latest()
            ->limit(8)
            ->get();

        return view('b2b.dashboard', [
            'agency' => $agency,
            'totalePrenotazioni' => $totalePrenotazioni,
            'incassatoGenerato' => $incassatoGenerato,
            'commissioneMaturata' => $commissioneMaturata,
            'commissionePagata' => $commissionePagata,
            'commissioneDaRicevere' => $commissioneDaRicevere,
            'ultime' => $ultime,
        ]);
    }
}
