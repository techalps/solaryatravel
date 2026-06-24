<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Enums\BookingStatus;
use App\Mail\BookingCancelled;
use App\Mail\AdminBookingCancelled;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Support\AdminMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PaymentService $paymentService,
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

        // Tour "su richiesta" (SOLARYA PRIVATE CRUISE): nessun checkout online.
        // Si prenotano solo contattando lo staff via email/WhatsApp.
        if ($tour->booking_on_request) {
            return redirect()->route('tours.show', $tour->slug)
                ->with('error', 'Questa crociera è su richiesta: contattaci via email o WhatsApp per disponibilità e tariffe.');
        }

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

            $startTime = strlen($time) === 5 ? $time . ':00' : $time;
            $endTime = \Carbon\Carbon::parse($startTime)
                ->addMinutes((int) round(($tour->duration_hours ?? 1) * 60))
                ->format('H:i:s');

            $departure = \App\Models\TourDeparture::firstOrCreate(
                [
                    'tour_id' => $tour->id,
                    'departure_date' => $date,
                    'start_time' => $startTime,
                ],
                [
                    'end_time' => $endTime,
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
            \App\Support\BookingLog::failure('booking_create', 'Creazione prenotazione dal sito fallita', null, $e, [
                'tour_id' => $validated['tour_id'] ?? null,
                'departure_id' => $validated['tour_departure_id'] ?? null,
                'email' => $validated['customer_email'] ?? null,
            ]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Pagina con le istruzioni per il pagamento tramite bonifico bancario.
     * Mostrata dopo una prenotazione con metodo "bonifico" (stato AWAITING_TRANSFER).
     */
    public function bankTransfer(Booking $booking): View|RedirectResponse
    {
        if ($booking->status !== BookingStatus::AWAITING_TRANSFER) {
            return redirect()->route('booking.show', $booking->uuid);
        }

        $booking->loadMissing(['tour', 'departure']);

        // Importo da versare: acconto se previsto, altrimenti intero.
        $amountDue = $booking->payment_type === 'deposit' && $booking->deposit_amount
            ? (float) $booking->deposit_amount
            : (float) $booking->total_amount;

        // Invia (una sola volta) l'email con le istruzioni di bonifico.
        if (! $booking->payment_link_sent_at) {
            try {
                Mail::to($booking->customer_email)->send(new \App\Mail\BookingAwaitingTransfer($booking, $amountDue));
                $booking->update(['payment_link_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::error('Invio email bonifico fallito', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }
        }

        return view('bookings.bank-transfer', [
            'booking' => $booking,
            'amountDue' => $amountDue,
            'bankDetails' => \App\Support\Settings::bankTransferDetails(),
        ]);
    }

    /**
     * Pagina per il pagamento del saldo (prenotazioni con acconto versato).
     */
    public function balance(Booking $booking): View|RedirectResponse
    {
        if (! $booking->hasBalanceDue()) {
            return redirect()->route('booking.show', $booking->uuid)
                ->with('info', 'Per questa prenotazione non risulta un saldo da pagare.');
        }

        $booking->loadMissing(['tour', 'departure']);

        return view('bookings.balance', [
            'booking' => $booking,
            'balanceAmount' => (float) $booking->balance_amount,
        ]);
    }

    /**
     * Avvia il checkout Stripe per il saldo residuo.
     */
    public function payBalance(Booking $booking): RedirectResponse
    {
        if (! $booking->hasBalanceDue()) {
            return redirect()->route('booking.show', $booking->uuid)
                ->with('info', 'Per questa prenotazione non risulta un saldo da pagare.');
        }

        try {
            $session = $this->paymentService->createCheckoutSession($booking, 'balance');
            return redirect($session['url']);
        } catch (\Throwable $e) {
            Log::error('Avvio pagamento saldo fallito', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            return back()->with('error', 'Errore durante l\'avvio del pagamento. Riprova.');
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
            // Calcola la penale PRIMA di cancellare (serve l'importo versato).
            $calc = $this->paymentService->calculateRefundAmount($booking);

            $this->bookingService->cancel($booking, 'Annullata dal cliente');

            // Applica il rimborso secondo policy (Stripe automatico o bonifico manuale).
            $refund = null;
            if (($calc['paid'] ?? 0) > 0) {
                $refund = $this->paymentService->applyCancellationRefund($booking, (float) $calc['amount']);
                if (($refund['amount'] ?? 0) > 0 && ! ($refund['manual'] ?? false)) {
                    $booking->update(['status' => BookingStatus::REFUNDED]);
                }
            }

            // Email al cliente con dettaglio penale/rimborso.
            try {
                Mail::to($booking->customer_email)->send(
                    new BookingCancelled($booking->fresh(), 'Annullata dal cliente', $calc)
                );
            } catch (\Throwable $e) {
                Log::error('Invio mail annullamento (cliente) fallito', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }

            // Notifica admin (con avviso se il rimborso bonifico è da gestire a mano).
            try {
                AdminMailer::send(new AdminBookingCancelled($booking->fresh(), 'Annullata dal cliente', $calc, $refund));
            } catch (\Throwable $e) {
                Log::error('Notifica admin annullamento fallita', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }

            $msg = 'La prenotazione è stata annullata con successo.';
            if ($refund && ($refund['amount'] ?? 0) > 0) {
                $msg .= ' Rimborso previsto: €' . number_format($refund['amount'], 2, ',', '.')
                     . ' (' . $calc['percentage'] . '%).';
            } elseif (($calc['paid'] ?? 0) > 0) {
                $msg .= ' Secondo le condizioni di cancellazione non è previsto alcun rimborso.';
            }

            return redirect()->route('booking.show', $booking->uuid)->with('success', $msg);
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
