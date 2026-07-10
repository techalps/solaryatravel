<div>
    <div class="tg-tour-about-sidebar booking-widget mb-30">
        <h4 class="tg-tour-about-title title-2 mb-15">Prenota questo tour</h4>

        @php
            $availableDateKeys = array_keys($availableDates ?? []);
            $hasPicker = !empty($availableDateKeys);
            $timesForSelected = $selectedDate ? ($availableDates[$selectedDate] ?? []) : [];
            $needsTimePicker = $selectedDate && count($timesForSelected) > 1;
        @endphp

        {{-- Date picker --}}
        @if($hasPicker)
            <span class="tg-tour-about-sidebar-title d-inline-block mb-5">Data</span>
            <div class="tg-booking-form-parent-inner mb-15" wire:ignore>
                <div class="tg-tour-about-date p-relative">
                    <input id="booking-date-input"
                           class="input"
                           type="text"
                           placeholder="Seleziona una data"
                           value="{{ $selectedDate }}"
                           data-available-dates='@json($availableDateKeys)'
                           data-current="{{ $selectedDate }}"
                           autocomplete="off"
                           readonly>
                    <span class="calender" style="color:var(--tg-theme-primary)">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.1111 1V3.80003M4.88888 1V3.80003M1 6.59992H15M2.55556 2.39988H13.4444C14.3036 2.39988 15 3.02668 15 3.79989V13.6C15 14.3732 14.3036 15 13.4444 15H2.55556C1.69645 15 1 14.3732 1 13.6V3.79989C1 3.02668 1.69645 2.39988 2.55556 2.39988Z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span class="angle"><i class="fa-sharp fa-solid fa-angle-down"></i></span>
                </div>
            </div>

            {{-- Time picker (solo se >1 orario disponibile per la data scelta) --}}
            @if($needsTimePicker)
                <div class="tg-tour-about-time d-flex align-items-center flex-wrap mb-15">
                    <span class="time me-2">Orario:</span>
                    @foreach($timesForSelected as $t)
                        <div class="form-check me-3 m-0">
                            <input class="form-check-input" type="radio" id="time-{{ $t }}" wire:click="pickTime('{{ $t }}')" @checked($selectedTime === $t)>
                            <label class="form-check-label" for="time-{{ $t }}">{{ $t }}</label>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Hint per quando manca data --}}
            @if(!$selectedDate)
                <div class="tg-tour-about-border-doted mb-15"></div>
                <p class="text-muted small text-center py-2 mb-0">
                    <i class="fa-regular fa-hand-pointer me-1"></i>
                    Clicca sul campo data per scegliere il giorno.
                </p>
            @endif
        @endif

        @if(!$hasPicker && !$departure)
            <div class="alert alert-warning small">Nessuna partenza disponibile al momento.</div>
        @endif

        {{-- Form principale (solo quando partenza risolta) --}}
        @if($departure)
            <div class="tg-tour-about-border-doted mb-15"></div>

            {{-- Partecipanti: Adulti + Bambini --}}
            <div class="tg-tour-about-tickets-wrap mb-15">
                <span class="tg-tour-about-sidebar-title d-inline-block mb-10">Partecipanti:</span>

                @php $maxSeats = $this->maxSeats; @endphp

                {{-- Disponibilità posti per questa partenza --}}
                @if($maxSeats !== null)
                    @if($maxSeats <= 0)
                        <div class="alert alert-danger small py-2 mb-10">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            Posti esauriti per questa data. Scrivici su
                            <a href="https://wa.me/393450884743" target="_blank" rel="noopener">WhatsApp</a> o via
                            <a href="mailto:info@solaryatravel.com">email</a> per le alternative.
                        </div>
                    @else
                        <div class="bk-seats-left small text-muted mb-10">
                            <i class="fa-regular fa-chair me-1"></i>
                            Posti disponibili per questa data: <strong>{{ $maxSeats }}</strong>
                        </div>

                        {{-- Dettaglio posti per ogni catamarano --}}
                        @php $cats = $this->catamaranAvailability; @endphp
                        @if(count($cats) > 1)
                            <div class="bk-cat-avail mb-10">
                                @foreach($cats as $c)
                                    <span class="bk-cat-chip {{ $c['free'] <= 0 ? 'is-full' : '' }}">
                                        <i class="fa-solid fa-sailboat me-1"></i>{{ $c['name'] }}
                                        <em>{{ $c['free'] }}/{{ $c['capacity'] }} posti</em>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @endif

                {{-- Avviso: gruppo distribuito su più catamarani (sopra i partecipanti) --}}
                @php $largestFree = $this->largestSingleFree; @endphp
                @if($largestFree !== null && $this->totalSelected > $largestFree && (!$this->maxSeats || $this->totalSelected <= $this->maxSeats))
                    <div class="alert alert-info small py-2 mb-10">
                        <i class="fa-solid fa-people-group me-1"></i>
                        Con <strong>{{ $this->totalSelected }} partecipanti</strong> il gruppo verrà diviso su più catamarani
                        (la barca più capiente ha {{ $largestFree }} posti liberi per questa partenza).
                    </div>
                @endif

                {{-- Adulti --}}
                <div class="tg-tour-about-tickets mb-15">
                    <div class="tg-tour-about-tickets-adult">
                        <span>Adulti</span>
                        <p class="mb-0">(da 18 anni) <span>€{{ number_format($this->adultUnitPrice, 0, ',', '.') }}</span></p>
                    </div>
                    <div class="bk-stepper">
                        <button type="button" wire:click="decrementAdults" @disabled($adultsCount <= 1) aria-label="Diminuisci">−</button>
                        <span class="qty">{{ $adultsCount }}</span>
                        <button type="button" class="plus" wire:click="incrementAdults" @disabled($this->capacityReached) aria-label="Aumenta">+</button>
                    </div>
                </div>

                {{-- Nomi degli adulti aggiuntivi (il primo coincide con l'intestatario, gestito sotto) --}}
                @if($adultsCount > 1)
                    <div class="bk-children-wrap mb-15">
                        @for ($i = 1; $i < $adultsCount; $i++)
                            <div class="bk-child-row" wire:key="adult-{{ $i }}">
                                <div class="bk-child-row-head">
                                    <span class="bk-child-num">Adulto {{ $i + 1 }}</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text" wire:model.blur="adults.{{ $i }}.first_name" class="bk-input" placeholder="Nome *">
                                        @error('adults.'.$i.'.first_name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="text" wire:model.blur="adults.{{ $i }}.last_name" class="bk-input" placeholder="Cognome *">
                                        @error('adults.'.$i.'.last_name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                                @include('livewire.public._document-fields', ['group' => 'adults', 'idx' => $i])
                            </div>
                        @endfor
                    </div>
                @endif

                @if($this->childBrackets->isNotEmpty())
                    {{-- Bambini --}}
                    <div class="tg-tour-about-tickets mb-10">
                        <div class="tg-tour-about-tickets-adult">
                            <span>Bambini</span>
                            <p class="mb-0"><span class="text-muted small fw-normal">prezzo in base all'età</span></p>
                        </div>
                        <div class="bk-stepper">
                            <button type="button" wire:click="removeChild" @disabled(count($children) <= 0) aria-label="Diminuisci">−</button>
                            <span class="qty">{{ count($children) }}</span>
                            <button type="button" class="plus" wire:click="addChild" @disabled($this->capacityReached) aria-label="Aumenta">+</button>
                        </div>
                    </div>

                    <div class="bk-reductions-info small text-muted mb-10">
                        <i class="fa-regular fa-circle-info me-1"></i>
                        Riduzioni:
                        @foreach($this->childBrackets as $b)
                            <span class="bk-reduction-chip">
                                {{ $b->label }}
                                <em class="text-nowrap">
                                    @if($b->max_age !== null && $b->min_age)
                                        ({{ $b->min_age }}–{{ $b->max_age }})
                                    @elseif($b->max_age !== null)
                                        (≤ {{ $b->max_age }})
                                    @else
                                        (≥ {{ $b->min_age }})
                                    @endif
                                </em>
                                · €{{ number_format((float) $b->price * (float) $departure->price_modifier, 0, ',', '.') }}
                            </span>
                        @endforeach
                    </div>

                    @if(count($children) > 0)
                        <div class="bk-children-wrap mb-15">
                            @foreach($this->resolvedChildren as $c)
                                @php $idx = $c['index']; @endphp
                                <div class="bk-child-row" wire:key="child-{{ $idx }}">
                                    <div class="bk-child-row-head">
                                        <span class="bk-child-num">Bambino {{ $idx + 1 }}</span>
                                        <button type="button" wire:click="removeChild({{ $idx }})" class="bk-child-remove" title="Rimuovi"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <input type="text" wire:model.blur="children.{{ $idx }}.first_name" class="bk-input" placeholder="Nome *">
                                            @error('children.'.$idx.'.first_name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                        </div>
                                        <div class="col-6">
                                            <input type="text" wire:model.blur="children.{{ $idx }}.last_name" class="bk-input" placeholder="Cognome *">
                                            @error('children.'.$idx.'.last_name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                        </div>
                                    </div>
                                    <label class="bk-label">Data di nascita <span class="text-danger">*</span></label>
                                    <input type="date" wire:model.live="children.{{ $idx }}.dob" class="bk-input" max="{{ now()->toDateString() }}">
                                    @if($c['error'])
                                        <small class="text-danger d-block mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $c['error'] }}</small>
                                    @elseif($c['ready'])
                                        <div class="bk-child-resolved">
                                            <i class="fa-solid fa-check-circle me-1"></i>
                                            <strong>{{ $c['bracket']->label }}</strong> ({{ $c['age'] }} anni)
                                            <span class="text-primary fw-bold">€{{ number_format($c['unit_price'], 0, ',', '.') }}</span>
                                        </div>
                                    @elseif($c['dob'] === '')
                                        <small class="text-muted d-block mt-1"><i class="fa-regular fa-circle-info me-1"></i>Inserisci la data di nascita per vedere la riduzione.</small>
                                    @endif
                                    @include('livewire.public._document-fields', ['group' => 'children', 'idx' => $idx])
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- Extras --}}
            @if($this->addons->isNotEmpty())
                <div class="tg-tour-about-border-doted mb-15"></div>
                <div class="tg-tour-about-extra mb-15">
                    <span class="tg-tour-about-sidebar-title mb-10 d-inline-block">Aggiungi extra:</span>
                    <div class="tg-filter-list">
                        <ul class="list-unstyled mb-0">
                            @foreach($this->addons as $addon)
                                @php $isActive = in_array($addon->id, $selectedAddons, true); @endphp
                                <li wire:key="addon-{{ $addon->id }}">
                                    <div class="checkbox d-flex align-items-center">
                                        <input class="tg-checkbox" type="checkbox" id="addon-{{ $addon->id }}" wire:click="toggleAddon({{ $addon->id }})" @checked($isActive)>
                                        <label for="addon-{{ $addon->id }}" class="tg-label ms-2 mb-0">{{ $addon->name }}</label>
                                    </div>
                                    <span class="quantity">€{{ number_format((float) $addon->price, 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Riepilogo prezzi --}}
            @if($this->totalSelected > 0)
                <div class="tg-tour-about-border-doted mb-15"></div>
                <div class="bk-summary-mini mb-15">
                    @foreach($this->pricing['brackets'] ?? [] as $line)
                        <div class="bk-summary-line">
                            <span>{{ $line['label'] }} × {{ $line['count'] }}</span>
                            <span>€{{ number_format($line['line_total'], 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                    @if(($this->pricing['addons_total'] ?? 0) > 0)
                        <div class="bk-summary-line">
                            <span>Extra</span>
                            <span>€{{ number_format($this->pricing['addons_total'], 2, ',', '.') }}</span>
                        </div>
                    @endif
                    @if(($this->pricing['discount_amount'] ?? 0) > 0)
                        <div class="bk-summary-line discount">
                            <span><i class="fa-solid fa-tag me-1"></i>Sconto</span>
                            <span>− €{{ number_format($this->pricing['discount_amount'], 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Discount --}}
            <div class="mb-15">
                <label class="bk-label">Codice sconto</label>
                <div class="d-flex gap-2">
                    <input type="text" wire:model="discountCode" class="bk-input flex-grow-1" placeholder="ES. ESTATE10" @disabled($discountValid)>
                    @if($discountValid)
                        <button type="button" wire:click="removeDiscount" class="btn btn-light border rounded-pill px-3" title="Rimuovi"><i class="fa-solid fa-xmark"></i></button>
                    @else
                        <button type="button" wire:click="applyDiscount" class="btn btn-outline-primary rounded-pill px-3">Applica</button>
                    @endif
                </div>
                @if($discountFeedback)
                    <small class="d-block mt-1 {{ $discountValid ? 'text-success' : 'text-danger' }}">{{ $discountFeedback }}</small>
                @endif
            </div>

            <div class="tg-tour-about-border-doted mb-15"></div>

            {{-- Customer fields --}}
            <span class="tg-tour-about-sidebar-title d-inline-block mb-10"><i class="fa-regular fa-user text-primary me-1"></i>Dati intestatario</span>
            <div class="row g-2 mb-10">
                <div class="col-6">
                    <input type="text" wire:model.live.debounce.300ms="customer_first_name" class="bk-input" placeholder="Nome *">
                    @error('customer_first_name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-6">
                    <input type="text" wire:model.live.debounce.300ms="customer_last_name" class="bk-input" placeholder="Cognome *">
                    @error('customer_last_name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-12">
                    <input type="text" wire:model.blur="customer_tax_code" class="bk-input text-uppercase" placeholder="Codice fiscale *" maxlength="16">
                    @error('customer_tax_code') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-12">
                    <input type="email" wire:model="customer_email" class="bk-input" placeholder="Email *">
                    @error('customer_email') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-12">
                    <input type="tel" wire:model="customer_phone" class="bk-input" placeholder="Telefono">
                    @error('customer_phone') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-12">
                    <textarea wire:model="special_requests" class="bk-textarea" rows="2" placeholder="Richieste speciali (opzionale)"></textarea>
                </div>
                {{-- Documento dell'intestatario (adulto #1). Obbligatorio come per tutti. --}}
                <div class="col-12">
                    @include('livewire.public._document-fields', ['group' => 'adults', 'idx' => 0])
                </div>
            </div>

            {{-- Crea account (solo ospiti non loggati) --}}
            @guest
                <div class="bk-account-box mb-15">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="wantsAccount" id="bk-wants-account">
                        <label class="form-check-label small fw-semibold" for="bk-wants-account">
                            <i class="fa-solid fa-user-plus text-primary me-1"></i>Crea un account per gestire le tue prenotazioni
                        </label>
                    </div>
                    @if($wantsAccount)
                        <div class="row g-2 mt-2">
                            <div class="col-12">
                                <small class="text-muted d-block mb-2">L'account sarà creato con l'email <strong>{{ $customer_email ?: 'indicata sopra' }}</strong>.</small>
                            </div>
                            <div class="col-sm-6">
                                <input type="password" wire:model="accountPassword" class="bk-input" placeholder="Password *" autocomplete="new-password">
                                @error('accountPassword') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-sm-6">
                                <input type="password" wire:model="accountPassword_confirmation" class="bk-input" placeholder="Conferma password *" autocomplete="new-password">
                            </div>
                        </div>
                    @endif
                </div>
            @endguest

            {{-- Total --}}
            <div class="tg-tour-about-coast d-flex align-items-center flex-wrap justify-content-between mb-5">
                <span class="tg-tour-about-sidebar-title d-inline-block">Costo totale:</span>
                <h5 class="total-price mb-0">€{{ number_format($this->pricing['total_amount'] ?? 0, 2, ',', '.') }}</h5>
            </div>
            <div class="text-end text-muted small mb-15" style="font-size:.78rem">IVA inclusa</div>

            @php
                $depositOn = \App\Support\Settings::depositEnabled();
                $bankOn = \App\Support\Settings::bankTransferEnabled();
                $total = (float) ($this->pricing['total_amount'] ?? 0);
                $depAmount = round($total * \App\Support\Settings::depositPercentage() / 100, 2);
            @endphp

            {{-- Scelta acconto / intero --}}
            @if($depositOn)
                <div class="bk-pay-box mb-15">
                    <div class="small fw-semibold mb-2"><i class="fa-solid fa-wallet text-primary me-1"></i>Importo da pagare ora</div>
                    <label class="bk-pay-opt">
                        <input type="radio" wire:model="paymentChoice" value="full">
                        <span>Intero · <strong>€{{ number_format($total, 2, ',', '.') }}</strong></span>
                    </label>
                    <label class="bk-pay-opt">
                        <input type="radio" wire:model="paymentChoice" value="deposit">
                        <span>Acconto {{ \App\Support\Settings::depositPercentage() }}% · <strong>€{{ number_format($depAmount, 2, ',', '.') }}</strong>
                            <span class="text-muted d-block" style="font-size:.76rem">Saldo entro {{ \App\Support\Settings::balanceDueHours() }}h dalla partenza</span></span>
                    </label>
                </div>
            @endif

            {{-- Scelta metodo: carta / bonifico istantaneo --}}
            @if($bankOn)
                @php $bankExpiryHours = \App\Support\Settings::bankTransferExpiryHours(); @endphp
                <div class="bk-pay-box mb-15">
                    <div class="small fw-semibold mb-2"><i class="fa-solid fa-credit-card text-primary me-1"></i>Metodo di pagamento</div>
                    <label class="bk-pay-opt">
                        <input type="radio" wire:model="paymentMethod" value="card">
                        <span>Carta di credito <span class="text-muted">(immediato)</span></span>
                    </label>
                    <label class="bk-pay-opt">
                        <input type="radio" wire:model="paymentMethod" value="bank_transfer">
                        <span>Bonifico istantaneo
                            <span class="text-muted d-block" style="font-size:.76rem">Posti riservati per {{ $bankExpiryHours }}h; confermiamo manualmente alla ricezione del bonifico.</span></span>
                    </label>
                </div>
            @endif

            {{-- Avviso minimo partecipanti --}}
            <div class="bk-min-notice mb-15">
                <i class="fa-solid fa-circle-info me-1"></i>
                {{ \App\Support\Settings::minParticipantsNotice() }}
            </div>

            {{-- Terms --}}
            <div class="form-check mb-15">
                <input class="form-check-input" type="checkbox" wire:model="terms" id="bk-terms">
                <label class="form-check-label small" for="bk-terms">
                    Accetto i <a href="{{ route('terms') }}" target="_blank">termini</a> e la <a href="{{ route('privacy') }}" target="_blank">privacy policy</a>.
                </label>
                @error('terms') <small class="d-block text-danger">{{ $message }}</small> @enderror
            </div>

            @if($errorMessage)
                <div class="alert alert-danger small mb-15">{{ $errorMessage }}</div>
            @endif

            <button type="button" class="tg-btn tg-btn-switch-animation w-100" wire:click="requestSubmit" wire:loading.attr="disabled" wire:target="requestSubmit,submit,confirmSplit" @disabled($this->hasChildrenWithErrors || $adultsCount < 1 || ($this->maxSeats !== null && ($this->maxSeats <= 0 || $this->totalSelected > $this->maxSeats)))>
                <span wire:loading.remove wire:target="requestSubmit,submit,confirmSplit">
                    <i class="fa-solid fa-lock me-2"></i>Prenota ora
                </span>
                <span wire:loading wire:target="requestSubmit,submit,confirmSplit">
                    <i class="fa-solid fa-spinner fa-spin me-2"></i>Attendere…
                </span>
            </button>

            {{-- Modale: il gruppo verrà diviso su più catamarani --}}
            @if($showSplitModal)
                <div class="bk-modal-overlay" wire:key="split-modal">
                    <div class="bk-modal" role="dialog" aria-modal="true">
                        <div class="bk-modal-head">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <h5>Gruppo su più catamarani</h5>
                        </div>
                        <div class="bk-modal-body">
                            <p>
                                Per questa data non c'è un'unica imbarcazione con
                                <strong>{{ $this->totalSelected }} posti</strong> liberi: il tuo gruppo verrà
                                diviso su <strong>{{ $this->splitCatamaransCount }} catamarani</strong> per la stessa partenza.
                            </p>
                            <p class="text-muted small mb-0">
                                Puoi proseguire così, scegliere un'altra data, oppure contattare il nostro
                                customer care su WhatsApp per organizzare il gruppo su un'unica barca.
                            </p>
                        </div>
                        <div class="bk-modal-actions">
                            <button type="button" class="tg-btn w-100" wire:click="confirmSplit" wire:loading.attr="disabled" wire:target="confirmSplit">
                                <i class="fa-solid fa-check me-2"></i>Prosegui comunque
                            </button>
                            <button type="button" class="bk-modal-btn-outline w-100" wire:click="closeSplitModal">
                                <i class="fa-solid fa-calendar-day me-2"></i>Cambia data
                            </button>
                            <a href="https://wa.me/393450884743" target="_blank" rel="noopener" class="bk-modal-btn-wa w-100">
                                <i class="fa-brands fa-whatsapp me-2"></i>Contatta su WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <small class="d-block text-muted text-center mt-2" style="font-size:.78rem">
                <i class="fa-solid fa-shield-alt me-1"></i>Pagamento sicuro via Stripe
            </small>
        @endif
    </div>

    {{-- Styles --}}
    <style>
        .booking-widget .bk-input { border-radius: 50px; padding: .55rem 1rem; border: 1.5px solid #eef0f3; width: 100%; background: #fff; font-size: .88rem; }
        .booking-widget .bk-input:focus { outline: none; border-color: var(--tg-theme-secondary); box-shadow: 0 0 0 3px rgba(var(--tg-theme-secondary-rgb, 124,55,255),.12); }
        .booking-widget .bk-textarea { border-radius: 14px; padding: .65rem 1rem; border: 1.5px solid #eef0f3; width: 100%; font-size: .88rem; resize: vertical; }
        .booking-widget .bk-textarea:focus { outline: none; border-color: var(--tg-theme-secondary); box-shadow: 0 0 0 3px rgba(var(--tg-theme-secondary-rgb, 124,55,255),.12); }
        .booking-widget .bk-account-box { background: #f8fafc; border: 1.5px solid #eef0f3; border-radius: 14px; padding: .75rem 1rem; }
        .booking-widget .bk-pay-box { background: #f8fafc; border: 1.5px solid #eef0f3; border-radius: 14px; padding: .75rem 1rem; }
        .booking-widget .bk-pay-opt { display: flex; align-items: flex-start; gap: .55rem; padding: .4rem 0; font-size: .88rem; cursor: pointer; }
        .booking-widget .bk-pay-opt input { margin-top: .2rem; }
        .booking-widget .bk-label { font-size: .78rem; font-weight: 600; color: #0E1B33; margin-bottom: .35rem; display: block; }
        .booking-widget .bk-min-notice { background: #fff8e6; border: 1px solid #ffe39a; border-radius: 12px; padding: .6rem .8rem; font-size: .8rem; color: #6b4e00; line-height: 1.45; }

        .booking-widget .bk-stepper { display: inline-flex; align-items: center; background: #f4f4f4; border-radius: 50px; padding: 3px; gap: 4px; }
        .booking-widget .bk-stepper button { width: 26px; height: 26px; border-radius: 50%; border: 0; background: #fff; color: #0E1B33; font-weight: 700; font-size: 1rem; line-height: 1; display: inline-flex; align-items: center; justify-content: center; }
        .booking-widget .bk-stepper button:disabled { opacity: .3; cursor: not-allowed; }
        .booking-widget .bk-stepper button.plus { background: var(--tg-theme-secondary); color: #fff; }
        .booking-widget .bk-stepper .qty { min-width: 22px; text-align: center; font-weight: 700; font-size: .9rem; color: #0E1B33; }

        /* Modale split catamarani */
        .bk-modal-overlay { position: fixed; inset: 0; background: rgba(14,27,51,.55); display: flex; align-items: center; justify-content: center; z-index: 1080; padding: 1rem; }
        .bk-modal { background: #fff; border-radius: 18px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden; }
        .bk-modal-head { display: flex; align-items: center; gap: .6rem; padding: 1rem 1.25rem; background: #fff8e6; color: #6b4e00; }
        .bk-modal-head i { font-size: 1.3rem; }
        .bk-modal-head h5 { margin: 0; font-size: 1.05rem; font-weight: 700; }
        .bk-modal-body { padding: 1rem 1.25rem; font-size: .9rem; color: #0E1B33; line-height: 1.5; }
        .bk-modal-body p { margin-bottom: .6rem; }
        .bk-modal-actions { padding: 0 1.25rem 1.25rem; display: flex; flex-direction: column; gap: .55rem; }
        .bk-modal-btn-outline { display: inline-flex; align-items: center; justify-content: center; padding: .6rem 1rem; border-radius: 50px; border: 1.5px solid #e2e8f0; background: #fff; color: #0E1B33; font-weight: 600; font-size: .9rem; text-decoration: none; }
        .bk-modal-btn-outline:hover { background: #f4f4f4; }
        .bk-modal-btn-wa { display: inline-flex; align-items: center; justify-content: center; padding: .6rem 1rem; border-radius: 50px; border: 0; background: #25D366; color: #fff; font-weight: 600; font-size: .9rem; text-decoration: none; }
        .bk-modal-btn-wa:hover { background: #1ebe57; color: #fff; }

        .booking-widget .bk-summary-mini { background: #fafafa; border-radius: 10px; padding: .65rem .85rem; }
        .booking-widget .bk-summary-line { display: flex; justify-content: space-between; padding: .2rem 0; font-size: .85rem; color: #0E1B33; }
        .booking-widget .bk-summary-line.discount { color: #198754; }

        .booking-widget .tg-tour-about-coast .total-price { font-size: 22px; }
        .booking-widget .tg-btn { background: var(--tg-theme-secondary); color: #fff; padding: 14px 22px; font-weight: 600; }
        .booking-widget .tg-btn:hover { background: var(--tg-theme-secondary); filter: brightness(.85); color: #fff; }
        .booking-widget .tg-btn:disabled { opacity: .5; cursor: not-allowed; }
        .booking-widget .tg-tour-about-extra .tg-filter-list ul li { padding: .35rem 0; }

        /* Disponibilità per catamarano */
        .booking-widget .bk-cat-avail { display: flex; flex-wrap: wrap; gap: .35rem; }
        .booking-widget .bk-cat-chip {
            display: inline-flex; align-items: center; gap: .15rem;
            background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0;
            border-radius: 50px; padding: 3px 11px; font-size: .74rem; font-weight: 600;
        }
        .booking-widget .bk-cat-chip em { font-style: normal; font-weight: 500; opacity: .75; }
        .booking-widget .bk-cat-chip.is-full { background: #fef2f2; color: #b91c1c; border-color: #fecaca; opacity: .8; }

        .booking-widget .bk-reductions-info { line-height: 1.7; }
        .booking-widget .bk-reduction-chip {
            display: inline-block; background: color-mix(in srgb, var(--tg-theme-secondary) 10%, #fff); color: var(--tg-theme-secondary); border-radius: 50px;
            padding: 2px 10px; font-size: .72rem; font-weight: 600;
            margin-right: .25rem; margin-bottom: .2rem;
        }
        .booking-widget .bk-reduction-chip em { font-style: normal; opacity: .7; font-weight: 500; }

        .booking-widget .bk-children-wrap { display: flex; flex-direction: column; gap: .65rem; }
        .booking-widget .bk-child-row {
            border: 1.5px solid #eef0f3; border-radius: 14px; padding: .65rem .75rem; background: #fafafa;
        }
        .booking-widget .bk-child-row-head {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: .35rem;
        }
        .booking-widget .bk-child-num { font-weight: 700; font-size: .82rem; color: #0E1B33; }
        .booking-widget .bk-child-remove {
            background: transparent; border: 0; color: #6c757d; padding: 0 .25rem;
            cursor: pointer; transition: color .15s; font-size: .85rem;
        }
        .booking-widget .bk-child-remove:hover { color: #dc3545; }
        .booking-widget .bk-child-resolved {
            margin-top: .5rem; padding: .4rem .6rem; background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 10px; font-size: .8rem; color: #14532d;
            display: flex; align-items: center; gap: .35rem; flex-wrap: wrap;
        }
        .booking-widget .bk-child-resolved strong { color: #0E1B33; }

        /* Time radios as pill chips */
        .booking-widget .tg-tour-about-time .form-check {
            display: inline-flex; align-items: center; gap: .35rem;
        }
        .booking-widget .tg-tour-about-time .form-check-input {
            margin: 0; transform: none;
        }

        /* Flatpickr theme override (purple) */
        .flatpickr-calendar.bk-fp-theme .flatpickr-day.selected,
        .flatpickr-calendar.bk-fp-theme .flatpickr-day.selected:hover { background: var(--tg-theme-secondary); border-color: var(--tg-theme-secondary); }
        .flatpickr-calendar.bk-fp-theme .flatpickr-day.today:not(.selected) { border-color: var(--tg-theme-secondary); color: var(--tg-theme-secondary); }
        .flatpickr-calendar.bk-fp-theme .flatpickr-day.flatpickr-disabled { text-decoration: line-through; opacity: .35; }
    </style>

    {{-- Flatpickr init for date input --}}
    @if(!empty($availableDates))
        <script>
            (function () {
                function initBookingDatePicker() {
                    const el = document.getElementById('booking-date-input');
                    if (!el || typeof flatpickr === 'undefined') return;
                    if (el._fp) return; // già inizializzato
                    const dates = JSON.parse(el.dataset.availableDates || '[]');
                    if (dates.length === 0) return;
                    el._fp = flatpickr(el, {
                        enable: dates,
                        minDate: 'today',
                        maxDate: dates[dates.length - 1],
                        dateFormat: 'Y-m-d',
                        disableMobile: true,
                        defaultDate: el.dataset.current || null,
                        locale: {
                            firstDayOfWeek: 1,
                            weekdays: { shorthand: ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'], longhand: ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'] },
                            months: { shorthand: ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'], longhand: ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'] },
                        },
                        onChange: function (selectedDates, dateStr) {
                            if (window.Livewire && dateStr) {
                                @this.call('pickDate', dateStr);
                            }
                        },
                    });
                    el._fp.calendarContainer && el._fp.calendarContainer.classList.add('bk-fp-theme');
                }
                if (document.readyState !== 'loading') {
                    initBookingDatePicker();
                } else {
                    document.addEventListener('DOMContentLoaded', initBookingDatePicker);
                }
                document.addEventListener('livewire:navigated', initBookingDatePicker);
                document.addEventListener('livewire:update', initBookingDatePicker);
            })();
        </script>
    @endif
</div>
