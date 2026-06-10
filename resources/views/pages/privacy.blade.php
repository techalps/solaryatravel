@extends('layouts.public')

@section('title', 'Privacy Policy')
@section('meta_description', 'Informativa sul trattamento dei dati personali di Solarya Travel S.r.l. ai sensi del Regolamento UE 2016/679 (GDPR).')

@section('content')

    {{-- ============= BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="mb-3 wow fadeInUp">Privacy Policy</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Privacy Policy</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="py-130" style="padding-top:80px;padding-bottom:90px;background:#fff">
        <div class="container">
            <div class="mx-auto legal-page" style="max-width:820px">
                <p class="legal-lead">Ai sensi del Regolamento UE 2016/679 (GDPR)</p>

                <h2 class="legal-h2">1. Titolare del Trattamento</h2>
                <p class="legal-p mb-0"><strong>SOLARYA TRAVEL S.R.L.</strong></p>
                <p class="legal-p mb-0">Via Toscanini 9/C – 07026 Olbia (SS)</p>
                <p class="legal-p mb-0">P. IVA 03071410900</p>
                <p class="legal-p mb-0">Email: <a href="mailto:info@solaryatravel.com">info@solaryatravel.com</a></p>
                <p class="legal-p mb-0">PEC: <a href="mailto:solaryatravel@pec.it">solaryatravel@pec.it</a></p>
                <p class="legal-p">WhatsApp: <a href="https://wa.me/393450884743" target="_blank" rel="noopener">+39 345 088 4743</a></p>

                <h2 class="legal-h2">2. Tipologia di Dati Raccolti</h2>
                <p class="legal-p">Possono essere raccolti:</p>
                <ul class="legal-list">
                    <li>nome e cognome;</li>
                    <li>indirizzo email;</li>
                    <li>numero di telefono;</li>
                    <li>dati necessari alla prenotazione;</li>
                    <li>dati di navigazione;</li>
                    <li>indirizzo IP;</li>
                    <li>informazioni inviate tramite moduli di contatto;</li>
                    <li>dati forniti tramite WhatsApp.</li>
                </ul>

                <h2 class="legal-h2">3. Finalità del Trattamento</h2>
                <p class="legal-p">I dati sono trattati per:</p>
                <ul class="legal-list">
                    <li>gestione delle prenotazioni;</li>
                    <li>esecuzione dei servizi richiesti;</li>
                    <li>assistenza clienti;</li>
                    <li>adempimenti fiscali e amministrativi;</li>
                    <li>comunicazioni operative;</li>
                    <li>invio di newsletter e comunicazioni promozionali previo consenso;</li>
                    <li>miglioramento del sito e dei servizi offerti;</li>
                    <li>analisi statistiche.</li>
                </ul>

                <h2 class="legal-h2">4. Base Giuridica del Trattamento</h2>
                <p class="legal-p">Il trattamento è effettuato sulla base di:</p>
                <ul class="legal-list">
                    <li>esecuzione di un contratto;</li>
                    <li>obblighi di legge;</li>
                    <li>consenso dell'interessato;</li>
                    <li>legittimo interesse del titolare.</li>
                </ul>

                <h2 class="legal-h2">5. Modalità di Trattamento</h2>
                <p class="legal-p">I dati sono trattati con strumenti elettronici e cartacei adottando adeguate misure di sicurezza per prevenirne perdita, uso illecito o accessi non autorizzati.</p>

                <h2 class="legal-h2">6. Conservazione dei Dati</h2>
                <p class="legal-p">I dati saranno conservati per il tempo necessario al raggiungimento delle finalità per cui sono stati raccolti e comunque nei termini previsti dalla normativa vigente.</p>

                <h2 class="legal-h2">7. Comunicazione dei Dati</h2>
                <p class="legal-p">I dati potranno essere comunicati a:</p>
                <ul class="legal-list">
                    <li>consulenti fiscali e amministrativi;</li>
                    <li>fornitori di servizi informatici;</li>
                    <li>piattaforme di pagamento;</li>
                    <li>autorità competenti quando richiesto dalla legge.</li>
                </ul>
                <p class="legal-p">I dati non saranno diffusi pubblicamente.</p>

                <h2 class="legal-h2">8. Newsletter e Marketing</h2>
                <p class="legal-p">L'utente può iscriversi volontariamente alla newsletter. Il consenso potrà essere revocato in qualsiasi momento mediante il link presente nelle comunicazioni ricevute o scrivendo a <a href="mailto:info@solaryatravel.com">info@solaryatravel.com</a>.</p>

                <h2 class="legal-h2">9. Cookie e Strumenti di Analisi</h2>
                <p class="legal-p">Il sito può utilizzare:</p>
                <ul class="legal-list">
                    <li>cookie tecnici;</li>
                    <li>Google Analytics;</li>
                    <li>Meta Pixel;</li>
                    <li>cookie funzionali e statistici.</li>
                </ul>
                <p class="legal-p">L'utente può gestire le proprie preferenze tramite il banner cookie presente sul sito.</p>

                <h2 class="legal-h2">10. Diritti dell'Interessato</h2>
                <p class="legal-p">L'interessato può esercitare in qualsiasi momento i diritti previsti dagli articoli 15-22 del GDPR, inclusi:</p>
                <ul class="legal-list">
                    <li>accesso ai dati;</li>
                    <li>rettifica;</li>
                    <li>cancellazione;</li>
                    <li>limitazione del trattamento;</li>
                    <li>portabilità;</li>
                    <li>opposizione al trattamento.</li>
                </ul>
                <p class="legal-p">Le richieste possono essere inviate a <a href="mailto:info@solaryatravel.com">info@solaryatravel.com</a> oppure <a href="mailto:solaryatravel@pec.it">solaryatravel@pec.it</a>.</p>

                <h2 class="legal-h2">11. Reclami</h2>
                <p class="legal-p">L'interessato può presentare reclamo all'Autorità Garante per la Protezione dei Dati Personali secondo le modalità previste dalla normativa vigente.</p>
            </div>
        </div>
    </section>

    @include('partials.public.legal-styles')
@endsection
