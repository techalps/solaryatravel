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
        $dayStr = $date->toDateString();
        $tourId = $request->integer('tour') ?: null;

        // 1) Partenze MATERIALIZZATE del giorno (con conteggio prenotazioni).
        $materialized = TourDeparture::with(['tour'])
            ->whereDate('departure_date', $dayStr)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($tourId, fn ($q) => $q->where('tour_id', $tourId))
            ->whereHas('tour', fn ($q) => $q->where('is_active', true))
            ->orderBy('start_time')
            ->withCount(['bookings as confirmed_bookings_count' => function ($q) {
                $q->whereIn('status', BookingStatus::boardableStatuses());
            }])
            ->get();

        $seen = $materialized->map(fn ($d) => $d->tour_id . '-' . \Carbon\Carbon::parse($d->start_time)->format('H:i'))->all();

        $items = $materialized->map(fn ($d) => [
            'tour' => $d->tour,
            'time' => \Carbon\Carbon::parse($d->start_time)->format('H:i'),
            'end_time' => $d->end_time ? \Carbon\Carbon::parse($d->end_time)->format('H:i') : null,
            'count' => (int) $d->confirmed_bookings_count,
            'departure' => $d,          // scanner disponibile
            'spanning' => false,
        ])->values()->all();

        // 2) Partenze VIRTUALI dai periodi: tour che OPERANO quel giorno, anche con 0
        //    prenotazioni e senza riga materializzata. Card visibile, senza scanner.
        $gen = app(\App\Services\DepartureGeneratorService::class);
        $tourQuery = \App\Models\Tour::where('is_active', true)->whereHas('periods')
            ->when($tourId, fn ($q) => $q->where('id', $tourId));
        foreach ($tourQuery->get() as $tour) {
            foreach ($gen->generate($tour, $date->copy()->startOfDay(), $date->copy()->endOfDay(), true) as $v) {
                $key = $tour->id . '-' . $v['time'];
                if (in_array($key, $seen, true)) {
                    continue;
                }
                $seen[] = $key;
                $items[] = [
                    'tour' => $tour,
                    'time' => $v['time'],
                    'end_time' => null,
                    'count' => 0,
                    'departure' => null,    // niente scanner: nessuna prenotazione
                    'spanning' => false,
                ];
            }
        }

        // 3) Prenotazioni a USO ESCLUSIVO il cui BLOCCO copre il giorno ma la cui
        //    partenza è un altro giorno: card dedicata con link allo scanner reale.
        foreach ($this->spanningReservedDepartures($dayStr, $tourId) as $sp) {
            $items[] = $sp;
        }

        usort($items, fn ($a, $b) => strcmp($a['time'], $b['time']));

        $tours = \App\Models\Tour::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.boarding.index', [
            'items' => collect($items),
            'date' => $date,
            'tours' => $tours,
            'selectedTour' => $tourId,
        ]);
    }

    /**
     * Partenze "estese": prenotazioni a uso esclusivo il cui blocco copre il giorno
     * ma la cui partenza effettiva è in un altro giorno del periodo. Restituisce voci
     * con la partenza reale (per lo scanner) e il conteggio prenotazioni.
     *
     * @return array<int, array>
     */
    protected function spanningReservedDepartures(string $dayStr, ?int $tourId): array
    {
        $blocks = \App\Models\TourCatamaranBlock::query()
            ->whereDate('start_date', '<=', $dayStr)
            ->whereDate('end_date', '>=', $dayStr)
            ->whereColumn('start_date', '!=', 'end_date')
            ->get();

        if ($blocks->isEmpty()) {
            return [];
        }

        $numbers = $blocks->map(fn ($b) => preg_match('/#(\S+)/', (string) $b->reason, $m) ? $m[1] : null)
            ->filter()->unique()->values();
        if ($numbers->isEmpty()) {
            return [];
        }

        $bookings = \App\Models\Booking::query()
            ->whereIn('booking_number', $numbers)
            ->whereIn('status', BookingStatus::boardableStatuses())
            ->when($tourId, fn ($q) => $q->where('tour_id', $tourId))
            // solo quelle la cui partenza NON è questo giorno (già incluse altrove)
            ->whereHas('departure', fn ($d) => $d->whereDate('departure_date', '!=', $dayStr))
            ->with('tour', 'departure')
            ->get();

        return $bookings->map(function ($b) {
            return [
                'tour' => $b->tour,
                'time' => $b->departure ? \Carbon\Carbon::parse($b->departure->start_time)->format('H:i') : '00:00',
                'end_time' => $b->departure && $b->departure->end_time ? \Carbon\Carbon::parse($b->departure->end_time)->format('H:i') : null,
                'count' => 1,
                'departure' => $b->departure,
                'spanning' => true,
                'origin_date' => $b->booking_date,
            ];
        })->all();
    }

    /**
     * Pagina imbarco di una specifica partenza.
     */
    public function show(TourDeparture $departure): View
    {
        $departure->load([
            'tour',
            'bookings' => fn ($q) => $q->whereIn('status', BookingStatus::boardableStatuses()),
            'bookings.seatRecords' => fn ($q) => $q->whereNull('cancelled_at'),
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

        if (! in_array($seat->booking->status, BookingStatus::boardableStatuses(), true)) {
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

        // Aggiorna stato booking se tutti i seat ATTIVI sono imbarcati: una
        // prenotazione imbarcabile (confermata, acconto versato, attesa bonifico)
        // passa a "check-in". I posti disdetti non bloccano il check-in.
        $booking = $seat->booking->loadMissing('seatRecords');
        $activeSeats = $booking->seatRecords->whereNull('cancelled_at');
        $allBoarded = $activeSeats->isNotEmpty() && $activeSeats->every(fn ($s) => $s->boarded_at !== null);
        if ($allBoarded
            && in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::DEPOSIT_PAID, BookingStatus::AWAITING_TRANSFER], true)) {
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
            ->whereNull('cancelled_at') // i partecipanti disdetti non si imbarcano
            ->whereHas('booking', function ($q) use ($departure) {
                $q->where('tour_departure_id', $departure->id)
                    ->whereIn('status', BookingStatus::boardableStatuses());
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
