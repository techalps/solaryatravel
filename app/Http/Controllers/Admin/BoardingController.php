<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingSeat;
use App\Models\TourDeparture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardingController extends Controller
{
    /**
     * Lista delle partenze imminenti per l'imbarco.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'date' => 'nullable|date',
            'tour' => 'nullable|integer',
        ]);

        // Vista per singolo giorno (default oggi): pensata per l'operatività di imbarco.
        $date = $request->date('date') ?? now()->startOfDay();
        $tourId = $request->integer('tour') ?: null;

        $departures = TourDeparture::with(['tour'])
            ->whereDate('departure_date', $date->toDateString())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($tourId, fn ($q) => $q->where('tour_id', $tourId))
            ->whereHas('tour', fn ($q) => $q->where('is_active', true))
            ->orderBy('start_time')
            ->withCount(['bookings as confirmed_bookings_count' => function ($q) {
                $q->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN]);
            }])
            ->get();

        // Elenco tour attivi per il filtro a tendina.
        $tours = \App\Models\Tour::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.boarding.index', [
            'departures' => $departures,
            'date' => $date,
            'tours' => $tours,
            'selectedTour' => $tourId,
        ]);
    }

    /**
     * Pagina imbarco di una specifica partenza.
     */
    public function show(TourDeparture $departure): View
    {
        $departure->load([
            'tour',
            'bookings' => fn ($q) => $q->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN]),
            'bookings.seatRecords.ageBracket',
            'bookings.seatRecords.catamaran',
            'bookings.seatRecords.boardedBy',
        ]);

        return view('admin.boarding.show', compact('departure'));
    }

    /**
     * JSON: stato corrente dei posti (polling real-time).
     */
    public function state(TourDeparture $departure): JsonResponse
    {
        $seats = $this->seatsFor($departure);

        return response()->json([
            'departure_id' => $departure->id,
            'total' => $seats->count(),
            'boarded' => $seats->whereNotNull('boarded_at')->count(),
            'updated_at' => now()->toIso8601String(),
            'seats' => $seats->map(fn ($s) => $this->seatPayload($s))->values(),
        ]);
    }

    /**
     * Scansione QR: marca il posto come imbarcato.
     */
    public function scan(Request $request, TourDeparture $departure): JsonResponse
    {
        $data = $request->validate([
            'qr_code' => 'required|string',
        ]);

        $code = trim($data['qr_code']);

        $seat = BookingSeat::with(['booking', 'ageBracket', 'catamaran'])
            ->where('qr_code', $code)
            ->first();

        if (! $seat) {
            return response()->json([
                'success' => false,
                'code' => 'not_found',
                'message' => 'QR code non riconosciuto.',
            ], 404);
        }

        if ($seat->booking->tour_departure_id !== $departure->id) {
            return response()->json([
                'success' => false,
                'code' => 'wrong_departure',
                'message' => 'Questo biglietto non appartiene a questa partenza.',
                'seat' => $this->seatPayload($seat),
            ], 422);
        }

        if (! in_array($seat->booking->status, [BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN], true)) {
            return response()->json([
                'success' => false,
                'code' => 'booking_not_confirmed',
                'message' => 'Prenotazione non confermata (' . $seat->booking->status->value . ').',
                'seat' => $this->seatPayload($seat),
            ], 422);
        }

        if ($seat->isBoarded()) {
            return response()->json([
                'success' => false,
                'code' => 'already_boarded',
                'message' => 'Passeggero già imbarcato alle ' . $seat->boarded_at->format('H:i') . '.',
                'seat' => $this->seatPayload($seat),
            ], 409);
        }

        $seat->markBoarded(auth()->id());

        // Aggiorna stato booking se tutti i seat sono imbarcati
        $booking = $seat->booking->loadMissing('seatRecords');
        if ($booking->seatRecords->every(fn ($s) => $s->boarded_at !== null)
            && $booking->status === BookingStatus::CONFIRMED) {
            $booking->update(['status' => BookingStatus::CHECKED_IN, 'checked_in_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'code' => 'boarded',
            'message' => 'Imbarco registrato.',
            'seat' => $this->seatPayload($seat->fresh(['boardedBy', 'booking'])),
        ]);
    }

    /**
     * Toggle manuale (pulsante in lista).
     */
    public function toggle(Request $request, TourDeparture $departure, BookingSeat $seat): JsonResponse
    {
        abort_unless($seat->booking->tour_departure_id === $departure->id, 404);

        if ($seat->isBoarded()) {
            $seat->unmarkBoarded();
            $action = 'unboarded';
        } else {
            $seat->markBoarded(auth()->id());
            $action = 'boarded';
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'seat' => $this->seatPayload($seat->fresh(['boardedBy', 'booking'])),
        ]);
    }

    private function seatsFor(TourDeparture $departure)
    {
        return BookingSeat::with(['booking', 'ageBracket', 'catamaran', 'boardedBy'])
            ->whereHas('booking', function ($q) use ($departure) {
                $q->where('tour_departure_id', $departure->id)
                    ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN]);
            })
            ->orderBy('booking_id')
            ->orderBy('seat_number')
            ->get();
    }

    private function seatPayload(BookingSeat $seat): array
    {
        return [
            'id' => $seat->id,
            'qr_code' => $seat->qr_code,
            'name' => $seat->guest_full_name ?: ($seat->booking->customer_full_name ?? '—'),
            'age_bracket' => $seat->ageBracket?->label,
            'catamaran' => $seat->catamaran?->name,
            'booking_number' => $seat->booking->booking_number,
            'is_primary' => (bool) $seat->is_primary,
            'boarded' => $seat->boarded_at !== null,
            'boarded_at' => optional($seat->boarded_at)->format('H:i'),
            'boarded_by' => $seat->boardedBy?->name,
        ];
    }
}
