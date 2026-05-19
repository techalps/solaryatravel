{{--
    Form di ricerca tour condiviso fra home e listing.
    Variabili attese (passate dai controller):
      - $searchTours      : Collection<Tour>  (id, name)
      - $tourSearch       : array { tour: ?int, date: ?string, adults: int, children: int }
      - $minBookingDate   : string Y-m-d (opzionale)
--}}
@php
    $tourSearch = $tourSearch ?? ['tour' => null, 'date' => null, 'adults' => 2, 'children' => 0];
    $searchTours = $searchTours ?? collect();
    $minBookingDate = $minBookingDate ?? now()->format('Y-m-d');
@endphp

<div class="tg-booking-form-area">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="tg-booking-form-wrap">
                    <div class="tg-booking-form-item">
                        <form action="{{ route('tours.index') }}" method="GET">
                            <div class="tg-booking-form-input-group d-flex align-items-end justify-content-between flex-wrap">

                                {{-- Tour --}}
                                <div class="tg-booking-form-parent-inner mr-15 mb-10">
                                    <span class="tg-booking-form-title mb-5">Tour:</span>
                                    <div class="tg-booking-add-input-field">
                                        <select name="tour" class="bf-native-select">
                                            <option value="">Tutti i tour</option>
                                            @foreach($searchTours as $st)
                                                <option value="{{ $st->id }}" {{ (int)($tourSearch['tour'] ?? 0) === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="location">
                                            <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.3329 6.7071C12.3329 11.2324 6.55512 15.1111 6.55512 15.1111C6.55512 15.1111 0.777344 11.2324 0.777344 6.7071C0.777344 5.16402 1.38607 3.68414 2.46962 2.59302C3.55316 1.5019 5.02276 0.888916 6.55512 0.888916C8.08748 0.888916 9.55708 1.5019 10.6406 2.59302C11.7242 3.68414 12.3329 5.16402 12.3329 6.7071Z" stroke="currentColor" stroke-width="1.15556" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M6.55512 8.64649C7.61878 8.64649 8.48105 7.7782 8.48105 6.7071C8.48105 5.636 7.61878 4.7677 6.55512 4.7677C5.49146 4.7677 4.6292 5.636 4.6292 6.7071C4.6292 7.7782 5.49146 8.64649 6.55512 8.64649Z" stroke="currentColor" stroke-width="1.15556" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>

                                {{-- Data partenza --}}
                                <div class="tg-booking-form-parent-inner mr-15 mb-10">
                                    <span class="tg-booking-form-title mb-5">Data partenza:</span>
                                    <div class="tg-booking-add-input-date p-relative">
                                        <span>
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.76501 0.777771V3.26668M4.23413 0.777771V3.26668M0.777344 5.75548H13.2218M2.16006 2.02211H11.8391C12.6027 2.02211 13.2218 2.57927 13.2218 3.26656V11.9778C13.2218 12.6651 12.6027 13.2222 11.8391 13.2222H2.16006C1.39641 13.2222 0.777344 12.6651 0.777344 11.9778V3.26656C0.777344 2.57927 1.39641 2.02211 2.16006 2.02211Z" stroke="currentColor" stroke-width="0.977778" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <input type="text" name="date" value="{{ $tourSearch['date'] ?? '' }}" class="input bf-flatpickr" placeholder="gg/mm/aaaa" data-min="{{ $minBookingDate }}" autocomplete="off">
                                    </div>
                                </div>

                                {{-- Ospiti (dropdown con +/-) --}}
                                <div class="tg-booking-form-parent-inner tg-hero-quantity p-relative mr-15 mb-10" id="bfGuestRoot">
                                    <span class="tg-booking-form-title mb-5">Ospiti:</span>
                                    <div class="tg-booking-add-input-field tg-booking-quantity-toggle" id="bfGuestToggle">
                                        <span class="location">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 8a3 3 0 100-6 3 3 0 000 6zM2 14c0-2.5 2.7-4 6-4s6 1.5 6 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                            </svg>
                                        </span>
                                        <span class="tg-booking-title-value" id="bfGuestLabel">+ Aggiungi ospiti</span>
                                    </div>
                                    <input type="hidden" name="adults" id="bfAdults" value="{{ (int)($tourSearch['adults'] ?? 2) }}">
                                    <input type="hidden" name="children" id="bfChildren" value="{{ (int)($tourSearch['children'] ?? 0) }}">
                                    <div class="tg-booking-form-location-list tg-quantity tg-booking-quantity-active" id="bfGuestPanel">
                                        <ul>
                                            <li>
                                                <span class="mr-20">Adulti</span>
                                                <div class="tg-booking-quantity-item">
                                                    <span class="decrement" data-target="bfAdults" data-min="1">
                                                        <svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1 1H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </span>
                                                    <input class="tg-quantity-input" type="text" value="{{ (int)($tourSearch['adults'] ?? 2) }}" data-quantity-display="bfAdults" readonly>
                                                    <span class="increment" data-target="bfAdults" data-max="20">
                                                        <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1.21924 7H13.3836" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M7.30176 13V1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                            </li>
                                            <li>
                                                <span class="mr-20">Bambini</span>
                                                <div class="tg-booking-quantity-item">
                                                    <span class="decrement" data-target="bfChildren" data-min="0">
                                                        <svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1 1H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </span>
                                                    <input class="tg-quantity-input" type="text" value="{{ (int)($tourSearch['children'] ?? 0) }}" data-quantity-display="bfChildren" readonly>
                                                    <span class="increment" data-target="bfChildren" data-max="20">
                                                        <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1.21924 7H13.3836" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M7.30176 13V1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                            </li>
                                        </ul>
                                        <div class="tg-booking-form-search-btn mt-15">
                                            <button type="button" class="bk-search-button bk-search-button-2 w-100" id="bfGuestOk">Ok</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bottone --}}
                                <div class="tg-booking-form-search-btn mb-10">
                                    <button class="bk-search-button" type="submit">Cerca
                                        <span class="ml-5">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.2218 13.2222L10.5188 10.5192M12.1959 6.48705C12.1959 9.6402 9.63977 12.1963 6.48662 12.1963C3.33348 12.1963 0.777344 9.6402 0.777344 6.48705C0.777344 3.3339 3.33348 0.777771 6.48662 0.777771C9.63977 0.777771 12.1959 3.3339 12.1959 6.48705Z" stroke="currentColor" stroke-width="1.575" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('head')
