<?php

namespace App\Http\Controllers\B2B;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Services\PaymentService;
use App\Support\AdminMailer;
use App\Support\B2bContext;
use App\Support\BookingLog;
use App\Support\Settings;
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
    public function __construct(protected PaymentService $paymentService) {}

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

    /**
     * Reinvia al CLIENTE FINALE gli estremi di pagamento: link Stripe se il
     * pagamento è a carta, istruzioni bonifico se è un bonifico. L'agenzia non
     * maneggia la carta: paga il cliente. Riusa il checkout già generato.
     */
    public function resendPayment(Booking $booking): RedirectResponse
    {
        $this->authorizeAgency($booking);

        // Solo per prenotazioni ancora da saldare.
        if (! in_array($booking->status, [BookingStatus::PENDING, BookingStatus::AWAITING_TRANSFER, BookingStatus::DEPOSIT_PAID], true)) {
            return back()->with('warning', 'Per questa prenotazione non risultano pagamenti in sospeso.');
        }

        try {
            $this->paymentService->sendPaymentInstructions($booking);
            BookingLog::info('b2b_payment_resend', 'Estremi di pagamento reinviati al cliente (portale agenzie)', $booking, [
                'agency_id' => $booking->b2b_user_id,
                'to' => $booking->customer_email,
            ]);

            return back()->with('success', 'Estremi di pagamento reinviati a '.$booking->customer_email.'.');
        } catch (\Throwable $e) {
            // L'errore reale (es. auth SMTP 535) va tracciato per diagnosi; all'agenzia
            // mostriamo un messaggio generico.
            BookingLog::failure('b2b_payment_resend', 'Reinvio estremi pagamento fallito (portale agenzie)', $booking, $e, [
                'agency_id' => $booking->b2b_user_id,
                'to' => $booking->customer_email,
            ]);

            return back()->with('error', 'Invio non riuscito. Riprova tra poco o verifica la configurazione email.');
        }
    }

    /**
     * Richiesta di annullamento dall'agenzia: NON annulla la prenotazione. Traccia
     * la richiesta in metadata e notifica l'admin, che confermerà/rifiuterà
     * applicando l'eventuale penale (Fase 10). Idempotente.
     */
    public function requestCancellation(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeAgency($booking);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->storeRequest($booking, 'cancellation', $data['reason'] ?? null);
    }

    /** Richiesta di modifica dall'agenzia: come sopra, va approvata da un admin. */
    public function requestModification(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeAgency($booking);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return $this->storeRequest($booking, 'modification', $data['reason']);
    }

    /**
     * Registra una richiesta (annullamento/modifica) in metadata e notifica
     * l'admin. La richiesta resta "pending" finché un admin non la lavora.
     */
    private function storeRequest(Booking $booking, string $type, ?string $reason): RedirectResponse
    {
        $existing = $booking->metadata['b2b_request'] ?? null;
        if ($existing && ($existing['status'] ?? null) === 'pending') {
            return back()->with('warning', 'C\'è già una richiesta in attesa di approvazione per questa prenotazione.');
        }

        $agency = B2bContext::actingAgency();
        $booking->update([
            'metadata' => array_merge($booking->metadata ?? [], [
                'b2b_request' => [
                    'type' => $type,           // cancellation | modification
                    'status' => 'pending',     // pending | approved | rejected
                    'reason' => $reason,
                    'agency_id' => $agency?->getKey(),
                    'requested_by_user_id' => auth()->id(),
                    'requested_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        BookingLog::info('b2b_request', 'Richiesta '.$type.' dall\'agenzia (in attesa di approvazione)', $booking, [
            'agency_id' => $agency?->getKey(),
            'reason' => $reason,
        ]);

        try {
            AdminMailer::send(new \App\Mail\AdminB2bRequest($booking, $type, $reason, $agency));
        } catch (\Throwable $e) {
            // La notifica è best-effort: la richiesta resta tracciata in metadata.
        }

        $label = $type === 'cancellation' ? 'annullamento' : 'modifica';
        return back()->with('success', "Richiesta di {$label} inviata. Sarà valutata da Solarya, che ti ricontatterà.");
    }

    /** Verifica che la prenotazione appartenga all'agenzia effettiva della sessione. */
    private function authorizeAgency(Booking $booking): void
    {
        $agency = B2bContext::actingAgency();
        abort_if($agency === null || $booking->b2b_user_id !== $agency->getKey(), 403);
    }
}
