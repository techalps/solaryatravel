<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Services\DepartureGeneratorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Widget di prenotazione incorporabile (iframe) per le agenzie.
 *
 * Stessa idea del referral (Flusso B): il widget carica il flusso pubblico
 * di prenotazione dentro un <iframe> sul sito dell'agenzia, già referenziato
 * via ?ref=TOKEN. L'attribuzione della commissione all'agenzia è gestita
 * automaticamente dal BookingForm (cookie b2b_ref impostato da
 * CaptureReferralMiddleware), come per il link/QR referral.
 *
 * Differenza dal sito normale: layout "nudo" (layouts.widget) senza header,
 * footer, tracking o cookie banner — pensato per stare in un riquadro.
 *
 * Due stati:
 *  - senza ?tour: griglia tour cliccabili (sceglie il cliente);
 *  - con ?tour: form di prenotazione (con selettore data/orario interno).
 */
class WidgetController extends Controller
{
    /**
     * Entry point del widget. La cattura di ?ref=TOKEN avviene già nel
     * CaptureReferralMiddleware (gruppo web), quindi qui non serve gestirla.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $tourParam = $request->query('tour');

        // Stato "scelta tour": griglia delle crociere prenotabili online.
        if (! $tourParam) {
            $tours = Tour::active()
                ->ordered()
                ->where('booking_on_request', false)
                ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
                ->get();
            $tours->load('periods');

            return view('widget.tours', [
                'tours' => $tours,
            ]);
        }

        // Stato "prenotazione": un tour specifico (id o slug).
        $tour = Tour::active()
            ->where(function ($q) use ($tourParam) {
                $q->where('id', $tourParam)->orWhere('slug', $tourParam);
            })
            ->with(['ageBrackets', 'images', 'periods.ageBrackets', 'catamarans'])
            ->firstOrFail();

        // Le crociere "su richiesta" non hanno checkout online: rimanda alla griglia.
        if ($tour->booking_on_request) {
            return redirect()->route('widget.index');
        }

        // Date disponibili (180gg) generate dai periodi, per il selettore interno
        // del BookingForm (modalità self-pick, vedi mount($availableDates)).
        $availableDates = app(DepartureGeneratorService::class)
            ->upcoming($tour, 180)
            ->groupBy('date')
            ->map(fn ($items) => $items->pluck('time')->unique()->values()->all())
            ->all();

        return view('widget.booking', [
            'tour' => $tour,
            'availableDates' => $availableDates,
        ]);
    }
}
