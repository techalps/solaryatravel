@extends('layouts.admin')

@section('title', 'Modifica prenotazione ' . $booking->booking_number)

@section('content')
    @php
        $statusValue = $booking->status?->value ?? (string) $booking->status;
    @endphp

    {{-- Header --}}
    <div class="dash-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left"></i> Torna alla prenotazione
                </a>
            </div>
            <h1 class="mb-1">Modifica #{{ $booking->booking_number }}</h1>
            <p class="text-muted mb-0">
                {{ $booking->customer_first_name }} {{ $booking->customer_last_name }}
                · {{ $booking->tour?->name ?? '—' }}
                @if ($booking->departure?->departure_date)
                    · {{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }}
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-light rounded-pill border px-3 fw-semibold">
                Annulla
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger rounded-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i>Dettagli modificabili</h5>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Stato</label>
                            <select name="status" id="status" class="form-select">
                                @foreach ($statuses as $st)
                                    <option value="{{ $st->value }}" {{ old('status', $statusValue) === $st->value ? 'selected' : '' }}>
                                        {{ $st->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="form-label fw-semibold">Telefono cliente</label>
                            <input type="text" name="customer_phone" id="customer_phone"
                                   class="form-control"
                                   value="{{ old('customer_phone', $booking->customer_phone) }}"
                                   maxlength="30"
                                   placeholder="+39 ...">
                        </div>

                        <div class="mb-0">
                            <label for="special_requests" class="form-label fw-semibold">Note / richieste</label>
                            <textarea name="special_requests" id="special_requests"
                                      class="form-control" rows="4" maxlength="1000"
                                      placeholder="Note interne o richieste del cliente">{{ old('special_requests', $booking->special_requests) }}</textarea>
                            <small class="text-muted">Max 1000 caratteri.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Riferimento</h5>

                        <div class="mb-2">
                            <div class="text-muted small">Cliente</div>
                            <div class="fw-semibold">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">{{ $booking->customer_email }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Tour</div>
                            <div class="fw-semibold">{{ $booking->tour?->name ?? '—' }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Partenza</div>
                            <div class="fw-semibold">
                                @if ($booking->departure)
                                    {{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }}
                                    · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}
                                @else — @endif
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted small">Posti</div>
                            <div class="fw-semibold">{{ $booking->seats }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                        <i class="bi bi-check2 me-1"></i> Salva modifiche
                    </button>
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-light border rounded-pill fw-semibold py-2">
                        Annulla
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection
