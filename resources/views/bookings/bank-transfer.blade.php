@extends('layouts.public')

@section('title', 'Bonifico bancario · ' . $booking->booking_number)

@section('content')
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row"><div class="col-12 text-center text-white">
                <h1 class="mb-3 wow fadeInUp">Prenotazione registrata</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Bonifico</li>
                    </ol>
                </nav>
            </div></div>
        </div>
    </div>

    <section class="py-130" style="padding-top:70px;padding-bottom:90px;background:#fff">
        <div class="container">
            <div class="mx-auto" style="max-width:680px">

                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fff7ed;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px">
                        <i class="fa-solid fa-building-columns" style="font-size:26px;color:#d97706"></i>
                    </div>
                    <h2 class="fw-bold mb-2" style="color:#0E1B33">Completa il pagamento con bonifico</h2>
                    <p class="text-muted mb-0">La prenotazione <strong>#{{ $booking->booking_number }}</strong> è in attesa del bonifico. Una volta ricevuto, la confermeremo e riceverai i biglietti via email.</p>
                </div>

                <div class="p-4 p-md-5 rounded-4" style="background:#f8fafc;border:1px solid #e9eef5">
                    <div class="text-center mb-4">
                        <div class="small text-uppercase fw-bold text-muted" style="letter-spacing:.06em">Importo da versare</div>
                        <div class="fw-bold" style="font-size:2.2rem;color:var(--tg-theme-primary)">€ {{ number_format($amountDue, 2, ',', '.') }}</div>
                        @if($booking->payment_type === 'deposit')
                            <span class="badge text-bg-info">Acconto · saldo successivo</span>
                        @endif
                    </div>

                    @if($bankDetails)
                        <div class="mb-3">
                            <div class="small text-uppercase fw-bold text-muted mb-1" style="letter-spacing:.06em">Coordinate bancarie</div>
                            <pre class="mb-0 p-3 rounded-3" style="background:#fff;border:1px solid #e9eef5;white-space:pre-wrap;font-family:inherit;font-size:.95rem;color:#0E1B33">{{ $bankDetails }}</pre>
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 p-3 rounded-3" style="background:#fffbeb;border:1px solid #fde68a">
                        <i class="fa-solid fa-circle-info text-warning"></i>
                        <div class="small" style="color:#92400e">Indica come <strong>causale</strong> il numero di prenotazione: <strong>{{ $booking->booking_number }}</strong></div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('booking.show', $booking->uuid) }}" class="tg-btn">Vai alla mia prenotazione</a>
                </div>
                <p class="text-center text-muted small mt-3 mb-0">Ti abbiamo inviato queste istruzioni anche via email a {{ $booking->customer_email }}.</p>
            </div>
        </div>
    </section>
@endsection
