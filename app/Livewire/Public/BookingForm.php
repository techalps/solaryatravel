<?php

namespace App\Livewire\Public;

use App\Models\Addon;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BookingForm extends Component
{
    public Tour $tour;
    public ?TourDeparture $departure = null;

    /**
     * Mappa date disponibili => array di orari.
     * Quando passata in mount(), il widget mostra il selettore data/orario.
     *
     * @var array<string, array<int, string>>
     */
    public array $availableDates = [];

    /** Data scelta (YYYY-MM-DD) — usata solo in modalità self-pick. */
    public string $selectedDate = '';

    /** Orario scelto (HH:MM) — usato solo in modalità self-pick. */
    public string $selectedTime = '';

    /** Adulti: minimo 1 obbligatorio */
    public int $adultsCount = 1;

    /**
     * Nome/cognome + documento d'identità di ogni adulto. La lunghezza dell'array
     * è sincronizzata con $adultsCount. Il primo adulto è il prenotante
     * (intestatario). Ogni passeggero DEVE avere un documento d'identità.
     *
     * Del documento si chiedono solo TIPO e NUMERO. Scadenza, codice fiscale e
     * luogo di rilascio (stato/provincia/comune) sono stati rimossi da tutti i
     * form, admin compreso: troppi campi in fase di prenotazione. Le colonne a
     * DB restano per non perdere lo storico.
     *
     * @var array<int, array{first_name:string,last_name:string,doc_type:string,doc_number:string}>
     */
    public array $adults = [
        ['first_name' => '', 'last_name' => '', 'doc_type' => '', 'doc_number' => ''],
    ];

    /**
     * Bambini: data di nascita + nome/cognome + documento (obbligatorio anch'esso).
     * La data di nascita resta perché determina la fascia d'età e quindi il prezzo.
     *
     * @var array<int, array{dob:string,first_name:string,last_name:string,doc_type:string,doc_number:string}>
     */
    public array $children = [];

    /** Struttura vuota di un blocco documento (riusata per adulti e bambini). */
    private static function emptyDocument(): array
    {
        return ['doc_type' => '', 'doc_number' => ''];
    }

    /**
     * Normalizza i campi documento di un passeggero nel formato atteso dal
     * BookingService.
     *
     * @param  array<string,mixed>  $p
     * @return array{doc_type:string,doc_number:string}
     */
    private function documentFor(array $p): array
    {
        return [
            'doc_type' => (string) ($p['doc_type'] ?? ''),
            'doc_number' => strtoupper(trim((string) ($p['doc_number'] ?? ''))),
        ];
    }

    /** Data e ora della partenza selezionata (null se non ancora risolta). */
    #[Computed]
    public function departureAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->departure) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse(
            $this->departure->departure_date->toDateString()
            .' '.\Illuminate\Support\Carbon::parse($this->departure->start_time)->format('H:i:s')
        );
    }

    /**
     * L'acconto è proponibile per questa partenza? (attivo + anticipo minimo)
     * Usato dalla view per mostrare o nascondere la scelta acconto/intero.
     */
    #[Computed]
    public function depositAvailable(): bool
    {
        return \App\Support\Settings::depositAvailableFor($this->departureAt);
    }

    /** @var array<int> addon ids selezionati */
    public array $selectedAddons = [];

    public string $discountCode = '';
    public ?string $discountFeedback = null;
    public bool $discountValid = false;

    // Dati cliente / prenotante
    public string $customer_first_name = '';
    public string $customer_last_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    /**
     * Codice fiscale dell'intestatario.
     *
     * NON è più un campo del form nei canali di vendita: dopo il test i clienti
     * hanno segnalato troppi campi in fase di prenotazione. La proprietà resta
     * per non rompere il payload del BookingService (e perché l'admin può
     * comunque valorizzare il CF su una prenotazione esistente): se vuota, a DB
     * finisce null.
     */
    public string $customer_tax_code = '';
    public string $special_requests = '';
    public bool $terms = false;

    /** Creazione account opzionale (solo per ospiti non loggati). */
    public bool $wantsAccount = false;
    public string $accountPassword = '';
    public string $accountPassword_confirmation = '';

    /** Metodo di pagamento scelto: 'card' | 'bank_transfer'. */
    public string $paymentMethod = 'card';
    /** Importo: 'full' (intero) | 'deposit' (acconto), se l'acconto è abilitato. */
    public string $paymentChoice = 'full';

    public ?string $errorMessage = null;

    /**
     * Modalità Portale Agenzie: stesso form del cliente, ma la prenotazione è
     * compilata da un'agenzia PER un suo cliente. Cambia: niente pre-popolamento
     * dei dati con l'utente loggato (è l'agenzia, non il cliente), attribuzione
     * all'agenzia (source=b2b, attribution_source=b2b_portal) e redirect verso
     * il portale b2b invece del checkout pubblico.
     */
    public bool $b2bMode = false;

    /**
     * Modalità Widget: il form è caricato nel widget incorporato sul sito di
     * un'agenzia (iframe). È un normale flusso cliente referenziato (?ref=TOKEN),
     * ma serve a marcare l'attribuzione come 'b2b_widget' anziché 'b2b_referral'.
     */
    public bool $widgetMode = false;

    /** Mostra il modale di avviso "il gruppo verrà diviso su più catamarani". */
    public bool $showSplitModal = false;

    /**
     * Prenotazione a 0€ richiesta dall'agenzia (posti omaggio).
     *
     * Disponibile SOLO in modalità B2B e solo alle agenzie con il permesso
     * attivo (User::canBookComplimentary()): è una concessione per singola
     * agenzia, come i posti omaggio dell'admin. Il permesso viene riverificato
     * lato server al submit, quindi manomettere il campo non serve a nulla.
     */
    public bool $complimentary = false;

    /** Motivo dell'omaggio: tracciato sulla prenotazione per i controlli. */
    public string $complimentaryReason = '';

    /**
     * L'agenzia che sta operando può registrare prenotazioni a 0€?
     * Unica fonte di verità, usata sia dalla vista sia dalla validazione.
     */
    #[Computed]
    public function canBookComplimentary(): bool
    {
        if (! $this->b2bMode) {
            return false;
        }

        return (bool) \App\Support\B2bContext::actingAgency()?->canBookComplimentary();
    }

    public function mount(Tour $tour, ?TourDeparture $departure = null, array $availableDates = [], bool $b2bMode = false, bool $widgetMode = false): void
    {
        $this->tour = $tour;
        $this->departure = $departure;
        $this->availableDates = $availableDates;
        $this->b2bMode = $b2bMode;
        $this->widgetMode = $widgetMode;

        // Se la partenza è già passata (es. /prenota?date=...), pre-popola data/orario per consistenza
        if ($departure) {
            $this->selectedDate = \Carbon\Carbon::parse($departure->departure_date)->toDateString();
            $this->selectedTime = \Carbon\Carbon::parse($departure->start_time)->format('H:i');
        }

        // In modalità B2B l'utente loggato è l'agenzia, NON il cliente: i dati
        // cliente vanno inseriti dall'operatore, non pre-popolati.
        if (auth()->check() && ! $this->b2bMode) {
            $u = auth()->user();
            $parts = explode(' ', $u->name ?? '', 2);
            $this->customer_first_name = $parts[0] ?? '';
            $this->customer_last_name = $parts[1] ?? '';
            $this->customer_email = $u->email ?? '';
            // Il telefono del profilo va precompilato come gli altri dati: senza
            // di esso la prenotazione resta senza numero anche per chi l'ha già
            // dato in fase di registrazione (e il contatto WhatsApp non compare).
            $this->customer_phone = $u->phone ?? '';
        }

        $this->adults = [[
            'first_name' => $this->customer_first_name,
            'last_name' => $this->customer_last_name,
        ]];
    }

    // ===== Date/time pickers (modalità self-pick) =====

    /**
     * Chiamato dal datepicker (Flatpickr) o dal radio orari.
     * Se la data ha un solo orario disponibile, lo seleziona in automatico.
     */
    public function pickDate(string $date): void
    {
        if (!isset($this->availableDates[$date])) {
            $this->selectedDate = '';
            $this->selectedTime = '';
            $this->departure = null;
            return;
        }
        $this->selectedDate = $date;
        $times = $this->availableDates[$date] ?? [];
        if (count($times) === 1) {
            $this->selectedTime = $times[0];
            $this->resolveDeparture();
        } else {
            // Più orari: aspetta che l'utente scelga
            $this->selectedTime = '';
            $this->departure = null;
        }
    }

    public function pickTime(string $time): void
    {
        if (!$this->selectedDate) {
            return;
        }
        $allowed = $this->availableDates[$this->selectedDate] ?? [];
        if (!in_array($time, $allowed, true)) {
            return;
        }
        $this->selectedTime = $time;
        $this->resolveDeparture();
    }

    /**
     * Risolve/crea la partenza dal date+time selezionati.
     * Usa la stessa logica del BookingController::start (firstOrCreate
     * della tour_departures dopo aver verificato che il periodo copre data+orario).
     */
    protected function resolveDeparture(): void
    {
        if (!$this->selectedDate || !$this->selectedTime) {
            $this->departure = null;
            return;
        }

        $period = $this->tour->periods()
            ->whereDate('start_date', '<=', $this->selectedDate)
            ->whereDate('end_date', '>=', $this->selectedDate)
            ->get()
            ->first(function ($p) {
                $weekdays = is_array($p->weekdays) && !empty($p->weekdays) ? $p->weekdays : [1,2,3,4,5,6,7];
                $times = is_array($p->times) && !empty($p->times) ? $p->times : ['10:00'];
                $iso = \Carbon\Carbon::parse($this->selectedDate)->isoWeekday();
                return in_array($iso, array_map('intval', $weekdays), true)
                    && in_array($this->selectedTime, array_map(fn ($t) => substr($t, 0, 5), $times), true);
            });

        if (!$period) {
            $this->departure = null;
            return;
        }

        $startTime = strlen($this->selectedTime) === 5 ? $this->selectedTime . ':00' : $this->selectedTime;
        $endTime = \Carbon\Carbon::parse($startTime)
            ->addMinutes((int) round(($this->tour->duration_hours ?? 1) * 60))
            ->format('H:i:s');

        $this->departure = TourDeparture::firstOrCreate(
            [
                'tour_id' => $this->tour->id,
                'departure_date' => $this->selectedDate,
                'start_time' => $startTime,
            ],
            [
                'end_time' => $endTime,
                'status' => 'scheduled',
                'price_modifier' => 1.0,
            ]
        );
    }

    // ===== Computed =====

    /**
     * Prezzo adulto (dal periodo che copre la data di partenza), modulato dal price_modifier.
     */
    #[Computed]
    public function adultUnitPrice(): float
    {
        if (!$this->departure) {
            return 0.0;
        }
        $period = app(PricingService::class)->resolvePeriod($this->tour, $this->departure->departure_date);
        if (!$period) {
            return 0.0;
        }
        return (float) $period->base_price * (float) $this->departure->price_modifier;
    }

    /**
     * Riduzioni bambini disponibili (brackets) per la data della partenza.
     */
    #[Computed]
    public function childBrackets(): Collection
    {
        if (!$this->departure) {
            return collect();
        }
        return app(PricingService::class)
            ->resolveBrackets($this->tour, $this->departure->departure_date);
    }

    /**
     * Bambini risolti: ogni voce ha ['dob','age','bracket','unit_price','ready'].
     * `ready` indica che la DOB è valida e mappata su un bracket.
     */
    #[Computed]
    public function resolvedChildren(): array
    {
        $out = [];
        if (!$this->departure) {
            return $out;
        }
        $brackets = $this->childBrackets;
        $depDate = Carbon::parse($this->departure->departure_date);
        $modifier = (float) $this->departure->price_modifier;

        foreach ($this->children as $idx => $child) {
            $dob = $child['dob'] ?? '';
            $entry = [
                'index' => $idx,
                'dob' => $dob,
                'age' => null,
                'bracket' => null,
                'unit_price' => 0.0,
                'ready' => false,
                'error' => null,
            ];

            if ($dob === '') {
                $entry['error'] = null; // semplicemente non ancora compilato
                $out[] = $entry;
                continue;
            }

            try {
                $dobCarbon = Carbon::parse($dob);
            } catch (\Throwable) {
                $entry['error'] = __('booking.errors.invalid_date');
                $out[] = $entry;
                continue;
            }

            if ($dobCarbon->gt($depDate)) {
                $entry['error'] = __('booking.errors.dob_after_departure');
                $out[] = $entry;
                continue;
            }

            $age = (int) floor($dobCarbon->diffInYears($depDate));
            $entry['age'] = $age;

            $bracket = app(PricingService::class)->resolveBracketForDob($brackets, $dob, $this->departure->departure_date);
            if (!$bracket) {
                $entry['error'] = "Nessuna riduzione disponibile per questa età ({$age} anni).";
                $out[] = $entry;
                continue;
            }

            $entry['bracket'] = $bracket;
            $entry['unit_price'] = (float) $bracket->price * $modifier;
            $entry['ready'] = true;
            $out[] = $entry;
        }

        return $out;
    }

    #[Computed]
    public function addons(): Collection
    {
        return Addon::active()->ordered()->get();
    }

    #[Computed]
    public function pricing(): array
    {
        $empty = [
            'base_price' => 0, 'addons_total' => 0, 'discount_amount' => 0,
            'discount_code_id' => null, 'subtotal' => 0, 'tax_rate' => 0,
            'tax_amount' => 0, 'total_amount' => 0, 'total_seats' => 0,
            'counting_seats' => 0, 'brackets' => [], 'adults_count' => $this->adultsCount,
            'adult_unit_price' => 0, 'unresolved_children' => 0,
        ];

        if (!$this->departure) {
            return $empty;
        }

        // Costruisci array bambini solo con quelli risolti correttamente
        $resolved = collect($this->resolvedChildren)
            ->where('ready', true)
            ->map(fn ($c) => ['dob' => $c['dob'], 'bracket_id' => $c['bracket']->id])
            ->values()
            ->all();

        return app(PricingService::class)->calculateForParticipants(
            $this->tour,
            $this->departure,
            $this->adultsCount,
            $resolved,
            $this->selectedAddons,
            $this->discountValid ? $this->discountCode : null,
            // Omaggio: TUTTI i posti a 0€, extra compresi. Il conteggio passa dal
            // permesso (complimentarySeats()), non dal solo campo del form.
            $this->complimentarySeats(),
            $this->complimentarySeats() > 0
        );
    }

    /**
     * Quanti posti sono omaggio: tutti, oppure nessuno.
     *
     * Il permesso è riverificato qui, quindi un $complimentary manomesso da un
     * agenzia non autorizzata non produce alcuno sconto — né nel riepilogo a
     * schermo né al salvataggio, che passa per questa stessa funzione.
     */
    private function complimentarySeats(): int
    {
        if (! $this->complimentary || ! $this->canBookComplimentary()) {
            return 0;
        }

        return max(0, (int) $this->totalSelected);
    }

    #[Computed]
    public function totalSelected(): int
    {
        return $this->adultsCount + count($this->children);
    }

    /**
     * Posti totali ancora disponibili per la partenza selezionata (somma su
     * tutti i catamarani disponibili). Limita quanti partecipanti si possono
     * selezionare nello stepper. Senza partenza, nessun limite noto.
     */
    #[Computed]
    public function maxSeats(): ?int
    {
        if (!$this->departure) {
            return null;
        }
        return app(BookingService::class)->remainingCapacity($this->departure);
    }

    /**
     * True quando sono stati selezionati tutti i posti disponibili: blocca i "+".
     */
    #[Computed]
    public function capacityReached(): bool
    {
        $max = $this->maxSeats;
        return $max !== null && $this->totalSelected >= $max;
    }

    /**
     * Posti liberi sul singolo catamarano più capiente: se il gruppo selezionato
     * supera questo valore ma resta entro maxSeats, verrà diviso su più barche.
     */
    #[Computed]
    public function largestSingleFree(): ?int
    {
        if (!$this->departure) {
            return null;
        }
        return app(BookingService::class)->largestSingleCatamaranFree($this->departure);
    }

    /**
     * Disponibilità per singolo catamarano (nome, capienza, posti liberi), per
     * mostrare all'utente quanti posti ci sono su ogni barca.
     *
     * @return array<int, array{name: string, capacity: int, free: int}>
     */
    #[Computed]
    public function catamaranAvailability(): array
    {
        if (!$this->departure) {
            return [];
        }
        return app(BookingService::class)->catamaranAvailabilityList($this->departure);
    }

    /**
     * Vero quando il gruppo selezionato NON entra in un singolo catamarano ma
     * rientra nella capienza totale della partenza: verrà diviso su più barche.
     * Serve a mostrare il modale di avviso prima del checkout.
     */
    #[Computed]
    public function willSplit(): bool
    {
        if (!$this->departure) {
            return false;
        }
        $largest = $this->largestSingleFree;
        $max = $this->maxSeats;
        return $largest !== null
            && $this->totalSelected > $largest
            && ($max === null || $this->totalSelected <= $max);
    }

    /** Numero di catamarani su cui verrà diviso il gruppo (stima per il modale). */
    #[Computed]
    public function splitCatamaransCount(): int
    {
        if (!$this->departure) {
            return 1;
        }
        $assignment = app(BookingService::class)
            ->distributeSeats($this->departure->tour, $this->departure, $this->totalSelected);
        return is_array($assignment) ? count($assignment) : 1;
    }

    // Le liste stati/province servivano al luogo di rilascio del documento, che
    // non si chiede più: rimosse insieme ai campi.

    /** Tipi di documento accettati (value => label). */
    #[Computed]
    public function docTypes(): array
    {
        // Le chiavi restano quelle del modello (valore salvato a DB); solo le
        // etichette mostrate passano dai file di lingua.
        return collect(\App\Models\BookingSeat::DOC_TYPES)
            ->map(fn (string $label, string $key) => __('booking.doc_types.'.$key))
            ->all();
    }

    #[Computed]
    public function hasChildrenWithErrors(): bool
    {
        foreach ($this->resolvedChildren as $c) {
            if (!$c['ready']) {
                return true;
            }
        }
        return false;
    }

    // ===== Adults stepper =====

    public function incrementAdults(): void
    {
        // Non superare la capienza residua della partenza (somma di tutti i catamarani).
        $max = $this->maxSeats;
        if ($max !== null && $this->totalSelected >= $max) {
            return;
        }
        $this->adultsCount++;
        $this->syncAdults();
    }

    public function decrementAdults(): void
    {
        $this->adultsCount = max(1, $this->adultsCount - 1);
        $this->syncAdults();
    }

    /**
     * Allinea la lunghezza di $adults a $adultsCount, conservando i dati già
     * inseriti. Mantiene il primo adulto agganciato a customer_first/last_name
     * (è sempre l'intestatario).
     */
    protected function syncAdults(): void
    {
        $current = is_array($this->adults) ? $this->adults : [];
        $next = [];
        for ($i = 0; $i < $this->adultsCount; $i++) {
            $prev = is_array($current[$i] ?? null) ? $current[$i] : [];
            $next[] = [
                'first_name' => trim((string) ($prev['first_name'] ?? '')),
                'last_name' => trim((string) ($prev['last_name'] ?? '')),
                'doc_type' => (string) ($prev['doc_type'] ?? ''),
                'doc_number' => (string) ($prev['doc_number'] ?? ''),
            ];
        }
        $this->adults = $next;
    }

    // Quando il prenotante scrive il proprio nome, riportalo sul primo adulto.
    public function updatedCustomerFirstName(string $value): void
    {
        if (!isset($this->adults[0])) {
            $this->adults[0] = array_merge(['first_name' => '', 'last_name' => ''], self::emptyDocument());
        }
        $this->adults[0]['first_name'] = $value;
    }

    public function updatedCustomerLastName(string $value): void
    {
        if (!isset($this->adults[0])) {
            $this->adults[0] = array_merge(['first_name' => '', 'last_name' => ''], self::emptyDocument());
        }
        $this->adults[0]['last_name'] = $value;
    }

    // I vecchi hook updatedAdults()/updatedChildren() servivano solo a ripulire
    // provincia e comune al cambio dello Stato di emissione: con il luogo di
    // rilascio non più richiesto non c'è più nessuna cascata da mantenere.

    // ===== Children stepper =====

    public function addChild(): void
    {
        if ($this->childBrackets->isEmpty()) {
            return;
        }
        // Non superare la capienza residua della partenza.
        $max = $this->maxSeats;
        if ($max !== null && $this->totalSelected >= $max) {
            return;
        }
        $this->children[] = array_merge(['dob' => '', 'first_name' => '', 'last_name' => ''], self::emptyDocument());
    }

    public function removeChild(int $index = -1): void
    {
        if ($index < 0) {
            // remove last
            array_pop($this->children);
        } else {
            unset($this->children[$index]);
            $this->children = array_values($this->children);
        }
    }

    // ===== Addons =====

    public function toggleAddon(int $addonId): void
    {
        if (in_array($addonId, $this->selectedAddons, true)) {
            $this->selectedAddons = array_values(array_diff($this->selectedAddons, [$addonId]));
        } else {
            $this->selectedAddons[] = $addonId;
        }
    }

    // ===== Discount =====

    public function applyDiscount(): void
    {
        $this->discountFeedback = null;
        $this->discountValid = false;
        if (trim($this->discountCode) === '') {
            return;
        }
        $code = \App\Models\DiscountCode::where('code', strtoupper($this->discountCode))
            ->where('is_active', true)
            ->first();
        if (!$code || !$code->isValid()) {
            $this->discountFeedback = __('booking.discount.invalid');
            return;
        }
        $this->discountValid = true;
        $this->discountFeedback = __('booking.discount.applied');
    }

    public function removeDiscount(): void
    {
        $this->discountCode = '';
        $this->discountFeedback = null;
        $this->discountValid = false;
    }

    // ===== Submit =====

    /**
     * Click su "Prenota ora": se il gruppo verrà diviso su più catamarani,
     * mostra prima il modale di avviso; altrimenti procede col checkout.
     */
    public function requestSubmit(BookingService $bookingService)
    {
        if ($this->willSplit && ! $this->showSplitModal) {
            $this->showSplitModal = true;
            return null;
        }
        return $this->submit($bookingService);
    }

    public function closeSplitModal(): void
    {
        $this->showSplitModal = false;
    }

    /** Conferma dal modale: procede con la prenotazione (split accettato). */
    public function confirmSplit(BookingService $bookingService)
    {
        $this->showSplitModal = false;
        return $this->submit($bookingService);
    }

    public function submit(BookingService $bookingService)
    {
        $this->errorMessage = null;

        // Riallinea l'array adulti prima di validare (in caso il count sia cambiato senza che gli step abbiano sincronizzato).
        $this->syncAdults();

        $docTypes = implode(',', array_keys(\App\Models\BookingSeat::DOC_TYPES));

        $rules = [
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'special_requests' => 'nullable|string|max:1000',
            'adults' => 'array|min:1',
            'adults.*.first_name' => 'required|string|max:100',
            'adults.*.last_name' => 'required|string|max:100',
            'children.*.first_name' => 'required|string|max:100',
            'children.*.last_name' => 'required|string|max:100',
            'terms' => 'accepted',
        ];
        // Documento obbligatorio per OGNI passeggero (adulti + bambini): del
        // documento si chiedono solo tipo e numero.
        foreach (['adults', 'children'] as $group) {
            $rules[$group.'.*.doc_type'] = 'required|in:'.$docTypes;
            $rules[$group.'.*.doc_number'] = 'required|string|max:40';
        }

        $this->validate($rules, [
            'terms.accepted' => __('booking.validation.terms'),
            'adults.*.first_name.required' => __('booking.validation.adult_first_name'),
            'adults.*.last_name.required' => __('booking.validation.adult_last_name'),
            'children.*.first_name.required' => __('booking.validation.child_first_name'),
            'children.*.last_name.required' => __('booking.validation.child_last_name'),
            'adults.*.doc_type.required' => __('booking.validation.doc_type_required'),
            'adults.*.doc_type.in' => __('booking.validation.doc_type_invalid'),
            'adults.*.doc_number.required' => __('booking.validation.doc_number_required'),
            'children.*.doc_type.required' => __('booking.validation.doc_type_required'),
            'children.*.doc_type.in' => __('booking.validation.doc_type_invalid'),
            'children.*.doc_number.required' => __('booking.validation.doc_number_required'),
        ]);

        // Validazione password solo se l'ospite ha scelto di creare un account.
        // In modalità B2B l'agenzia non crea account per il cliente.
        $creatingAccount = ! $this->b2bMode && $this->wantsAccount && ! Auth::check();
        if ($creatingAccount) {
            if (User::where('email', $this->customer_email)->exists()) {
                $this->errorMessage = __('booking.errors.account_exists');
                return null;
            }
            $this->validate(
                ['accountPassword' => ['required', 'confirmed', Password::defaults()]],
                [
                    'accountPassword.required' => 'Inserisci una password per il tuo account.',
                    'accountPassword.confirmed' => 'Le password non corrispondono.',
                ]
            );
        }

        if (!$this->departure) {
            $this->errorMessage = __('booking.errors.no_date');
            return null;
        }

        // Orario limite di prenotazione (server-side, anti-manomissione): la
        // partenza non dev'essere passata né oltre la scadenza (orario del giorno
        // prima, per-tour o globale). L'admin non passa da qui (usa il flusso admin).
        $departAt = \Carbon\Carbon::parse($this->departure->departure_date->toDateString()
            .' '.\Carbon\Carbon::parse($this->departure->start_time)->format('H:i:s'));
        if ($departAt->isPast()) {
            $this->errorMessage = __('booking.errors.departure_unavailable');
            return null;
        }
        if (now()->gt($this->tour->bookingDeadlineFor($departAt))) {
            $this->errorMessage = __('booking.errors.bookings_closed', ['time' => $this->tour->effectiveCutoffTime()]);
            return null;
        }

        if ($this->adultsCount < 1) {
            $this->errorMessage = __('booking.errors.need_adult');
            return null;
        }

        if ($this->hasChildrenWithErrors) {
            $this->errorMessage = __('booking.errors.children_dob');
            return null;
        }

        // Bambini risolti: mantieni anche nome/cognome dalla nostra input.
        $resolvedChildren = [];
        foreach ($this->resolvedChildren as $i => $c) {
            if (!$c['ready']) {
                continue;
            }
            $resolvedChildren[] = [
                'dob' => $c['dob'],
                'bracket_id' => $c['bracket']->id,
                'first_name' => trim((string) ($this->children[$i]['first_name'] ?? '')),
                'last_name' => trim((string) ($this->children[$i]['last_name'] ?? '')),
                'document' => $this->documentFor($this->children[$i] ?? []),
            ];
        }

        // Costruisci la lista guests nell'ordine in cui i seat verranno creati:
        // 1) tutti gli adulti, 2) i bambini "counting" e "non counting" (lo
        // service li riordina internamente, ma a noi serve solo identificare
        // il primo adulto = intestatario).
        $guests = [];
        foreach ($this->adults as $i => $a) {
            $guests[] = array_merge([
                'first_name' => trim($a['first_name'] ?? ''),
                'last_name' => trim($a['last_name'] ?? ''),
                'tax_code' => $i === 0 && trim($this->customer_tax_code) !== ''
                    ? strtoupper(trim($this->customer_tax_code))
                    : null,
            ], $this->documentFor($a));
        }
        foreach ($resolvedChildren as $c) {
            $guests[] = array_merge([
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
            ], $c['document'] ?? []);
        }

        // Risolvi metodo/tipo di pagamento secondo le impostazioni attive.
        $useBankTransfer = \App\Support\Settings::bankTransferEnabled() && $this->paymentMethod === 'bank_transfer';
        // Server-side: se la partenza è troppo vicina l'acconto non è ammesso,
        // qualunque cosa arrivi dal client (radio manomesso o stato stantio).
        $useDeposit = $this->depositAvailable && $this->paymentChoice === 'deposit';
        $paymentType = $useBankTransfer ? 'bank_transfer' : ($useDeposit ? 'deposit' : 'full');

        $payload = [
            'tour_id' => $this->tour->id,
            'tour_departure_id' => $this->departure->id,
            'adults_count' => $this->adultsCount,
            'children' => $resolvedChildren,
            'addons' => $this->selectedAddons,
            'discount_code' => $this->discountValid ? $this->discountCode : null,
            'customer_first_name' => $this->customer_first_name,
            'customer_last_name' => $this->customer_last_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_tax_code' => trim($this->customer_tax_code) !== ''
                ? strtoupper(trim($this->customer_tax_code))
                : null,
            'special_requests' => $this->special_requests,
            'guests' => $guests,
            'payment_type' => $paymentType,
            'use_deposit' => $useDeposit,
        ];

        // Prenotazione omaggio dell'agenzia autorizzata: tutti i posti a 0€.
        // complimentarySeats() riverifica il permesso, quindi qui non serve
        // ricontrollarlo. A totale zero non c'è nulla da incassare: la
        // prenotazione nasce già CONFERMATA e salta acconto/bonifico/Stripe.
        $complimentarySeats = $this->complimentarySeats();
        if ($complimentarySeats > 0) {
            $payload['complimentary_seats'] = $complimentarySeats;
            $payload['complimentary_includes_addons'] = true;
            $payload['complimentary_reason'] = trim($this->complimentaryReason) !== ''
                ? trim($this->complimentaryReason)
                : 'Prenotazione omaggio registrata dall\'agenzia.';
            $payload['status'] = BookingStatus::CONFIRMED;
            $payload['payment_type'] = 'full';
            $payload['use_deposit'] = false;
        }

        // In modalità B2B chi è loggato è l'AGENZIA, non il cliente: la
        // prenotazione deve far capo al cliente finale. Passiamo user_id esplicito
        // = account cliente con quella email (se esiste), altrimenti null (ospite).
        // Senza questo, BookingService userebbe auth()->id() = l'agenzia.
        if ($this->b2bMode) {
            $payload['user_id'] = User::where('email', $this->customer_email)->value('id');
        }

        try {
            $booking = $bookingService->create($payload, $this->b2bMode ? 'b2b' : 'website');

            // Modalità B2B: attribuisci la prenotazione all'agenzia effettiva
            // (reale o impersonata) e calcola/snapshotta la commissione.
            if ($this->b2bMode) {
                app(\App\Services\CommissionService::class)
                    ->attributeToAgency($booking, \App\Support\B2bContext::actingAgency(), 'b2b_portal');

                // Omaggio: non c'è niente da incassare, quindi nessun invio di
                // estremi di pagamento (un'email "paga 0 €" sarebbe assurda per il
                // cliente). La prenotazione è già confermata.
                if ((float) $booking->total_amount <= 0) {
                    session()->flash('success', 'Prenotazione omaggio creata e confermata: nessun pagamento richiesto al cliente.');

                    return redirect()->route('b2b.bookings.show', $booking->uuid);
                }

                // Il cliente NON è presente (ha prenotato l'agenzia per lui): inviamo
                // subito al cliente gli estremi di pagamento (link Stripe o bonifico),
                // come farebbe il checkout sul sito. Best-effort: se fallisce, l'agenzia
                // può sempre usare "Reinvia estremi" dal dettaglio.
                $sent = false;
                try {
                    app(\App\Services\PaymentService::class)->sendPaymentInstructions($booking);
                    $sent = true;
                } catch (\Throwable $e) {
                    \App\Support\BookingLog::failure('b2b_payment_send', 'Invio automatico estremi pagamento al cliente fallito', $booking, $e, [
                        'to' => $booking->customer_email,
                    ]);
                }

                session()->flash($sent ? 'success' : 'warning', $sent
                    ? 'Prenotazione creata. Gli estremi di pagamento sono stati inviati a '.$booking->customer_email.'.'
                    : 'Prenotazione creata, ma l\'invio degli estremi al cliente non è riuscito: usa "Reinvia estremi" qui sotto.');

                // Resta nel portale b2b: il pagamento si gestisce dal dettaglio.
                return redirect()->route('b2b.bookings.show', $booking->uuid);
            }

            // Flusso B (referral): se il cliente è arrivato da un link agenzia
            // (?ref=TOKEN → cookie b2b_ref impostato dal middleware), attribuiamo
            // la vendita all'agenzia. Solo per QUESTA prenotazione: consumiamo il
            // cookie. La commissione si calcola sul totale finale (sconti inclusi).
            $refAgencyId = request()->cookie(\App\Http\Middleware\CaptureReferralMiddleware::COOKIE);
            if ($refAgencyId) {
                $agency = User::where('role', 'b2b')->find($refAgencyId);
                if ($agency) {
                    // Widget incorporato → 'b2b_widget'; link/QR referral → 'b2b_referral'.
                    app(\App\Services\CommissionService::class)
                        ->attributeToAgency($booking, $agency, $this->widgetMode ? 'b2b_widget' : 'b2b_referral');
                }
                \Illuminate\Support\Facades\Cookie::queue(
                    \Illuminate\Support\Facades\Cookie::forget(\App\Http\Middleware\CaptureReferralMiddleware::COOKIE)
                );
            }

            // Creazione account opzionale dell'ospite: dopo il booking, così
            // un fallimento nella prenotazione non lascia account orfani.
            if ($creatingAccount) {
                $user = User::create([
                    'name' => trim($this->customer_first_name . ' ' . $this->customer_last_name),
                    'email' => $this->customer_email,
                    'phone' => $this->customer_phone ?: null,
                    'tax_code' => $this->customer_tax_code ? strtoupper(trim($this->customer_tax_code)) : null,
                    'password' => Hash::make($this->accountPassword),
                    'role' => UserRole::CUSTOMER,
                    'locale' => app()->getLocale(),
                ]);

                // Collega questo booking (e altri con la stessa email) al nuovo account.
                \App\Models\Booking::where('customer_email', $user->email)
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->id]);

                event(new Registered($user));
                Auth::login($user);
            }

            // Bonifico: niente Stripe, mostra le istruzioni di pagamento.
            if ($useBankTransfer) {
                return redirect()->route('booking.bank-transfer', $booking->uuid);
            }

            return redirect()->route('payment.show', $booking->uuid);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            return null;
        }
    }

    public function render()
    {
        return view('livewire.public.booking-form');
    }
}