<style>
    /* Adatta select native + input ospiti allo stile tg-booking-form del template */
    .tg-booking-add-input-field .bf-native-select{
        appearance:none;-webkit-appearance:none;-moz-appearance:none;
        border:0;background:transparent;outline:none;cursor:pointer;
        width:100%;height:100%;padding-right:30px;
        font-weight:500;color:#0E1422;font-size:15px;
    }
    /* Riempi la barra di ricerca: gli input crescono, il bottone resta auto */
    .tg-booking-form-input-group{ gap:12px; }
    .tg-booking-form-input-group .tg-booking-form-parent-inner{
        flex:1 1 0;
        min-width:180px;
        margin-right:0 !important;
    }
    .tg-booking-form-input-group .tg-booking-form-search-btn{
        flex:0 0 auto;
        margin-right:0 !important;
    }
    .tg-booking-form-input-group .bk-search-button{ white-space:nowrap; }
    /* Sovrascrivi le width fisse del template (216px / 200px) per riempire il contenitore */
    .tg-booking-form-input-group .tg-booking-add-input-field,
    .tg-booking-form-input-group .tg-booking-add-input-date .input,
    .tg-booking-form-input-group .tg-booking-add-input-date{
        width:100%;
    }
    /* Il dropdown ospiti deve aprirsi sopra al resto del form */
    .tg-booking-form-parent-inner.tg-hero-quantity{ position:relative; }
    .tg-booking-form-parent-inner.tg-hero-quantity .tg-booking-form-location-list{ z-index:20; }
    .bf-guest-wrap{display:flex;align-items:center;gap:6px}
    .bf-guest-input{
        width:46px;border:0;outline:none;background:transparent;
        font-weight:500;color:#0E1422;text-align:center;font-size:15px;
        -moz-appearance:textfield;
    }
    .bf-guest-input::-webkit-outer-spin-button,
    .bf-guest-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
    .bf-guest-sep{color:#9099a5;font-weight:500}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ===== Dropdown ospiti (adulti / bambini) =====
    (function () {
        var root   = document.getElementById('bfGuestRoot');
        var toggle = document.getElementById('bfGuestToggle');
        var panel  = document.getElementById('bfGuestPanel');
        var label  = document.getElementById('bfGuestLabel');
        var adults = document.getElementById('bfAdults');
        var kids   = document.getElementById('bfChildren');
        var okBtn  = document.getElementById('bfGuestOk');
        if (!root || !toggle || !panel) return;

        function refreshLabel() {
            var a = parseInt(adults.value, 10) || 0;
            var c = parseInt(kids.value, 10) || 0;
            var parts = [];
            parts.push(a + ' ' + (a === 1 ? 'adulto' : 'adulti'));
            if (c > 0) parts.push(c + ' ' + (c === 1 ? 'bambino' : 'bambini'));
            label.textContent = parts.join(', ');
        }
        function open()  { toggle.classList.add('active');    panel.classList.add('tg-list-open'); }
        function close() { toggle.classList.remove('active'); panel.classList.remove('tg-list-open'); }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (panel.classList.contains('tg-list-open')) close(); else open();
        });
        panel.addEventListener('click', function (e) { e.stopPropagation(); });
        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) close();
        });
        if (okBtn) okBtn.addEventListener('click', close);

        // +/- handlers
        panel.querySelectorAll('.increment, .decrement').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var hidden   = document.getElementById(targetId);
                var display  = panel.querySelector('[data-quantity-display="' + targetId + '"]');
                if (!hidden || !display) return;
                var val = parseInt(hidden.value, 10) || 0;
                if (btn.classList.contains('increment')) {
                    var max = parseInt(btn.getAttribute('data-max'), 10);
                    if (!isNaN(max) && val >= max) return;
                    val++;
                } else {
                    var min = parseInt(btn.getAttribute('data-min'), 10) || 0;
                    if (val <= min) return;
                    val--;
                }
                hidden.value = val;
                display.value = val;
                refreshLabel();
            });
        });

        refreshLabel();
    })();

    // Flatpickr per il campo data
    if (typeof flatpickr !== 'undefined') {
        document.querySelectorAll('.bf-flatpickr').forEach(function (el) {
            flatpickr(el, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                minDate: el.dataset.min || 'today',
                disableMobile: true,
            });
        });
    }
});
</script>
@endpush
