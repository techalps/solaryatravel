@extends('layouts.auth')

@section('title', 'Prossimamente — Solarya Travel')
@section('meta_description', 'Il sito di Solarya Travel sarà presto online.')

@push('head')
<style>
    .cs-wrap {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 16px;
        background: linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.55)),
                    url('{{ asset('assets/template/img/hero/hero-1.jpg') }}') center/cover no-repeat;
        background-attachment: fixed;
    }
    .cs-card {
        width: 100%;
        max-width: 560px;
        background: #fff;
        border-radius: 20px;
        padding: 56px 48px 48px;
        box-shadow: 0 24px 64px rgba(0,0,0,.18);
        text-align: center;
    }
    @media (max-width: 480px) {
        .cs-card { padding: 40px 26px 32px; }
    }
    .cs-logo {
        display: block;
        margin: 0 auto 32px;
        height: 38px;
        width: auto;
    }
    .cs-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(124,55,255,.1);
        color: var(--tg-theme-primary);
        font-weight: 700;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        padding: 7px 16px;
        border-radius: 50px;
        margin-bottom: 22px;
    }
    .cs-badge i { font-size: .7rem; }
    .cs-title {
        color: var(--tg-common-black, #0E1B33);
        font-weight: 700;
        font-size: clamp(1.8rem, 4vw, 2.6rem);
        line-height: 1.15;
        margin-bottom: 16px;
    }
    .cs-text {
        color: #64748b;
        font-size: 1.02rem;
        line-height: 1.7;
        margin-bottom: 30px;
    }
    .cs-contacts {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
        margin-bottom: 8px;
    }
    .cs-contact {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--tg-common-black, #0E1B33);
        text-decoration: none;
        font-weight: 600;
        font-size: .95rem;
    }
    .cs-contact i { color: var(--tg-theme-primary); width: 18px; }
    .cs-contact:hover { color: var(--tg-theme-primary); }
    .cs-social {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 26px;
    }
    .cs-social a {
        width: 40px; height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f1f5f9;
        color: #475569;
        text-decoration: none;
        transition: background .2s, color .2s;
    }
    .cs-social a:hover { background: var(--tg-theme-primary); color: #fff; }
    .cs-admin-link {
        display: inline-block;
        margin-top: 30px;
        font-size: .82rem;
        color: #94a3b8;
        text-decoration: none;
    }
    .cs-admin-link:hover { color: var(--tg-theme-primary); text-decoration: underline; }
</style>
@endpush

@section('content')
    <div class="cs-wrap">
        <div class="cs-card">
            <img class="cs-logo" src="{{ asset('images/logo_black.svg') }}" alt="Solarya Travel">

            <span class="cs-badge"><i class="fa-solid fa-anchor"></i> Prossimamente online</span>

            <h1 class="cs-title">Stiamo preparando qualcosa di speciale</h1>
            <p class="cs-text">
                Il nuovo sito di Solarya Travel sarà presto disponibile.<br>
                Per informazioni e prenotazioni puoi contattarci direttamente.
            </p>

            <div class="cs-contacts">
                <a class="cs-contact" href="mailto:info@solaryatravel.com"><i class="fa-solid fa-envelope"></i> info@solaryatravel.com</a>
                <a class="cs-contact" href="https://wa.me/393450884743" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Scrivici su WhatsApp</a>
            </div>

            <div class="cs-social">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            </div>

            <a class="cs-admin-link" href="{{ route('login') }}">Area riservata</a>
        </div>
    </div>
@endsection
