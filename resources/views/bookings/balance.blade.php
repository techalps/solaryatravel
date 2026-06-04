@extends('layouts.public')

@section('title', 'Pagamento saldo · ' . $booking->booking_number)

@section('content')
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-bookings.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row"><div class="col-12 text-center text-white">
                <h1 class="mb-3 wow fadeInUp">Pagamento del saldo</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Saldo</li>
                    </ol>
                </nav>
            </div></div>
        </div>
    </div>

    <section class="py-130" style="padding-top:70px;padding-bottom:90px;background:#fff">
        <div class="container">
            <div class="mx-auto" style="max-width:600px">
                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-2" style="color:#0E1B33">Completa il pagamento</h2>
                    <p class="text-muted mb-0">Prenotazione <strong>#{{ $booking->booking_number }}</strong> · {{ $booking->tour->name ?? '' }}</p>
                </div>

                <div class="p-4 p-md-5 rounded-4" style="background:#f8fafc;border:1px solid #e9eef5">
                    <table class="w-100" style="font-size:.95rem">
                        <tr><td class="py-1 text-muted">Totale prenotazione</td><td class="py-1 text-end">€ {{ number_format((float) $booking->total_amount, 2, ',', '.') }}</td></tr>
                        <tr><td class="py-1 text-muted">Acconto versato</td><td class="py-1 text-end">− € {{ number_format((float) $booking->amount_paid, 2, ',', '.') }}</td></tr>
                        <tr style="border-top:1px solid #e9eef5"><td class="pt-2 fw-bold" style="color:#0E1B33">Saldo da pagare</td><td class="pt-2 text-end fw-bold" style="color:var(--tg-theme-primary);font-size:1.3rem">€ {{ number_format($balanceAmount, 2, ',', '.') }}</td></tr>
                    </table>

                    @if($booking->balance_due_at)
                        <div class="d-flex align-items-center gap-2 mt-3 p-2 rounded-3" style="background:#fffbeb;border:1px solid #fde68a">
                            <i class="fa-solid fa-clock text-warning"></i>
                            <div class="small" style="color:#92400e">Salda entro il {{ \Carbon\Carbon::parse($booking->balance_due_at)->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('booking.balance.pay', $booking->uuid) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="tg-btn w-100">
                            <i class="fa-solid fa-lock me-2"></i>Paga il saldo con carta
                        </button>
                    </form>
                    <small class="d-block text-muted text-center mt-3" style="font-size:.78rem">
                        <i class="fa-brands fa-cc-stripe me-1"></i>Pagamento sicuro tramite Stripe
                    </small>
                </div>
            </div>
        </div>
    </section>

    <style>.tg-btn{background:var(--tg-theme-primary);color:#fff;padding:14px 22px;font-weight:600;border:none;border-radius:50px}.tg-btn:hover{background:var(--tg-theme-secondary);color:#fff}</style>
@endsection
