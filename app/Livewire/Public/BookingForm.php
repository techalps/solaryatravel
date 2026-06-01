<?php

namespace App\Livewire\Public;

use App\Models\Addon;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
     * Nome/cognome di ogni adulto. La lunghezza dell'array è sincronizzata
     * con $adultsCount. Il primo adulto è il prenotante (intestatario).
     *
     * @var array<int, array{first_name: string, last_name: string}>
     */
    public array $adults = [
        ['first_name' => '', 'last_name' => ''],
    ];

    /**
     * Bambini: data di nascita + nome/cognome.
     *
     * @var array<int, array{dob: string, first_name: string, last_name: string}>
     */
    public array $children = [];

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
    public string $customer_tax_code = '';
    public string $special_requests = '';
    public bool $terms = false;

    public ?string $errorMessage = null;

    public function mount(Tour $tour, ?TourDeparture $departure = null, array $availableDates = []): void
    {
        $this->tour = $tour;
        $this->departure = $departure;
        $this->availableDates = $availableDates;

        // Se la partenza è già passata (es. /prenota?date=...), pre-popola data/orario per consistenza
        if ($departure) {
            $this->selectedDate = \Carbon\Carbon::parse($departure->departure_date)->toDateString();
            $this->selectedTime = \Carbon\Carbon::parse($departure->start_time)->format('H:i');
        }

        if (auth()->check()) {
            $u = auth()->user();
            $this->customer_first_name = $u->first_name ?? $u->name ?? '';
            $this->customer_last_name = $u->last_name ?? '';
            $this->customer_email = $u->email ?? '';
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
                $entry['error'] = 'Data non valida.';
                $out[] = $entry;
                continue;
            }

            if ($dobCarbon->gt($depDate)) {
                $entry['error'] = 'La data di nascita deve essere precedente alla data di partenza.';
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
            $this->discountValid ? $this->discountCode : null
        );
    }

    #[Computed]
    public function totalSelected(): int
    {
        return $this->adultsCount + count($this->children);
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
            $next[] = [
                'first_name' => trim((string) ($current[$i]['first_name'] ?? '')),
                'last_name' => trim((string) ($current[$i]['last_name'] ?? '')),
            ];
        }
        $this->adults = $next;
    }

    // Quando il prenotante scrive il proprio nome, riportalo sul primo adulto.
    public function updatedCustomerFirstName(string $value): void
    {
        if (!isset($this->adults[0])) {
            $this->adults[0] = ['first_name' => '', 'last_name' => ''];
        }
        $this->adults[0]['first_name'] = $value;
    }

    public function updatedCustomerLastName(string $value): void
    {
        if (!isset($this->adults[0])) {
            $this->adults[0] = ['first_name' => '', 'last_name' => ''];
        }
        $this->adults[0]['last_name'] = $value;
    }

    // ===== Children stepper =====

    public function addChild(): void
    {
        if ($this->childBrackets->isEmpty()) {
            return;
        }
        $this->children[] = ['dob' => '', 'first_name' => '', 'last_name' => ''];
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
            $this->discountFeedback = 'Codice non valido o scaduto.';
            return;
        }
        $this->discountValid = true;
        $this->discountFeedback = 'Codice applicato correttamente.';
    }

    public function removeDiscount(): void
    {
        $this->discountCode = '';
        $this->discountFeedback = null;
        $this->discountValid = false;
    }

    // ===== Submit =====

    public function submit(BookingService $bookingService)
    {
        $this->errorMessage = null;

        // Riallinea l'array adulti prima di validare (in caso il count sia cambiato senza che gli step abbiano sincronizzato).
        $this->syncAdults();

        $this->validate([
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_tax_code' => 'required|string|min:11|max:16',
            'special_requests' => 'nullable|string|max:1000',
            'adults' => 'array|min:1',
            'adults.*.first_name' => 'required|string|max:100',
            'adults.*.last_name' => 'required|string|max:100',
            'children.*.first_name' => 'required|string|max:100',
            'children.*.last_name' => 'required|string|max:100',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Devi accettare i termini e condizioni.',
            'customer_tax_code.required' => 'Inserisci il codice fiscale dell\'intestatario.',
            'customer_tax_code.min' => 'Codice fiscale non valido.',
            'adults.*.first_name.required' => 'Inserisci il nome di ogni adulto.',
            'adults.*.last_name.required' => 'Inserisci il cognome di ogni adulto.',
            'children.*.first_name.required' => 'Inserisci il nome di ogni bambino.',
            'children.*.last_name.required' => 'Inserisci il cognome di ogni bambino.',
        ]);

        if (!$this->departure) {
            $this->errorMessage = 'Seleziona una data di partenza.';
            return null;
        }

        if ($this->adultsCount < 1) {
            $this->errorMessage = 'Serve almeno un adulto.';
            return null;
        }

        if ($this->hasChildrenWithErrors) {
            $this->errorMessage = 'Controlla le date di nascita dei bambini: ognuna deve corrispondere a una riduzione disponibile.';
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
            ];
        }

        // Costruisci la lista guests nell'ordine in cui i seat verranno creati:
        // 1) tutti gli adulti, 2) i bambini "counting" e "non counting" (lo
        // service li riordina internamente, ma a noi serve solo identificare
        // il primo adulto = intestatario).
        $guests = [];
        foreach ($this->adults as $i => $a) {
            $guests[] = [
                'first_name' => trim($a['first_name'] ?? ''),
                'last_name' => trim($a['last_name'] ?? ''),
                'tax_code' => $i === 0 ? strtoupper(trim($this->customer_tax_code)) : null,
            ];
        }
        foreach ($resolvedChildren as $c) {
            $guests[] = [
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
            ];
        }

        try {
            $booking = $bookingService->create([
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
                'customer_tax_code' => strtoupper(trim($this->customer_tax_code)),
                'special_requests' => $this->special_requests,
                'guests' => $guests,
            ], 'website');

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
