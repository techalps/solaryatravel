<h2>Gli stati della prenotazione</h2>
<ul>
    <li><strong>In attesa</strong> — creata, in attesa di pagamento. Al cliente arriva il link Stripe.</li>
    <li><strong>Acconto versato</strong> — pagato l'acconto (se l'opzione acconto è attiva), resta il saldo.</li>
    <li><strong>Attesa bonifico</strong> — in attesa che arrivi il bonifico bancario.</li>
    <li><strong>Confermata</strong> — pagamento incassato, biglietti inviati.</li>
    <li><strong>Check-in</strong> — passeggeri imbarcati.</li>
    <li><strong>Completata</strong> — escursione effettuata.</li>
    <li><strong>Annullata / Rimborsata</strong> — non occupa più posti e non conta nei ricavi.</li>
</ul>
<div class="guide-tip">
    <strong>Imbarco:</strong> sono imbarcabili (compaiono nello scanner e nelle liste imbarco) le
    prenotazioni in stato <strong>Acconto versato, Attesa bonifico, Confermata, Check-in</strong> —
    non solo le confermate. Le prenotazioni "In attesa" (non pagate) non sono imbarcabili.
</div>

<h2>Metodi di pagamento</h2>
<ul>
    <li><strong>Carta (Stripe)</strong> — pagamento online con link inviato via email.</li>
    <li><strong>Bonifico</strong> — se attivo nelle impostazioni; al cliente vengono inviate le istruzioni.</li>
    <li><strong>Acconto</strong> — se attivo, il cliente versa una percentuale e salda in seguito.</li>
</ul>

<h2>Quando registri tu una prenotazione</h2>
<p>In creazione scegli il <strong>metodo di pagamento</strong>, che determina lo stato e cosa succede:</p>
<ul>
    <li><strong>Già incassato (contanti / POS / altro)</strong> → registra subito l'incasso e conferma la prenotazione (invia i biglietti). L'importo incassato risulta nella prenotazione, quindi in caso di annullamento il calcolo di penale/rimborso funziona.</li>
    <li><strong>Link di pagamento (Stripe)</strong> → la prenotazione resta "in attesa"; viene generato un <strong>link</strong> che trovi nel dettaglio, con il bottone <em>"Invia al cliente"</em> (puoi anche copiarlo e inviarlo come preferisci). Non parte alcuna email finché non premi il bottone.</li>
    <li><strong>Bonifico bancario</strong> (se attivo) → resta in "Attesa bonifico"; confermi tu l'incasso quando arriva.</li>
</ul>
<p>
    Se l'<strong>acconto</strong> è attivo, puoi scegliere la rata: "Intero importo" oppure
    "Acconto" (registra/richiede solo la prima rata, il saldo resta da incassare).
</p>
<div class="guide-tip">
    Lo <strong>Stato avanzato</strong> (sezione opzionale) serve solo per le registrazioni
    retroattive: forza manualmente lo stato (es. Completata, Check-in). Normalmente lo stato
    viene impostato in automatico dal metodo di pagamento.
</div>

<div class="guide-tip">
    Le opzioni "acconto" e "bonifico" si attivano dalle <strong>Impostazioni</strong>: quando sono spente,
    i relativi stati e scelte non compaiono.
</div>

<h2>Registrare l'incasso di un bonifico</h2>
<p>
    I bonifici arrivano in banca, non nel gestionale: finché non lo dici tu, il sistema non sa che
    il denaro è entrato. Nel <strong>dettaglio</strong> della prenotazione trovi il pulsante che
    chiude il ciclo:
</p>
<ul>
    <li><strong>"Conferma incasso bonifico"</strong> — sulle prenotazioni in <em>Attesa bonifico</em>,
        in alto accanto al titolo.</li>
    <li><strong>"Registra incasso"</strong> — nel riquadro <em>Da incassare</em>, quando resta un
        importo scoperto (saldo di un acconto, oppure differenza dopo un aumento di prezzo).</li>
</ul>
<p>
    In entrambi i casi viene registrato l'<strong>importo che manca davvero</strong>, non una cifra
    fissa, e lo stato avanza da solo: resta <em>Acconto versato</em> se rimane un residuo, passa a
    <em>Confermata</em> quando la prenotazione è saldata.
</p>
<div class="guide-tip">
    <strong>Se premi due volte non succede nulla di male.</strong> Quando non c'è più niente da
    incassare il sistema <strong>rifiuta</strong> l'operazione con un messaggio, invece di registrare
    un secondo pagamento. Prima non era così: un doppio clic creava un incasso doppio e la
    prenotazione risultava pagata più del dovuto.
</div>
<div class="guide-tip">
    <strong>Registra sempre gli incassi, anche quelli in contanti.</strong> Una prenotazione pagata
    ma senza incasso registrato risulta a sistema come <em>non pagata</em>: finisce fra i crediti da
    riscuotere e sparisce dall'incassato dei report. Se te ne accorgi dopo, registra comunque il
    pagamento: il dato si sistema.
</div>

<h2>Saldo (prenotazioni con acconto)</h2>
<p>
    Quando crei una prenotazione con <strong>acconto</strong>, in creazione vedi l'importo dell'acconto,
    il <strong>saldo da incassare</strong> e la <strong>data di scadenza</strong> del saldo (proposta in
    automatico dalle impostazioni, ma modificabile). La scadenza è modificabile anche dalla pagina di
    <strong>Modifica</strong>.
</p>
<p>
    Nel <strong>dettaglio</strong> della prenotazione, finché resta del denaro da incassare, compare il
    riquadro <em>"Da incassare"</em> con totale, quanto è già stato versato e quanto manca. Da lì puoi
    <strong>"Inviare la richiesta di saldo"</strong> (il cliente riceve un'email con il link per saldare
    o le istruzioni per il bonifico) oppure <strong>"Registrare l'incasso"</strong> quando i soldi
    sono arrivati.
</p>
<p>
    Il riquadro non riguarda solo gli acconti: compare ogni volta che il versato è inferiore al totale,
    quindi anche dopo un <strong>aumento di prezzo</strong> su una prenotazione già pagata.
</p>
<p>
    Inoltre il sistema invia <strong>automaticamente</strong> un promemoria di saldo al cliente quando la
    scadenza si avvicina (entro 24 ore), una sola volta.
</p>
