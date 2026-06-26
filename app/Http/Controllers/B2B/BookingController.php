<?php

namespace App\Http\Controllers\B2B;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
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

        // Date/orari disponibili nei prossimi 180 giorni (stessa fonte della
        // pagina tour del frontend), per alimentare il calendario del form.
        $departures = app(\App\Services\DepartureGeneratorService::class)->upcoming($tour, 180);
        $availableDates = $departures
            ->groupBy('date')
            ->map(fn ($items) => $items->pluck('time')->unique()->values()->all())
            ->all();

        return view('b2b.bookings.start', [
            'tour' => $tour,
            'departure' => $departure,
            'availableDates' => $availableDates,
            'agency' => B2bContext::actingAgency(),
        ]);
    }

    /** Lista delle prenotazioni dell'agenzia effettiva (isolamento per b2b_user_id). */
    public function index(Request $request): View
    {
        $agency = B2bContext::actingAgency();

        $query = Booking::query()
            ->where('b2b_user_id', $agency->getKey())
            ->with(['tour', 'departure']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%{$s}%")
                    ->orWhere('customer_first_name', 'like', "%{$s}%")
                    ->orWhere('customer_last_name', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%");
            });
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        return view('b2b.bookings.index', [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    /**
     * Dettaglio di una prenotazione dell'agenzia. L'isolamento è garantito qui:
     * un'agenzia può vedere SOLO le proprie prenotazioni (403 altrimenti).
     */
    public function show(Booking $booking): View
    {
        $this->authorizeAgency($booking);

        $booking->load(['tour', 'departure', 'payments', 'seatRecords.ageBracket', 'activeAddons.addon']);

        return view('b2b.bookings.show', ['booking' => $booking]);
    }

    /** Verifica che la prenotazione appartenga all'agenzia effettiva della sessione. */
    private function authorizeAgency(Booking $booking): void
    {
        $agency = B2bContext::actingAgency();
        abort_if($agency === null || $booking->b2b_user_id !== $agency->getKey(), 403);
    }
}
