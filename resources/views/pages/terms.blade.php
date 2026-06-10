@extends('layouts.public')

@section('title', 'Termini e Condizioni')
@section('meta_description', 'Termini e Condizioni di servizio e informativa sul diritto di recesso di Solarya Travel S.r.l.')

@section('content')

    {{-- ============= BREADCRUMB ============= --}}
    <div class="tg-breadcrumb-area pt-150 pb-90 p-relative" style="background: linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)), url('{{ asset('images/heroes/hero-tours.jpg') }}') center/cover; background-color: var(--tg-theme-primary);">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="mb-3 wow fadeInUp">Termini e Condizioni</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Termini e Condizioni</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="py-130" style="padding-top:80px;padding-bottom:90px;background:#fff">
        <div class="container">
            <div class="mx-auto legal-page" style="max-width:820px">
                <p class="legal-lead">Condizioni di servizio e informativa sul diritto di recesso</p>

                <h2 class="legal-h2">1. Titolare del Servizio</h2>
                <p class="legal-p">I servizi presenti sul sito sono erogati da:</p>
                <p class="legal-p mb-0"><strong>SOLARYA TRAVEL S.R.L.</strong></p>
                <p class="legal-p mb-0">Sede Legale: Via Toscanini 9/C – 07026 Olbia (SS)</p>
                <p class="legal-p mb-0">P. IVA: 03071410900</p>
                <p class="legal-p mb-0">Email: <a href="mailto:info@solaryatravel.com">info@solaryatravel.com</a></p>
                <p class="legal-p mb-0">PEC: <a href="mailto:solaryatravel@pec.it">solaryatravel@pec.it</a></p>
                <p class="legal-p">WhatsApp: <a href="https://wa.me/393450884743" target="_blank" rel="noopener">+39 345 088 4743</a></p>

                <h2 class="legal-h2">2. Oggetto del Servizio</h2>
                <p class="legal-p">Solarya Travel organizza e commercializza:</p>
                <ul class="legal-list">
                    <li>crociere giornaliere;</li>
                    <li>sunset cruises;</li>
                    <li>noleggio di imbarcazioni;</li>
                    <li>noleggio di imbarcazioni con skipper;</li>
                    <li>servizi con hostess di bordo;</li>
                    <li>servizi di cambusa;</li>
                    <li>esperienze turistiche e ricreative in mare.</li>
                </ul>

                <h2 class="legal-h2">3. Prenotazioni</h2>
                <p class="legal-p">La prenotazione si considera confermata al ricevimento di:</p>
                <ul class="legal-list">
                    <li>pagamento dell'acconto richiesto;</li>
                    <li>dati del referente della prenotazione;</li>
                    <li>accettazione delle presenti condizioni.</li>
                </ul>
                <p class="legal-p">Solarya Travel si riserva il diritto di rifiutare prenotazioni incomplete o non conformi alle presenti condizioni.</p>

                <h2 class="legal-h2">4. Pagamenti</h2>
                <p class="legal-p">Per confermare la prenotazione è richiesto un acconto pari al 50% del valore totale del servizio. Il saldo dovrà essere effettuato entro 12 ore precedenti l'orario previsto per la partenza.</p>
                <p class="legal-p">I pagamenti possono essere effettuati tramite:</p>
                <ul class="legal-list">
                    <li>Carta di credito;</li>
                    <li>Circuiti supportati da Stripe;</li>
                    <li>Altri sistemi eventualmente indicati sul sito.</li>
                </ul>
                <p class="legal-p">In caso di mancato saldo nei termini previsti, Solarya Travel potrà annullare la prenotazione trattenendo l'acconto versato.</p>

                <h2 class="legal-h2">5. Politica di Cancellazione</h2>
                <ul class="legal-list">
                    <li><strong>Oltre 14 giorni prima della partenza:</strong> rimborso del 70% dell'importo versato.</li>
                    <li><strong>Tra 14 e 7 giorni prima della partenza:</strong> rimborso del 50% dell'importo versato.</li>
                    <li><strong>Entro 7 giorni dalla partenza:</strong> nessun rimborso.</li>
                </ul>
                <p class="legal-p">La mancata presentazione ("No Show") comporta la perdita totale delle somme versate.</p>

                <h2 class="legal-h2">6. Condizioni Meteomarine</h2>
                <p class="legal-p">La decisione di effettuare, modificare, rinviare o annullare la navigazione spetta esclusivamente al comandante dell'imbarcazione. L'uscita potrà essere annullata per:</p>
                <ul class="legal-list">
                    <li>condizioni meteomarine avverse;</li>
                    <li>vento eccessivo;</li>
                    <li>ordinanze delle Autorità competenti;</li>
                    <li>ragioni di sicurezza;</li>
                    <li>qualsiasi situazione ritenuta incompatibile con una navigazione sicura.</li>
                </ul>
                <p class="legal-p">In caso di annullamento disposto da Solarya Travel o dal comandante, il cliente potrà scegliere tra:</p>
                <ul class="legal-list">
                    <li>riprogrammazione della data;</li>
                    <li>voucher utilizzabile entro 12 mesi;</li>
                    <li>rimborso integrale.</li>
                </ul>
                <p class="legal-p">Nuvolosità, pioggia leggera, mare mosso moderato o variazioni climatiche normali non costituiscono motivo di cancellazione gratuita.</p>

                <h2 class="legal-h2">7. Ritardi</h2>
                <p class="legal-p">I clienti devono presentarsi all'orario indicato nella conferma di prenotazione. Ritardi superiori a 30 minuti possono comportare:</p>
                <ul class="legal-list">
                    <li>riduzione del tempo dell'escursione;</li>
                    <li>perdita del diritto alla partecipazione;</li>
                    <li>assenza di qualsiasi rimborso.</li>
                </ul>

                <h2 class="legal-h2">8. Minori</h2>
                <p class="legal-p">I minori sono ammessi esclusivamente se accompagnati da almeno un genitore o da chi esercita legalmente la responsabilità genitoriale. I genitori rimangono responsabili della vigilanza e della sicurezza dei minori per tutta la durata dell'esperienza.</p>

                <h2 class="legal-h2">9. Animali</h2>
                <p class="legal-p">Gli animali non sono generalmente ammessi. Potranno essere autorizzati esclusivamente animali di piccolissima taglia previa approvazione espressa di Solarya Travel.</p>

                <h2 class="legal-h2">10. Sicurezza e Comportamento</h2>
                <p class="legal-p">Tutti i partecipanti sono tenuti a rispettare le indicazioni dello skipper, dell'equipaggio e dell'hostess. È vietato:</p>
                <ul class="legal-list">
                    <li>assumere comportamenti pericolosi;</li>
                    <li>arrecare danni all'imbarcazione;</li>
                    <li>disturbare gli altri partecipanti;</li>
                    <li>utilizzare sostanze stupefacenti;</li>
                    <li>abusare di bevande alcoliche.</li>
                </ul>
                <p class="legal-p">Il comandante può interrompere il servizio senza alcun rimborso in caso di violazione delle presenti regole.</p>

                <h2 class="legal-h2">11. Modifiche di Itinerario</h2>
                <p class="legal-p">Per ragioni di sicurezza, traffico nautico, condizioni del mare o esigenze operative, Solarya Travel può modificare:</p>
                <ul class="legal-list">
                    <li>itinerari;</li>
                    <li>tappe;</li>
                    <li>tempi di navigazione;</li>
                    <li>durata delle soste.</li>
                </ul>
                <p class="legal-p">Tali modifiche non danno diritto a rimborsi.</p>

                <h2 class="legal-h2">12. Responsabilità</h2>
                <p class="legal-p">Ogni partecipante prende parte all'attività sotto la propria responsabilità. Solarya Travel non risponde di:</p>
                <ul class="legal-list">
                    <li>oggetti smarriti;</li>
                    <li>oggetti dimenticati a bordo;</li>
                    <li>danni a beni personali;</li>
                    <li>eventi derivanti da informazioni inesatte fornite dal cliente.</li>
                </ul>
                <p class="legal-p">Resta ferma la responsabilità prevista dalla normativa vigente nei casi di dolo o colpa grave.</p>

                <h2 class="legal-h2">13. Foro Competente</h2>
                <p class="legal-p">Per ogni controversia sarà competente il Foro previsto dalla normativa applicabile a tutela del consumatore.</p>

                <hr class="legal-divider" id="recesso">

                <span class="legal-section-label">Diritto di Recesso</span>
                <h2 class="legal-subtitle">Informativa sul Diritto di Recesso</h2>
                <p class="legal-p">Ai sensi dell'articolo 59 del Decreto Legislativo 206/2005 (Codice del Consumo), il diritto di recesso di 14 giorni non si applica ai servizi relativi ad attività del tempo libero quando il contratto prevede una data o un periodo di esecuzione specifici.</p>
                <p class="legal-p">I servizi offerti da Solarya Travel, tra cui:</p>
                <ul class="legal-list">
                    <li>crociere giornaliere;</li>
                    <li>sunset cruises;</li>
                    <li>noleggio imbarcazioni;</li>
                    <li>noleggio con skipper;</li>
                    <li>servizi accessori di bordo;</li>
                </ul>
                <p class="legal-p">sono organizzati per date e orari determinati e rientrano pertanto nelle eccezioni previste dalla normativa.</p>
                <div class="legal-callout">
                    <p class="legal-p">Il cliente non può esercitare il diritto di recesso dopo la conferma della prenotazione. Restano valide esclusivamente le condizioni di cancellazione e rimborso indicate nei presenti Termini e Condizioni del servizio.</p>
                </div>
            </div>
        </div>
    </section>

    @include('partials.public.legal-styles')
@endsection
