<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Http\Controllers\Controller;
use App\Mail\BookingCancelled;
use App\Mail\BookingPaymentLink;
use App\Mail\BookingRefunded;
use App\Mail\BookingTickets;
use App\Mail\AdminBookingCancelled;
use App\Mail\AdminBookingRefunded;
use App\Support\Settings;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $query = Booking::with(['tour', 'departure', 'payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tour')) {
            $query->where('tour_id', $request->tour);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->date_to);
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

        $bookings = $query->orderBy('booking_date', 'desc')->paginate(20)->withQueryString();
        $tours = Tour::orderBy('name')->get();
        $statuses = BookingStatus::cases();
        $stats = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();

        return view('admin.bookings.index', compact('bookings', 'tours', 'statuses', 'stats'));
    }

    /**
     * Form admin per creare una nuova prenotazione manualmente.
     */
    public function create(Request $request): View
    {
        $tours = Tour::with('ageBrackets')->orderBy('name')->get();

        $selectedTour = null;
        $departures = collect();

        if ($request->filled('tour_id')) {
            $selectedTour = $tours->firstWhere('id', (int) $request->tour_id);
            if ($selectedTour) {
                // Admin: tutte le partenze (anche passate) per consentire
                // l'inserimento retroattivo. Più recenti in cima.
                $departures = $selectedTour->departures()
                    ->orderByDesc('departure_date')
                    ->orderBy('start_time')
                    ->get();
            }
        }

        $statuses = BookingStatus::cases();

        return view('admin.bookings.create', compact('tours', 'selectedTour', 'departures', 'statuses'));
    }

    /**
     * JSON: partenze future di un tour (per popolamento dinamico del form).
     */
    public function departuresJson(Tour $tour, \App\Services\PricingService $pricing)
    {
        // Admin: tutte le partenze (anche passate) per l'inserimento retroattivo.
        $departures = $tour->departures()
            ->orderByDesc('departure_date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($d) use ($tour, $pricing) {
                $period = $pricing->resolvePeriod($tour, $d->departure_date);
                $modifier = (float) $d->price_modifier;

                // Fasce d'età valide per QUESTA data (i bracket sono per-periodo),
                // con prezzo già modulato dal price_modifier della partenza.
                $brackets = $pricing->resolveBrackets($tour, $d->departure_date)
                    ->map(fn ($b) => [
                        'id' => $b->id,
                        'label' => $b->label,
                        'price' => round((float) $b->price * $modifier, 2),
                        'counts_as_seat' => (bool) $b->counts_as_seat,
                        'range_label' => $b->range_label,
                        'min_age' => (int) ($b->min_age ?? 0),
                        'max_age' => $b->max_age !== null ? (int) $b->max_age : null,
                    ])->values();

                return [
                    'id' => $d->id,
                    'iso_date' => \Carbon\Carbon::parse($d->departure_date)->format('Y-m-d'),
                    'date' => \Carbon\Carbon::parse($d->departure_date)->format('d/m/Y'),
                    'time' => \Carbon\Carbon::parse($d->start_time)->format('H:i'),
                    'end_time' => $d->end_time ? \Carbon\Carbon::parse($d->end_time)->format('H:i') : null,
                    'available' => $d->seats_available,
                    'capacity' => $d->capacity,
                    'price_modifier' => $modifier,
                    'adult_price' => $period
                        ? round((float) $period->base_price * $modifier, 2)
                        : null,
                    'is_past' => \Carbon\Carbon::parse($d->departure_date)->lt(now()->startOfDay()),
                    'status' => $d->status,
                    'brackets' => $brackets,
                ];
            });

        return response()->json([
            'tour' => ['id' => $tour->id, 'name' => $tour->name],
            'departures' => $departures,
        ]);
    }

    /**
     * Salva la prenotazione creata da admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $statusValues = array_column(BookingStatus::cases(), 'value');

        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'tour_departure_id' => 'required|exists:tour_departures,id',
            // Partecipanti (come nel frontend): adulti con nome/cognome, bambini con DOB.
            'adults' => 'required|array|min:1',
            'adults.*.first_name' => 'required|string|max:100',
            'adults.*.last_name' => 'required|string|max:100',
            'children' => 'nullable|array',
            'children.*.dob' => 'required_with:children|date|before:today',
            'children.*.first_name' => 'required_with:children|string|max:100',
            'children.*.last_name' => 'required_with:children|string|max:100',
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:addons,id',
            'discount_code' => 'nullable|string|max:50',
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_country' => 'nullable|string|max:5',
            'customer_tax_code' => 'nullable|string|max:16',
            'special_requests' => 'nullable|string|max:1000',
            'status' => 'required|in:' . implode(',', $statusValues),
        ]);

        $status = BookingStatus::from($validated['status']);

        // Risolvi ogni bambino sul bracket della data (in base al DOB) e prepara
        // la lista guests nell'ordine atteso dal service: adulti, poi bambini.
        $adults = array_values($validated['adults']);
        $children = array_values($validated['children'] ?? []);

        $resolvedChildren = [];
        $guests = [];
        foreach ($adults as $i => $a) {
            $guests[] = [
                'first_name' => trim($a['first_name']),
                'last_name' => trim($a['last_name']),
                'tax_code' => $i === 0 && !empty($validated['customer_tax_code'])
                    ? strtoupper(trim($validated['customer_tax_code']))
                    : null,
            ];
        }
        foreach ($children as $c) {
            // Il bracket viene risolto dal service in base al DOB; qui passiamo solo DOB.
            $resolvedChildren[] = ['dob' => $c['dob']];
            $guests[] = [
                'first_name' => trim($c['first_name']),
                'last_name' => trim($c['last_name']),
            ];
        }

        $payload = [
            'tour_id' => $validated['tour_id'],
            'tour_departure_id' => $validated['tour_departure_id'],
            'adults_count' => count($adults),
            'children' => $resolvedChildren,
            'addons' => $validated['addons'] ?? [],
            'discount_code' => $validated['discount_code'] ?? null,
            'customer_first_name' => $validated['customer_first_name'],
            'customer_last_name' => $validated['customer_last_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_country' => $validated['customer_country'] ?? 'IT',
            'special_requests' => $validated['special_requests'] ?? null,
            'guests' => $guests,
            'status' => $status,           // stato scelto dall'admin
            'admin_override' => true,      // consente partenze passate (retroattive)
        ];

        try {
            $booking = $this->bookingService->create($payload, 'admin');

            // Timestamp coerenti con lo stato scelto (utile per report e dettaglio).
            // Per le retroattive usa la data della partenza se è già passata.
            $eventTime = $booking->booking_date && $booking->booking_date->isPast()
                ? $booking->booking_date
                : now();
            $stamps = match ($status) {
                BookingStatus::CONFIRMED, BookingStatus::DEPOSIT_PAID, BookingStatus::AWAITING_TRANSFER
                    => ['confirmed_at' => $booking->confirmed_at ?? $eventTime],
                BookingStatus::CHECKED_IN
                    => ['confirmed_at' => $booking->confirmed_at ?? $eventTime, 'checked_in_at' => $eventTime],
                BookingStatus::COMPLETED
                    => ['confirmed_at' => $booking->confirmed_at ?? $eventTime, 'completed_at' => $eventTime],
                BookingStatus::CANCELLED, BookingStatus::REFUNDED
                    => ['cancelled_at' => now()],
                default => [],
            };
            if ($stamps) {
                $booking->update($stamps);
            }

            // Email in base allo stato scelto:
            //  - PENDING → invia il link di pagamento Stripe al cliente;
            //  - CONFIRMED → pagamento già incassato off-platform: invia i biglietti;
            //  - altri stati (deposito/bonifico/completata/...) → nessuna email automatica.
            if ($status === BookingStatus::PENDING) {
                $emailSent = $this->sendPaymentLinkEmail($booking);
                $message = $emailSent
                    ? 'Prenotazione creata. Email con link di pagamento inviata al cliente.'
                    : 'Prenotazione creata, ma l\'invio dell\'email è fallito (controlla il log).';
            } elseif ($status === BookingStatus::CONFIRMED) {
                $this->sendTicketsEmail($booking);
                $message = 'Prenotazione creata e confermata. Biglietti inviati al cliente.';
            } else {
                $message = 'Prenotazione creata con stato "' . $status->label() . '".';
            }

            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Genera la sessione Stripe e spedisce l'email con il link di pagamento.
     */
    protected function sendPaymentLinkEmail(Booking $booking): bool
    {
        try {
            $session = $this->paymentService->createCheckoutSession($booking);
            $booking->update([
                'checkout_url' => $session['url'],
                'payment_link_sent_at' => now(),
            ]);
            Mail::to($booking->customer_email)->send(new BookingPaymentLink($booking, $session['url']));
            return true;
        } catch (\Throwable $e) {
            Log::error('Invio email link pagamento fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Spedisce l'email con i biglietti / QR (idempotente).
     */
    protected function sendTicketsEmail(Booking $booking): bool
    {
        if ($booking->tickets_sent_at) {
            return true;
        }
        try {
            Mail::to($booking->customer_email)->send(new BookingTickets($booking));
            $booking->update(['tickets_sent_at' => now()]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Invio email biglietti fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'tour',
            'departure',
            'addons.addon',
            'payments',
            'checkIns',
            'discountCode',
            'seatRecords.catamaran',
            'seatRecords.ageBracket',
        ]);
        $catamarans = Catamaran::active()->ordered()->get();
        return view('admin.bookings.show', compact('booking', 'catamarans'));
    }

    public function edit(Booking $booking): View
    {
        $booking->load(['tour', 'departure', 'seatRecords.catamaran', 'seatRecords.ageBracket']);
        $catamarans = Catamaran::active()->ordered()->get();
        $statuses = BookingStatus::cases();
        return view('admin.bookings.edit', compact('booking', 'catamarans', 'statuses'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:' . implode(',', array_column(BookingStatus::cases(), 'value')),
            'special_requests' => 'nullable|string|max:1000',
            'customer_phone' => 'nullable|string|max:30',
        ]);
        $booking->update($validated);
        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Prenotazione aggiornata.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();
        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione eliminata.');
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::PENDING) {
            return back()->with('error', 'Solo le prenotazioni in attesa possono essere confermate.');
        }
        $booking->update(['status' => BookingStatus::CONFIRMED, 'confirmed_at' => now()]);
        return back()->with('success', 'Prenotazione confermata.');
    }

    /**
     * Conferma l'incasso di un bonifico: registra il pagamento, aggiorna lo stato
     * (CONFIRMED, oppure DEPOSIT_PAID se era un acconto con saldo residuo) e
     * triggera l'invio biglietti tramite l'Observer sul passaggio a CONFIRMED.
     */
    public function confirmTransfer(Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::AWAITING_TRANSFER) {
            return back()->with('error', 'Questa prenotazione non è in attesa di bonifico.');
        }

        // Importo incassato: acconto se previsto, altrimenti intero.
        $isDeposit = $booking->payment_type === 'deposit' && (float) $booking->deposit_amount > 0;
        $amount = $isDeposit ? (float) $booking->deposit_amount : (float) $booking->total_amount;

        // Registra il pagamento bonifico.
        Payment::create([
            'booking_id' => $booking->id,
            'gateway' => 'bank_transfer',
            'amount' => $amount,
            'currency' => $booking->currency ?: 'EUR',
            'status' => PaymentStatus::SUCCEEDED,
            'payment_method_type' => 'bank_transfer',
            'paid_at' => now(),
        ]);

        $newStatus = ($isDeposit && (float) $booking->balance_amount > 0)
            ? BookingStatus::DEPOSIT_PAID
            : BookingStatus::CONFIRMED;

        $booking->update([
            'amount_paid' => (float) $booking->amount_paid + $amount,
            'status' => $newStatus,
            'confirmed_at' => $booking->confirmed_at ?? now(),
        ]);

        // L'Observer su CONFIRMED invia biglietti + notifica admin.
        return back()->with('success', 'Bonifico confermato. La prenotazione è ora ' . $newStatus->label() . '.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            // penalty = applica penale da policy · full = rimborso totale · custom = importo libero · none = nessun rimborso
            'refund_mode' => 'nullable|in:penalty,full,custom,none',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $reason = $request->filled('reason') ? $request->input('reason') : 'Annullata da amministratore';
            $mode = $validated['refund_mode'] ?? 'penalty';

            // Calcola sempre la situazione (importo versato + penale da policy).
            $calc = $this->paymentService->calculateRefundAmount($booking);
            $paid = (float) ($calc['paid'] ?? 0);

            // Determina l'importo da rimborsare in base alla scelta admin.
            $refundAmount = match ($mode) {
                'full'   => $paid,
                'custom' => min((float) ($validated['refund_amount'] ?? 0), $paid),
                'none'   => 0.0,
                default  => (float) ($calc['amount'] ?? 0), // penalty
            };

            $this->bookingService->cancel($booking, $reason);

            $refund = null;
            if ($paid > 0) {
                $refund = $this->paymentService->applyCancellationRefund($booking, $refundAmount, $reason);
                if (($refund['amount'] ?? 0) > 0 && ! ($refund['manual'] ?? false)) {
                    $booking->update(['status' => BookingStatus::REFUNDED]);
                }
            }

            // Per l'email cliente, riflette l'importo effettivamente deciso dall'admin.
            $emailCalc = array_merge($calc, [
                'amount' => $refundAmount,
                'penalty' => round(max(0, $paid - $refundAmount), 2),
                'percentage' => $paid > 0 ? (int) round($refundAmount / $paid * 100) : 0,
            ]);

            try {
                Mail::to($booking->customer_email)->send(new BookingCancelled($booking->fresh(), $reason, $emailCalc));
            } catch (\Throwable $e) {
                Log::error('Invio mail annullamento fallito', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }

            try {
                \App\Support\AdminMailer::send(new AdminBookingCancelled($booking->fresh(), $reason, $emailCalc, $refund));
            } catch (\Throwable $e) {
                Log::error('Notifica admin annullamento fallita', ['booking' => $booking->booking_number, 'error' => $e->getMessage()]);
            }

            $msg = 'Prenotazione annullata. Email inviata al cliente.';
            if ($refund && ($refund['amount'] ?? 0) > 0) {
                $msg .= ($refund['manual'] ?? false)
                    ? ' Rimborso bonifico di €' . number_format($refund['amount'], 2, ',', '.') . ' da effettuare manualmente.'
                    : ' Rimborso di €' . number_format($refund['amount'], 2, ',', '.') . ' eseguito su Stripe.';
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function refund(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0|max:' . ((float) $booking->total_amount),
            'note' => 'nullable|string|max:500',
        ]);

        $amount = isset($validated['amount']) && $validated['amount'] !== null && $validated['amount'] !== ''
            ? (float) $validated['amount']
            : (float) $booking->total_amount;
        $note = $validated['note'] ?? null;

        // 1) Aggiorna la prenotazione
        $booking->update(['status' => BookingStatus::REFUNDED]);

        // 2) Rifletti il rimborso sui Payment del booking così la pagina Pagamenti
        //    mostra lo stato corretto (refunded / partially_refunded).
        $payments = $booking->payments()
            ->whereIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->orderBy('paid_at')
            ->get();

        $remaining = $amount;
        foreach ($payments as $payment) {
            if ($remaining <= 0) {
                break;
            }
            $alreadyRefunded = (float) $payment->refunded_amount;
            $refundable = max(0, (float) $payment->amount - $alreadyRefunded);
            if ($refundable <= 0) {
                continue;
            }
            $apply = min($remaining, $refundable);
            $totalRefunded = $alreadyRefunded + $apply;
            $isFull = $totalRefunded >= (float) $payment->amount - 0.005;

            $payment->update([
                'status' => $isFull ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED,
                'refunded_amount' => round($totalRefunded, 2),
                'refund_reason' => $note ?: 'Rimborso da admin (booking)',
                'refunded_at' => now(),
            ]);

            $remaining -= $apply;
        }

        // 3) Mail al cliente
        try {
            Mail::to($booking->customer_email)->send(new BookingRefunded($booking->fresh(), $amount, $note));
        } catch (\Throwable $e) {
            Log::error('Invio mail rimborso fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Rimborso registrato sulla prenotazione, ma l\'invio email è fallito (controlla il log).');
        }

        try {
            \App\Support\AdminMailer::send(new AdminBookingRefunded($booking->fresh(), $amount, $note));
        } catch (\Throwable $e) {
            Log::error('Notifica admin rimborso fallita', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Rimborso registrato (€' . number_format($amount, 2, ',', '.') . '). Email inviata al cliente.');
    }

    public function resendConfirmation(Booking $booking): RedirectResponse
    {
        try {
            Mail::to($booking->customer_email)->send(new BookingTickets($booking));
            $booking->update(['tickets_sent_at' => now()]);
            return back()->with('success', 'Biglietti reinviati a ' . $booking->customer_email . '.');
        } catch (\Throwable $e) {
            Log::error('Reinvio biglietti fallito', [
                'booking' => $booking->booking_number,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Invio fallito: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        return back()->with('info', 'Export non ancora implementato.');
    }

    /**
     * Sposta un singolo posto su un altro catamarano (richiesta AJAX o form).
     */
    public function moveSeat(Request $request, Booking $booking, BookingSeat $seat): RedirectResponse
    {
        if ($seat->booking_id !== $booking->id) {
            abort(404);
        }
        $request->validate(['catamaran_id' => 'required|exists:catamarans,id']);
        try {
            $this->bookingService->moveSeat($seat, (int) $request->catamaran_id);
            return back()->with('success', 'Posto spostato.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
