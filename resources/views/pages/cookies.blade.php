@extends('layouts.public')

@section('title', 'Cookie Policy')
@section('meta_description', 'Cookie Policy di Solarya Travel S.r.l.: tipologie di cookie utilizzati, finalità e modalità di gestione del consenso.')

@section('content')

    {{-- ============= BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="mb-3 wow fadeInUp">Cookie Policy</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Cookie Policy</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="py-130" style="padding-top:80px;padding-bottom:90px;background:#fff">
        <div class="container">
            <div class="mx-auto legal-page" style="max-width:820px">
                <p class="legal-lead">La presente Cookie Policy descrive le tipologie di cookie utilizzati dal sito di Solarya Travel S.r.l., le relative finalità e le modalità con cui l'utente può gestire il proprio consenso.</p>

                <h2 class="legal-h2">1. Cosa sono i cookie</h2>
                <p class="legal-p">I cookie sono piccoli file di testo che i siti visitati inviano al dispositivo dell'utente, dove vengono memorizzati per essere ritrasmessi agli stessi siti alla visita successiva. Tecnologie analoghe (come i pixel e l'archiviazione locale del browser) svolgono funzioni simili. Per semplicità, in questo documento tutte queste tecnologie sono indicate come "cookie".</p>

                <h2 class="legal-h2">2. Titolare del trattamento</h2>
                <p class="legal-p mb-0"><strong>SOLARYA TRAVEL S.R.L.</strong> — Via Toscanini 9/C – 07026 Olbia (SS) — P. IVA 03071410900</p>
                <p class="legal-p">Email: <a href="mailto:info@solaryatravel.com">info@solaryatravel.com</a> — PEC: <a href="mailto:solaryatravel@pec.it">solaryatravel@pec.it</a></p>

                <h2 class="legal-h2">3. Tipologie di cookie utilizzati</h2>

                <h3 class="legal-h3">a) Cookie tecnici e necessari</h3>
                <p class="legal-p">Sono indispensabili per il corretto funzionamento del sito e non richiedono il consenso dell'utente. Comprendono i cookie di sessione per l'autenticazione, la gestione delle prenotazioni e la memorizzazione delle preferenze sul consenso ai cookie. Senza questi cookie il sito potrebbe non funzionare correttamente.</p>

                <h3 class="legal-h3">b) Cookie statistici</h3>
                <p class="legal-p">Vengono installati solo previo consenso e ci permettono di analizzare in forma aggregata come gli utenti utilizzano il sito, al fine di migliorarne contenuti e servizi. Il sito utilizza <strong>Google Analytics 4</strong> con indirizzo IP anonimizzato.</p>

                <h3 class="legal-h3">c) Cookie di marketing</h3>
                <p class="legal-p">Vengono installati solo previo consenso e sono utilizzati per misurare l'efficacia delle campagne pubblicitarie e mostrare annunci pertinenti, anche su piattaforme di terze parti. Il sito può utilizzare <strong>Meta Pixel</strong> (Facebook/Instagram) e strumenti di remarketing di <strong>Google Ads</strong>.</p>

                <p class="legal-p">La gestione dei tag statistici e di marketing può avvenire tramite <strong>Google Tag Manager</strong>, strumento che si limita a orchestrare il caricamento degli altri script nel rispetto delle scelte espresse dall'utente.</p>

                <h2 class="legal-h2">4. Cookie di terze parti</h2>
                <p class="legal-p">Alcuni cookie sono installati da fornitori terzi. Per i relativi trattamenti e le possibilità di opt-out si rimanda alle rispettive informative:</p>
                <ul class="legal-list">
                    <li>Google (Analytics, Ads, Tag Manager): <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a></li>
                    <li>Meta (Facebook/Instagram Pixel): <a href="https://www.facebook.com/privacy/policy" target="_blank" rel="noopener">facebook.com/privacy/policy</a></li>
                    <li>Stripe (gestione pagamenti): <a href="https://stripe.com/privacy" target="_blank" rel="noopener">stripe.com/privacy</a></li>
                </ul>

                <h2 class="legal-h2">5. Base giuridica e conservazione</h2>
                <p class="legal-p">I cookie tecnici sono trattati sulla base del legittimo interesse del titolare a erogare il servizio richiesto. I cookie statistici e di marketing sono trattati esclusivamente sulla base del consenso dell'utente, liberamente revocabile in qualsiasi momento. I cookie hanno durate differenti: i cookie di sessione si cancellano alla chiusura del browser, mentre i cookie persistenti hanno una durata massima variabile secondo le indicazioni dei rispettivi fornitori.</p>

                <h2 class="legal-h2">6. Gestione del consenso</h2>
                <p class="legal-p">Al primo accesso al sito viene mostrato un banner che consente di accettare, rifiutare o personalizzare l'uso dei cookie non necessari. È possibile modificare le proprie scelte in qualsiasi momento tramite il pannello delle preferenze:</p>
                <div class="legal-callout">
                    <p class="legal-p mb-0">
                        <a href="#" class="open-cookie-settings"><strong>→ Gestisci le preferenze cookie</strong></a>
                    </p>
                </div>
                <p class="legal-p">In alternativa, è possibile gestire o disabilitare i cookie tramite le impostazioni del proprio browser:</p>
                <ul class="legal-list">
                    <li><strong>Chrome:</strong> Impostazioni → Privacy e sicurezza → Cookie e altri dati dei siti</li>
                    <li><strong>Firefox:</strong> Impostazioni → Privacy e sicurezza → Cookie e dati dei siti web</li>
                    <li><strong>Safari:</strong> Preferenze → Privacy → Cookie e dati dei siti web</li>
                    <li><strong>Edge:</strong> Impostazioni → Privacy, ricerca e servizi → Cookie</li>
                </ul>
                <p class="legal-p">La disattivazione dei cookie tecnici può compromettere il corretto funzionamento del sito.</p>

                <h2 class="legal-h2">7. Diritti dell'interessato</h2>
                <p class="legal-p">L'utente può esercitare in qualsiasi momento i diritti previsti dagli articoli 15-22 del Regolamento UE 2016/679 (GDPR) scrivendo a <a href="mailto:info@solaryatravel.com">info@solaryatravel.com</a>. Per ulteriori informazioni sul trattamento dei dati personali si rimanda alla <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>

                <h2 class="legal-h2">8. Aggiornamenti</h2>
                <p class="legal-p">La presente Cookie Policy può essere aggiornata periodicamente. Si invita l'utente a consultare regolarmente questa pagina per essere informato su eventuali modifiche.</p>

                <p class="legal-p small text-muted mt-4">Ultimo aggiornamento: {{ date('d/m/Y') }}</p>
            </div>
        </div>
    </section>

    @include('partials.public.legal-styles')
@endsection
