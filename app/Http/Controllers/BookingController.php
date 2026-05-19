<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Entry point del flusso di prenotazione: l'utente arriva qui
     * con tour_id (e opzionalmente departure_id) in query string.
     */
    public function start(Request $request): View|RedirectResponse
    {
        // Senza tour selezionato, rimanda al listing tour
        if (!$request->filled('tour')) {
            return redirect()->route('tours.index');
        }
        $tour = \App\Models\Tour::active()
            ->where(function ($q) use ($request) {
                $q->where('id', $request->tour)
                  ->orWhere('slug', $request->tour);
            })
            ->with(['ageBrackets', 'images'])
            ->firstOrFail();

        $departureId = $request->input('departure');
        $departure = null;

        if ($departureId) {
            $departure = $tour->departures()->find($departureId);
        } elseif ($request->filled('date') && $request->filled('time')) {
            // Date+time virtuali generate dai periodi: risolvi (o crea) la riga tour_departures
            $date = $request->input('date');
            $time = $request->input('time');
            // Verifica che la combinazione esista in un periodo del tour
            $period = $tour->periods()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->get()
                ->first(function ($p) use ($date, $time) {
                    $weekdays = is_array($p->weekdays) && !empty($p->weekdays) ? $p->weekdays : [1,2,3,4,5,6,7];
                    $times = is_array($p->times) && !empty($p->times) ? $p->times : ['10:00'];
                    $iso = \Carbon\Carbon::parse($date)->isoWeekday();
                    return in_array($iso, array_map('intval', $weekdays), true)
                        && in_array(substr($time, 0, 5), array_map(fn ($t) => substr($t, 0, 5), $times), true);
                });

            if (!$period) {
                return redirect()->route('tours.show', $tour->slug)
                    ->with('error', 'La data o l\'orario selezionato non è disponibile.');
            }

            $departure = \App\Models\TourDeparture::firstOrCreate(
                [
                    'tour_id' => $tour->id,
                    'departure_date' => $date,
                    'start_time' => strlen($time) === 5 ? $time . ':00' : $time,
                ],
                [
                    'status' => 'scheduled',
                    'price_modifier' => 1.0,
                ]
            );
        }

        return view('bookings.create', compact('tour', 'departure'));
    }

    /**
     * Crea una nuova prenotazione (chiamato dal form pubblico).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'tour_departure_id' => 'required|exists:tour_departures,id',
            'brackets' => 'required|array',
            'brackets.*' => 'integer|min:0',
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:addons,id',
            'discount_code' => 'nullable|string|max:50',
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'special_requests' => 'nullable|string|max:1000',
            'terms' => 'accepted',
        ]);

        try {
            $booking = $this->bookingService->create($validated, 'website');
            return redirect()->route('payment.show', $booking->uuid);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Genera/serve il QR code della prenotazione.
     */
    public function qrCode(Booking $booking)
    {
        $code = $booking->qr_code ?? $booking->uuid;
        $qr = app(\App\Services\QrCodeService::class)->png($code, 300);
        return response($qr)->header('Content-Type', 'image/png');
    }

    /**
     * Show the booking confirmation page.
     */
    public function confirmation(Booking $booking): View
    {
        $booking->load(['tour', 'departure', 'addons.addon']);
        return view('bookings.confirmation', compact('booking'));
    }

    /**
     * Pagina con i biglietti (1 QR per passeggero) stampabili.
     */
    public function tickets(Booking $booking): View
    {
        $booking->load(['tour', 'departure', 'seatRecords.ageBracket', 'seatRecords.catamaran']);
        return view('bookings.tickets', compact('booking'));
    }

    /**
     * Restituisce il PNG del QR di un singolo posto.
     */
    public function seatQr(BookingSeat $seat)
    {
        $png = app(\App\Services\QrCodeService::class)->png($seat->qr_code, 300);
        return response($png)->header('Content-Type', 'image/png');
    }

    /**
     * Show booking details for authenticated users.
     */
    public function show(Booking $booking): View
    {
        $booking->load(['tour', 'departure', 'addons.addon', 'payments', 'checkIns', 'seatRecords.catamaran', 'seatRecords.ageBracket']);

        if (auth()->check()) {
            if (auth()->user()->role !== 'admin' &&
                $booking->user_id !== auth()->id() &&
                $booking->customer_email !== auth()->user()->email) {
                abort(403);
            }
        }

        return view('bookings.show', compact('booking'));
    }

    /**
     * Show the booking verification form (for guests).
     */
    public function verify(string $bookingNumber): View
    {
        $booking = Booking::where('booking_number', $bookingNumber)->firstOrFail();

        return view('bookings.verify', compact('booking'));
    }

    /**
     * Verify booking access via email.
     */
    public function verifyEmail(Request $request, string $bookingNumber): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $booking = Booking::where('booking_number', $bookingNumber)
            ->where('customer_email', $request->email)
            ->firstOrFail();

        // Generate access token and store in session
        $accessToken = hash('sha256', $booking->id . $booking->customer_email . now()->timestamp);
        $booking->update(['access_token' => $accessToken]);
        session(['booking_access_' . $bookingNumber => $accessToken]);

        return redirect()->route('bookings.show', $bookingNumber);
    }

    /**
     * Show all bookings for the authenticated user.
     */
    public function myBookings(): View
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->orWhere('customer_email', auth()->user()->email)
            ->with(['tour', 'departure'])
            ->orderBy('booking_date', 'desc')
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        if (auth()->check() && auth()->user()->role !== 'admin') {
            if ($booking->user_id !== auth()->id() &&
                $booking->customer_email !== auth()->user()->email) {
                abort(403);
            }
        }

        try {
            $this->bookingService->cancel($booking, 'Annullata dal cliente');
            return redirect()
                ->route('booking.show', $booking->uuid)
                ->with('success', 'La prenotazione è stata annullata con successo.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Request booking modification.
     */
    public function requestModification(Request $request, string $bookingNumber): RedirectResponse
    {
        $booking = Booking::where('booking_number', $bookingNumber)->firstOrFail();

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // TODO: Send modification request notification to admin

        return redirect()
            ->back()
            ->with('success', 'La tua richiesta di modifica è stata inviata. Ti contatteremo presto.');
    }
}
