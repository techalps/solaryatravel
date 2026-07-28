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

<h2>Cliente con account</h2>
<p>
    Se inserisci l'<strong>email di un cliente già registrato</strong>, la prenotazione viene
    collegata al suo account e <strong>compare nel suo storico</strong> ("Le mie prenotazioni").
    Se l'email non ha ancora un account, la prenotazione resta valida e — quando il cliente si
    registrerà con quella stessa email — comparirà automaticamente nel suo storico.
</p>

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

<h2>Regalare un posto (omaggio)</h2>
<p>
    Nel form di prenotazione, nel blocco verde <strong>Posti omaggio</strong>, indica quanti
    partecipanti non devono pagare. Serve quando vuoi invitare qualcuno: un ospite dello staff,
    un partner, un accompagnatore.
</p>
<ul>
    <li>I posti omaggio <strong>occupano il posto in barca</strong> come tutti gli altri: contano per la
        capienza e ricevono biglietto e QR per l'imbarco.</li>
    <li>L'omaggio si applica ai posti di <strong>maggior valore</strong>. Esempio: 1 adulto (150&euro;) +
        1 bambino (100&euro;) con 1 posto omaggio &rarr; resta da pagare il bambino, 100&euro;.</li>
    <li>Il riepilogo a destra mostra subito la riga verde con lo sconto, così vedi il totale reale
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
