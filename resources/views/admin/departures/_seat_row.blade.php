@php
    /** @var \App\Models\BookingSeat $seat */
    $guestName = trim(($seat->guest_first_name ?? '') . ' ' . ($seat->guest_last_name ?? ''));
    if ($guestName === '') {
        $guestName = trim(($seat->booking->customer_first_name ?? '') . ' ' . ($seat->booking->customer_last_name ?? '')) ?: '—';
    }
    $bracketLabel = $seat->ageBracket?->label;
    $currentCatId = (int) ($seat->catamaran_id ?? 0);
    $isSpanning = $seat->is_spanning ?? false; // posto di una prenotazione multi-giorno (uso esclusivo)
@endphp
<li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 px-0"
    data-seat-row
    data-seat-id="{{ $seat->id }}">
    <div class="me-2 d-flex align-items-start gap-2 flex-grow-1" style="min-width:0">
        @unless ($isSpanning)
            {{-- Selezione per lo spostamento in blocco. I posti "spanning" (uso
                 esclusivo su più giorni) non sono spostabili, quindi niente casella. --}}
            <input type="checkbox"
                   name="seat_ids[]"
                   class="form-check-input mt-1 flex-shrink-0"
                   data-seat-check
                   data-from-catamaran="{{ $currentCatId }}"
                   value="{{ $seat->id }}"
                   aria-label="Seleziona {{ $guestName }}">
        @endunless
        <div>
        <div class="fw-semibold">
            {{ $guestName }}
            @if ($seat->is_primary)
                <span class="badge bg-primary-subtle text-primary ms-1" title="Intestatario">★</span>
            @endif
            @if ($isSpanning)
                <span class="badge bg-info-subtle text-info ms-1" title="Prenotazione su più giorni (uso esclusivo)"><i class="bi bi-water me-1"></i>uso esclusivo</span>
            @endif
        </div>
        <div class="small text-muted">
            @if ($bracketLabel)
                {{ $bracketLabel }} ·
            @endif
            <a href="{{ route('admin.bookings.show', $seat->booking) }}" class="text-decoration-none">#{{ $seat->booking->booking_number }}</a>
            · <span class="font-monospace">{{ $seat->qr_code }}</span>
            @if ($isSpanning && $seat->span_origin_date)
                · <span class="text-info">partenza {{ \Carbon\Carbon::parse($seat->span_origin_date)->format('d/m') }}</span>
            @endif
            @if ($seat->boarded_at)
                · <span class="text-success"><i class="bi bi-check2"></i> imbarcato</span>
            @endif
        </div>
        </div>
    </div>
    @if ($isSpanning)
        {{-- Posto di una prenotazione che parte in un altro giorno: sola lettura. --}}
        <span class="badge bg-light text-muted border">Riservato l'intero periodo</span>
    @else
    <form method="POST"
          action="{{ route('admin.bookings.seats.move', ['booking' => $seat->booking, 'seat' => $seat]) }}"
          data-move-seat
          class="d-flex align-items-center gap-2">
        @csrf
        <select name="catamaran_id"
                class="form-select form-select-sm"
                data-initial="{{ $currentCatId }}"
                style="min-width: 170px;">
            @php $exclusiveByCatamaran = $exclusiveByCatamaran ?? []; @endphp
            @foreach ($catamarans as $cat)
                @php
                    $cs = $stats[$cat->id] ?? ['count' => 0, 'capacity' => (int) $cat->capacity, 'free' => (int) $cat->capacity];
                    $isCurrent = $currentCatId === (int) $cat->id;
                    $isExclusive = isset($exclusiveByCatamaran[$cat->id]);
                    $isFull = $cs['free'] === 0 && !$isCurrent;
                @endphp
                {{-- I catamarani a uso esclusivo non sono una destinazione valida: si escludono
                     dalle opzioni (tranne quello attuale, per non perdere il valore selezionato). --}}
                @continue($isExclusive && !$isCurrent)
                <option value="{{ $cat->id }}"
                        data-full="{{ $isFull ? '1' : '0' }}"
                        @selected($isCurrent)>
                    {{ $cat->name }} ({{ $cs['count'] }}/{{ $cs['capacity'] }}){{ $isFull ? ' · pieno' : '' }}
                </option>
            @endforeach
        </select>
    </form>
    @endif
</li>
