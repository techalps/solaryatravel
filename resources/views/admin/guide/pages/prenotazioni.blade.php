<h2>L'elenco prenotazioni</h2>
<p>
    L'elenco è ordinato per <strong>numero di prenotazione</strong> (dal più recente).
    Puoi <strong>riordinare per qualsiasi colonna</strong> cliccando sulla sua intestazione
    (un secondo click inverte l'ordine). L'ordinamento vale su tutte le prenotazioni, non solo
    sulla pagina visibile.
</p>
<p>
    La colonna <strong>Data</strong> mostra <strong>andata e ritorno</strong>: per i tour normali
    coincidono come giorno e l'orario di ritorno deriva dalla durata del tour; per le prenotazioni
    a uso esclusivo valgono gli orari indicati (con il badge "Uso esclusivo"). L'orario di
    registrazione della prenotazione è mostrato in ora italiana.
</p>
<p>
    Puoi <strong>filtrare per stato, tour, agenzia e periodo</strong>. Le prenotazioni fatte da
    un'agenzia mostrano il badge <strong>B2B</strong> con il nome dell'agenzia. Le prenotazioni
    <strong>future</strong> con documenti d'identità incompleti mostrano il badge
    <strong>"Doc. mancante"</strong>: in cima all'elenco un avviso le conta e permette di
    <em>filtrarle</em> per completarle (vedi il capitolo Documenti d'identità).
</p>

<h2>Segnare le prenotazioni come completate</h2>
<p>
    Dopo che un tour si è svolto, la prenotazione va portata a <strong>Completata</strong>. Dall'elenco
    puoi farlo in due modi, senza aprire il dettaglio.
</p>
<p>
    <strong>Una alla volta:</strong> sulle prenotazioni confermate la cui partenza è già avvenuta
    compare l'icona <i class="bi bi-flag-fill"></i> <strong>bandierina</strong> accanto all'occhio.
    Un click (con conferma) e la prenotazione è completata. Sulle altre l'icona non compare.
</p>
<p>
    <strong>In blocco:</strong> seleziona le prenotazioni con la casella a inizio riga (quella
    nell'intestazione le seleziona tutte nella pagina), poi premi
    <strong>"Segna come completate"</strong> nella barra che appare in alto.
</p>
<ul>
    <li>
        Vale solo per le prenotazioni <strong>Confermate</strong> con la <strong>partenza già
        avvenuta</strong>. Se nella selezione ce ne sono altre, vengono saltate e ti vengono
        segnalate per numero: le restanti procedono comunque.
    </li>
    <li>
        <strong>Non serve il check-in.</strong> Il passaggio va direttamente da Confermata a
        Completata, anche se a bordo non è stato scansionato nessun QR.
    </li>
    <li>
        Un tour del mattino è completabile <strong>già nel pomeriggio dello stesso giorno</strong>:
        conta l'orario di fine della partenza, non la mezzanotte.
    </li>
    <li>
        La data registrata come completamento è quella della <strong>partenza</strong>, non il
        giorno in cui premi il pulsante: così i report restano corretti anche a distanza di tempo.
    </li>
    <li>
        La selezione riguarda <strong>solo la pagina che stai vedendo</strong>: per gruppi ampi
        restringi prima con i filtri (per esempio stato "Confermata" e un periodo passato).
    </li>
</ul>
<div class="guide-tip">
    Completare <strong>non invia nessuna email</strong> al cliente e non tocca posti, riserve o
    incassi: cambia solo lo stato. Annullamenti e rimborsi restano sul dettaglio della
    prenotazione, dove hanno le loro conferme e i loro effetti economici.
</div>

<h2>Modificare una prenotazione (disdette)</h2>
<p>
    Dal dettaglio prenotazione, con <strong>Modifica</strong>, puoi <strong>disdire singoli
    partecipanti o extra</strong>: spunta chi/cosa rimuovere e premi "Rimuovi selezionati".
    Prima di confermare appare un riepilogo con il <strong>rimborso/penale calcolato sulla somma
    dei rimossi</strong> (stessa policy dell'annullamento, in base ai giorni alla partenza), con le
    opzioni: penale da policy, rimborso totale, importo personalizzato, nessun rimborso.
</p>
<ul>
    <li>L'<strong>intestatario</strong> non è rimovibile e deve restare almeno un partecipante (per azzerare tutto si usa l'annullamento).</li>
    <li>Gli elementi rimossi restano nello <strong>storico</strong> segnati come <em>"Disdetto"</em>, sia in admin sia nella pagina del cliente.</li>
    <li>Posti, totale e disponibilità del catamarano si <strong>ricalcolano</strong> automaticamente.</li>
    <li>Il rimborso segue il pagamento: su carta è eseguito via Stripe, per bonifico/contanti è da effettuare manualmente.</li>
</ul>
<p>
    Dallo stesso dettaglio puoi anche <strong>modificare il documento</strong> di un passeggero
    (colonna Documento → Modifica/Inserisci) e <strong>spostare un passeggero su un altro catamarano</strong>
    (colonna Catamarano → "Sposta su…"). Vedi i capitoli <em>Documenti d'identità</em> e <em>Catamarani e posti</em>.
</p>

<h2>Cambiare la data di una prenotazione</h2>
<p>
    Dalla <strong>Modifica</strong>, nella sezione <strong>"Cambia data"</strong>, scegli la nuova
    data e l'orario, poi premi <em>"Verifica"</em>: il sistema mostra il <strong>nuovo totale</strong>
    e l'eventuale conguaglio rispetto a quanto già pagato.
</p>
<ul>
    <li><strong>Nuova data più cara</strong> → conguaglio da incassare. Segue il metodo di pagamento
        originale: con <strong>carta</strong> viene generato un link Stripe per la differenza (lo invii
        dal dettaglio); con <strong>bonifico/contanti</strong> scegli se segnarlo già incassato o in attesa.</li>
    <li><strong>Nuova data più economica</strong> → scegli se non rimborsare, rimborsare la differenza
        o un importo personalizzato (rimborso su Stripe o manuale secondo il pagamento).</li>
    <li><strong>Stesso prezzo</strong> → la prenotazione viene semplicemente spostata.</li>
</ul>
<p class="text-muted small">I prezzi dei posti vengono ricalcolati in base al periodo della nuova data; vale solo per i partecipanti attivi (non quelli disdetti).</p>
<div class="guide-tip">
    <strong>Assegnazione dei catamarani:</strong> spostando la prenotazione i passeggeri vengono
    <strong>ricollocati sulle barche libere della nuova data</strong>, tenendosi uniti quando possibile.
    Se sulla nuova data la barca di prima è <strong>riservata in uso esclusivo</strong>, in manutenzione
    o piena, i passeggeri passano a un'altra barca; se non c'è posto per tutti lo spostamento viene
    <strong>rifiutato e segnalato</strong> (te ne accorgi già premendo <em>"Verifica"</em>).
    Controlla l'assegnazione nel dettaglio dopo lo spostamento.
</div>

<h2>Cambiare il prezzo (tour su richiesta / catamarano riservato)</h2>
<p>
    Sulle prenotazioni a <strong>prezzo manuale</strong> puoi modificare il totale dalla
    <strong>Modifica</strong>, nel campo <em>"Prezzo totale"</em>. Serve quando il cliente aggiunge
    servizi in corsa (il prezzo sale) o quando si concorda uno sconto (il prezzo scende).
</p>
<div class="guide-tip">
    <strong>Attenzione:</strong> nel campo va scritto il <strong>nuovo totale</strong> della
    prenotazione, non la differenza. Se erano €1.000 e aggiungi €200 di extra, scrivi
    <strong>€1.200</strong>.
</div>
<p>
    Salvando, se il totale è cambiato si apre una finestra che mostra vecchio totale, nuovo totale e
    differenza, e ti chiede come gestirla. <strong>Nessuna email parte in automatico:</strong> il
    sistema prepara quello che serve e sei tu a contattare il cliente come preferisci.
</p>
<p><strong>Se il prezzo aumenta</strong> — scegli come incassare la differenza:</p>
<ul>
    <li><strong>Genera link di pagamento</strong> → viene creato un link Stripe per la <em>sola
        differenza</em>. Lo trovi nel dettaglio prenotazione, con il pulsante per copiarlo: lo mandi
        tu al cliente (WhatsApp, email, come vuoi) e resta salvato sulla prenotazione.</li>
    <li><strong>Bonifico</strong> → la differenza resta come importo da incassare. Nel dettaglio
        compare il riquadro <em>Da incassare</em>: quando i soldi arrivano premi
        <em>"Registra incasso"</em>.</li>
</ul>
<p class="text-muted">
    In entrambi i casi la prenotazione <strong>resta confermata</strong>: è valida e il cliente parte
    comunque, cambia solo l'importo ancora da incassare.
</p>
<div class="guide-tip">
    <strong>Il cliente non viene avvisato dal sistema.</strong> Nessuna email parte in automatico su
    una variazione di prezzo, né in aumento né in diminuzione: è una scelta voluta, perché queste
    cose si concordano prima a voce. Ricordati quindi di contattarlo tu, altrimenti riceverà un
    addebito o un rimborso senza spiegazioni.
</div>
<p><strong>Se il prezzo diminuisce</strong> — scegli se e come restituire la differenza:</p>
<ul>
    <li><strong>Storno su Stripe</strong> → il rimborso parte subito sulla carta del cliente
        (disponibile solo se aveva pagato con carta).</li>
    <li><strong>Bonifico</strong> → il sistema registra l'importo da restituire e lo tiene in
        evidenza nel dettaglio. Fai il bonifico e poi premi <em>"Ho eseguito lo storno"</em>: solo
        allora l'uscita entra nei report di cassa. L'IBAN te lo fai dare tu dal cliente.</li>
    <li><strong>Nessuno storno</strong> → abbassi solo il prezzo senza restituire denaro (per
        esempio se lo tieni come credito per una prossima uscita).</li>
</ul>
<p class="text-muted small">
    Lo storno restituisce l'<strong>intero importo</strong> concordato: le penali di cancellazione
    non si applicano, perché qui non si annulla nulla, si corregge il prezzo. Non puoi comunque
    stornare più di quanto il cliente ha effettivamente versato.
</p>

<h2>Cliente con account</h2>
<p>
    Se inserisci l'<strong>email di un cliente già registrato</strong>, la prenotazione viene
    collegata al suo account e <strong>compare nel suo storico</strong> ("Le mie prenotazioni").
    Se l'email non ha ancora un account, la prenotazione resta valida e — quando il cliente si
    registrerà con quella stessa email — comparirà automaticamente nel suo storico.
</p>

<h2>Correggere l'email del cliente</h2>
<p>
    Da <strong>Modifica prenotazione</strong> puoi correggere l'<strong>email del cliente</strong>.
    Serve quando è stata sbagliata in fase di inserimento: con un indirizzo errato il cliente non
    riceve <strong>niente</strong> — né link di pagamento, né biglietti, né promemoria.
</p>
<ul>
    <li>
        Dopo la correzione, <strong>reinvia la comunicazione</strong> dal dettaglio: "Reinvia
        biglietti" se è confermata, "Invia link di pagamento" se c'è ancora da pagare. La modifica
        dell'email da sola non rimanda nulla.
    </li>
    <li>
        Ogni correzione resta <strong>tracciata</strong> (indirizzo precedente, chi l'ha cambiata,
        data e ora): se un cliente sostiene di non aver ricevuto nulla, si ricostruisce dove era
        stato spedito.
    </li>
    <li>
        Lo stesso può fare l'<strong>agenzia</strong> dal portale, sulle proprie prenotazioni
        (vedi il capitolo Canale agenzie).
    </li>
</ul>

<h2>Scrivere al cliente su WhatsApp</h2>
<p>
    Nella scheda della prenotazione, sotto i dati del cliente, trovi il pulsante
    <strong>"Scrivi su WhatsApp"</strong>. Apre la chat col cliente e prepara già il messaggio con
    numero di prenotazione, tour e data: ti basta completarlo e inviare.
</p>
<ul>
    <li>Si apre WhatsApp Web o l'app del computer: il messaggio <strong>non parte da solo</strong>, lo invii tu.</li>
    <li>
        Se al posto del pulsante vedi la scritta <em>"Nessun numero di telefono in prenotazione"</em>,
        il cliente non ha lasciato il numero. Se invece leggi <em>"Numero non valido per WhatsApp"</em>,
        il numero c'è ma è incompleto o scritto male: correggilo dalla Modifica prenotazione.
    </li>
    <li>
        Per i numeri stranieri conviene che il campo <strong>Paese</strong> sia corretto: serve a
        ricostruire il prefisso internazionale quando il cliente non l'ha scritto.
    </li>
    <li>
        Se il cliente prenota da <strong>account registrato</strong>, il telefono del suo profilo
        viene proposto già compilato nel form. Resta comunque modificabile: il numero salvato
        sulla prenotazione è quello che si vede nella scheda.
    </li>
</ul>
<p>
    <strong>Anche il cliente può scrivervi.</strong> Il pulsante "Scrivici su WhatsApp" compare in tre
    punti: nella <strong>pagina della sua prenotazione</strong> (il link che riceve), nella
    <strong>conferma del pagamento</strong> e nell'<strong>email dei biglietti</strong>. Il messaggio
    parte già con numero di prenotazione, tour e data, così quando vi scrive sapete subito di cosa
    si tratta senza doverglielo chiedere.
</p>
<p>
    Il numero che riceve questi messaggi è quello in <strong>Impostazioni → Contatti → Numero
    WhatsApp</strong>, lo stesso usato da tutto il sito. Il messaggio è nella
    <strong>lingua in cui il cliente ha prenotato</strong> (italiano, inglese, francese o spagnolo).
</p>
<div class="guide-tip">
    Sono normali link WhatsApp: <strong>nessun costo per messaggio</strong> e nessun abbonamento.
    Il sistema non invia niente da solo — apre la chat con il testo già pronto, poi si scrive
    come sempre dal telefono o da WhatsApp Web.
</div>

<h2>Creare una prenotazione manuale</h2>
<p>
    Da <strong>Prenotazioni → Nuova prenotazione</strong> puoi registrare una prenotazione per telefono,
    walk-in o agenzia. Sono ammesse anche <strong>date passate</strong> per registrazioni retroattive.
</p>

<div class="guide-step"><span class="guide-step-num">1</span><div><strong>Seleziona il tour.</strong> Compaiono le opzioni e le date disponibili.</div></div>
<div class="guide-step"><span class="guide-step-num">2</span><div><strong>Scegli data e orario di partenza.</strong> Vengono mostrate solo le date prenotabili (stesse del sito), salvo l'uso esclusivo (vedi capitolo dedicato).</div></div>
<div class="guide-step"><span class="guide-step-num">3</span><div><strong>Indica quanti partecipanti.</strong> Adulti e bambini (per ogni bambino la data di nascita determina la riduzione). Il conteggio è in cima; i dati anagrafici e il documento di ciascuno si compilano più in basso, nella sezione <em>Dati passeggeri</em>.</div></div>
<div class="guide-step"><span class="guide-step-num">4</span><div><strong>(Opzionale) Scegli il catamarano.</strong> Lasciando "Automatico" il sistema assegna la barca con più posti liberi.</div></div>
<div class="guide-step"><span class="guide-step-num">5</span><div><strong>Compila i dati dell'intestatario</strong> (con il suo documento), un eventuale codice sconto e, se la prenotazione è per un'agenzia, associala in <em>Agenzia B2B</em> (vedi sotto).</div></div>
<div class="guide-step"><span class="guide-step-num">6</span><div><strong>Compila i Dati passeggeri.</strong> Nome, cognome e <strong>documento d'identità obbligatorio</strong> per ogni passeggero (vedi il capitolo Documenti d'identità).</div></div>
<div class="guide-step"><span class="guide-step-num">7</span><div><strong>Scegli lo stato</strong> della prenotazione e conferma.</div></div>

<h3 class="h6 fw-bold mt-4">Il metodo di pagamento decide cosa riceve il cliente</h3>
<ul>
    <li>
        <strong>Già incassato</strong> (contanti / POS) → l'incasso viene registrato subito. Se scegli
        lo stato "Confermata", al cliente arrivano i <strong>biglietti</strong>.
    </li>
    <li>
        <strong>Link di pagamento (Stripe)</strong> → la prenotazione resta "in attesa" e al cliente
        arriva l'<strong>email col link per pagare</strong>. Se preferisci mandarlo tu (WhatsApp,
        telefono), togli la spunta <em>"Invia subito l'email al cliente col link"</em>: il link resta
        salvato nel dettaglio, da copiare quando vuoi.
    </li>
    <li>
        <strong>Bonifico bancario</strong> → al cliente arrivano le <strong>coordinate</strong> e la
        prenotazione resta in attesa del tuo riscontro dell'incasso.
    </li>
</ul>

<h2>Associare la prenotazione a un'agenzia</h2>
<p>
    Se stai registrando una prenotazione <strong>per conto di un'agenzia</strong>, nella sezione
    <strong>Agenzia B2B</strong> selezionala dall'elenco: la prenotazione risulterà come fatta
    dall'agenzia e <strong>maturerà la sua commissione</strong>, esattamente come se l'avesse creata
    lei dal portale. Lasciando "Nessuna" resta una vendita diretta. Vedi il capitolo
    <em>Agenzie B2B e commissioni</em>.
</p>

<h2>Lo stato che scegli conta</h2>
<ul>
    <li><strong>In attesa</strong>: al cliente viene inviata l'email con il link di pagamento Stripe.</li>
    <li><strong>Confermata</strong>: pagamento già incassato fuori piattaforma; vengono inviati i biglietti.</li>
    <li>Altri stati (acconto, bonifico, completata…): nessuna email automatica.</li>
</ul>
<p>Trovi il dettaglio nel capitolo <em>Pagamenti e stati</em>.</p>

<h2>Tour "su richiesta"</h2>
<p>
    Per i tour senza listino (es. crociere private) inserisci a mano il <strong>prezzo totale</strong>
    della prenotazione: adulti e bambini servono solo a contare i posti. Questo accade automaticamente
    quando il tour è marcato come "su richiesta".
</p>

<div class="guide-warn">
    <strong>Date di nascita dei bambini:</strong> inseriscile da tastiera tranquillamente —
    il prezzo si aggiorna man mano, ma il campo non perde la digitazione.
</div>

<h2>Applicare uno sconto</h2>
<p>
    Nel <strong>riepilogo</strong> a destra, sotto il dettaglio dei prezzi, trovi il campo
    <strong>Sconto</strong>. Scrivi l'importo e scegli se è in <strong>€</strong> o in
    <strong>%</strong>: il totale si aggiorna mentre digiti, così vedi subito quanto pagherà
    davvero il cliente.
</p>
<ul>
    <li>Lo sconto si applica <strong>dopo</strong> gli eventuali posti omaggio, su quel che resta.
        Esempio: 450&euro; con un omaggio da 150&euro; &rarr; restano 300&euro;, e uno sconto del
        10% li porta a 270&euro;.</li>
    <li>Non può mai superare il totale: se scrivi 500&euro; su una prenotazione da 300&euro;, il
        totale si ferma a zero e un avviso te lo segnala (utile contro gli errori di battitura).</li>
    <li>Vale solo in <strong>creazione</strong>. Per cambiare il prezzo di una prenotazione già
        salvata usa la modifica del prezzo (vedi sopra), che gestisce anche l'incasso o lo storno
        della differenza.</li>
</ul>

<h2>Regalare un posto (omaggio)</h2>
<p>
    Sempre nel <strong>riepilogo</strong>, accanto allo sconto, il campo <strong>Omaggio</strong>
    indica quanti partecipanti non devono pagare. Serve quando vuoi invitare qualcuno: un ospite
    dello staff, un partner, un accompagnatore. Indicando almeno un posto compaiono il campo per
    il motivo e l'opzione sugli extra.
</p>
<ul>
    <li>I posti omaggio <strong>occupano il posto in barca</strong> come tutti gli altri: contano per la
        capienza e ricevono biglietto e QR per l'imbarco.</li>
    <li>L'omaggio si applica ai posti di <strong>maggior valore</strong>. Esempio: 1 adulto (150&euro;) +
        1 bambino (100&euro;) con 1 posto omaggio &rarr; resta da pagare il bambino, 100&euro;.</li>
    <li>Il riepilogo mostra subito la riga verde con l'importo omaggiato, così vedi il totale reale
        prima di salvare.</li>
    <li>Puoi scrivere un <strong>motivo</strong> (es. "ospite dello staff"): resta salvato sulla
        prenotazione insieme a chi ha concesso l'omaggio e quando.</li>
</ul>

<div class="guide-tip">
    L'interruttore <strong>"Omaggio anche sugli extra"</strong> decide se il posto regalato include
    anche pranzo e bevande. Se lo lasci spento gli extra restano a pagamento &mdash; scelta prudente,
    perché il fornitore va pagato comunque.
</div>

<div class="guide-warn">
    Non usare l'omaggio per applicare uno sconto commerciale: per quello ci sono i
    <strong>codici sconto</strong>. L'omaggio azzera il posto e resta tracciato come regalo,
    quindi nei report risulta ricavo zero su quel posto.
</div>
