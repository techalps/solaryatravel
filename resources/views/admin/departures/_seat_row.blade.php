@php
    /** @var \App\Models\BookingSeat $seat */
    $guestName = trim(($seat->guest_first_name ?? '') . ' ' . ($seat->guest_last_name ?? ''));
    if ($guestName === '') {
        $guestName = trim(($seat->booking->customer_first_name ?? '') . ' ' . ($seat->booking->customer_last_name ?? '')) ?: '—';
    }
    $bracketLabel = $seat->ageBracket?->label;
    $currentCatId = (int) ($seat->catamaran_id ?? 0);
@endphp
<li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 px-0">
    <div class="me-2">
        <div class="fw-semibold">
            {{ $guestName }}
            @if ($seat->is_primary)
                <span class="badge bg-primary-subtle text-primary ms-1" title="Intestatario">★</span>
            @endif
        </div>
        <div class="small text-muted">
            @if ($bracketLabel)
                {{ $bracketLabel }} ·
            @endif
            <a href="{{ route('admin.bookings.show', $seat->booking) }}" class="text-decoration-none">#{{ $seat->booking->booking_number }}</a>
            · <span class="font-monospace">{{ $seat->qr_code }}</span>
            @if ($seat->boarded_at)
                · <span class="text-success"><i class="bi bi-check2"></i> imbarcato</span>
            @endif
        </div>
    </div>
    <form method="POST"
          action="{{ route('admin.bookings.seats.move', ['booking' => $seat->booking, 'seat' => $seat]) }}"
          data-move-seat
          class="d-flex align-items-center gap-2">
        @csrf
        <select name="catamaran_id"
                class="form-select form-select-sm"
                data-initial="{{ $currentCatId }}"
                style="min-width: 200px;">
            @foreach ($catamarans as $cat)
                @php
                    $cs = $stats[$cat->id] ?? ['count' => 0, 'capacity' => (int) $cat->capacity, 'free' => (int) $cat->capacity];
                    $isCurrent = $currentCatId === (int) $cat->id;
                    $isFull = $cs['free'] === 0 && !$isCurrent;
                @endphp
                <option value="{{ $cat->id }}"
                        data-full="{{ $isFull ? '1' : '0' }}"
                        @selected($isCurrent)>
                    {{ $cat->name }} ({{ $cs['count'] }}/{{ $cs['capacity'] }}){{ $isFull ? ' · pieno' : '' }}
                </option>
            @endforeach
        </select>
    </form>
</li>
