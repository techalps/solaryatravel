<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\B2bContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Creazione prenotazione dal Portale Agenzie (Flusso A).
 *
 * Il form è IDENTICO a quello del cliente sul frontend (componente Livewire
 * BookingForm montato in b2bMode): stessa disponibilità, stessi prezzi calcolati
 * server-side, stessi vincoli. L'agenzia non ha poteri admin. L'unica differenza
 * è l'attribuzione all'agenzia effettiva (B2bContext::actingAgency).
 */
class BookingController extends Controller
{
    /** Step 1: scelta del tour. */
    public function create(): View
    {
        $tours = Tour::active()
            ->where('booking_on_request', false)
            ->with('images')
            ->orderBy('name')
            ->get();

        return view('b2b.bookings.create', ['tours' => $tours]);
    }

    /**
     * Step 2: form di prenotazione per il tour scelto. Replica
     * BookingController@start del frontend, ma rende dentro il layout b2b.
     */
    public function start(Request $request): View|RedirectResponse
    {
        if (! $request->filled('tour')) {
            return redirect()->route('b2b.bookings.create');
        }

        $tour = Tour::active()
            ->where(fn ($q) => $q->where('id', $request->tour)->orWhere('slug', $request->tour))
            ->with(['ageBrackets', 'images'])
            ->firstOrFail();

        if ($tour->booking_on_request) {
            return redirect()->route('b2b.bookings.create')
                ->with('error', 'Questa crociera è su richiesta e non è prenotabile dal portale.');
        }

        $departure = null;
        $departureId = $request->input('departure');
        if ($departureId) {
            $departure = $tour->departures()->find($departureId);
        }

        return view('b2b.bookings.start', [
            'tour' => $tour,
            'departure' => $departure,
            'agency' => B2bContext::actingAgency(),
        ]);
    }
}
