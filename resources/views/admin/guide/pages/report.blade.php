<h2>Dove sono le statistiche</h2>
<p>
    In <strong>Report &amp; Statistiche</strong> ci sono quattro schede (menu a sinistra):
    <strong>Overview</strong>, <strong>Ricavi</strong>, <strong>Prenotazioni</strong> e <strong>Occupazione</strong>.
    In alto a sinistra scegli il <strong>periodo</strong> (Oggi, Settimana, Mese, Trimestre, Anno, Tutto):
    cambia tutti i numeri della pagina. Ogni scheda ha il pulsante <strong>Esporta CSV</strong>.
</p>

<div class="guide-tip">
    <strong>Due date diverse, non confonderle.</strong> I numeri di <em>ricavo</em> e <em>occupazione</em> sono
    attribuiti alla <strong>data dell'escursione</strong> (la partenza). I <em>conteggi di prenotazioni e passeggeri</em>
    in Overview e Prenotazioni sono invece per <strong>data in cui è stata creata la prenotazione</strong>.
    Quindi una prenotazione fatta oggi per agosto conta tra le "prenotazioni di oggi", ma il suo ricavo compare ad agosto.
</div>

<h2>Cosa rientra nei ricavi</h2>
<p>I ricavi <strong>non</strong> sono solo gli incassi Stripe: contano <strong>tutte le prenotazioni valide</strong>,
   su qualsiasi canale (Stripe, bonifico, contanti, registrazioni manuali e retroattive). Rientrano le prenotazioni in stato:</p>
<ul>
    <li><strong>Acconto versato</strong></li>
    <li><strong>Attesa bonifico</strong></li>
    <li><strong>Confermata</strong></li>
    <li><strong>Check-in</strong></li>
    <li><strong>Completata</strong></li>
</ul>
<p>Non rientrano: <strong>In attesa</strong> (non ancora pagata), <strong>Annullata</strong>, <strong>Rimborsata</strong>, <strong>No show</strong>.</p>
<h2>Venduto vs Incassato (importante)</h2>
<p>
    Il dato principale dei ricavi è il <strong>venduto</strong>: il <strong>totale della prenotazione</strong>,
    non quanto è stato effettivamente incassato. Una prenotazione con solo acconto versato (o bonifico ancora in
    attesa) conta già per l'<strong>intero importo</strong>.
</p>
<p>
    Accanto trovi sempre l'<strong>incassato</strong>: quanto è davvero entrato in cassa finora (acconti + saldi
    realmente versati). La differenza è quello che resta <strong>da incassare</strong> (i saldi non ancora pagati).
</p>
<p class="text-muted">
    Esempio: prenotazione da €200 con acconto del 50% versato → <strong>Venduto €200</strong>,
    <strong>Incassato €100</strong>, <strong>da incassare €100</strong>. Quando il cliente salda, l'incassato sale a €200.
</p>

<hr>

<h2>Scheda «Overview»</h2>
<p>La panoramica del periodo. Le quattro card in alto:</p>
<ul>
    <li><strong>Ricavi (venduto)</strong> — totale venduto del periodo (per data escursione), con sotto quanto è stato <em>incassato</em> finora. La freccetta confronta il venduto col periodo precedente di pari durata.</li>
    <li><strong>Prenotazioni</strong> — numero di prenotazioni <em>create</em> nel periodo, di qualsiasi stato; sotto, quante sono confermate.</li>
    <li><strong>Passeggeri</strong> — somma dei posti venduti (campo "posti") delle prenotazioni create nel periodo.</li>
    <li><strong>Valore medio</strong> — ricavo medio per prenotazione (totale ÷ numero prenotazioni).</li>
</ul>
<p>Sotto:</p>
<ul>
    <li><strong>Andamento ricavi</strong> — grafico dei ricavi giorno per giorno (per data escursione).</li>
    <li><strong>Top tour</strong> — i 5 tour con più prenotazioni nel periodo.</li>
    <li><strong>Per stato</strong> — torta con la ripartizione delle prenotazioni per stato.</li>
</ul>

<h2>Scheda «Ricavi»</h2>
<p>Tutto per <strong>data escursione</strong>. Card in alto:</p>
<ul>
    <li><strong>Venduto</strong> — somma dei totali delle prenotazioni valide del periodo; sotto, quanto è già stato <em>incassato</em> e quanto resta <em>da incassare</em> (vedi sopra).</li>
    <li><strong>Prenotazioni</strong> — quante prenotazioni hanno fatto ricavo nel periodo (confermate/incassate).</li>
    <li><strong>Valore medio</strong> — ricavo medio per prenotazione.</li>
    <li><strong>Rimborsi</strong> — soldi <strong>realmente rimborsati</strong> nel periodo (pagamenti rimborsati su Stripe). È un dato di cassa reale, basato sulla data del rimborso, non sottratto automaticamente dal totale ricavi.</li>
</ul>
<ul>
    <li><strong>Ricavi mensili</strong> — barre con i ricavi mese per mese dell'anno in corso (indipendente dal periodo scelto).</li>
    <li><strong>Ricavi per tour</strong> — quanto ha incassato ogni tour nel periodo.</li>
    <li><strong>Per gateway</strong> — ripartizione per <em>canale di incasso reale</em> (Stripe, bonifico, ecc.), basata sui pagamenti effettivi. Le prenotazioni registrate a mano senza un pagamento tracciato finiscono nella voce <strong>«manuale»</strong>. Attenzione: questa sezione segue la data del pagamento, quindi i totali possono non coincidere al centesimo con "Totale ricavi" (che segue la data escursione).</li>
    <li><strong>Dettaglio giornaliero</strong> — tabella giorno per giorno: numero prenotazioni, totale e media.</li>
</ul>

<h2>Scheda «Prenotazioni»</h2>
<p>Conteggi per <strong>data di creazione</strong> (tranne "Per tour" e "Fasce orarie", che seguono la data escursione). Card:</p>
<ul>
    <li><strong>Totale</strong> — prenotazioni create nel periodo, ogni stato.</li>
    <li><strong>Confermate</strong> — quante confermate; sotto, quante completate.</li>
    <li><strong>Passeggeri</strong> — posti totali; sotto, media passeggeri per prenotazione.</li>
    <li><strong>Tasso cancellazione</strong> — % di prenotazioni annullate sul totale del periodo (diventa rossa sopra il 10%).</li>
</ul>
<ul>
    <li><strong>Fasce orarie più richieste</strong> — gli orari di partenza con più prenotazioni.</li>
    <li><strong>Per stato</strong> — torta della ripartizione per stato.</li>
    <li><strong>Per tour</strong> — schede per tour: prenotazioni, passeggeri, media a bordo.</li>
    <li><strong>Dettaglio giornaliero</strong> — tabella giorno per giorno: prenotazioni, passeggeri, media.</li>
</ul>

<h2>Scheda «Occupazione»</h2>
<p>Quanto si riempiono i catamarani. Considera solo prenotazioni <strong>confermate</strong> e <strong>completate</strong>, per data escursione. Card:</p>
<ul>
    <li><strong>Trasportati</strong> — passeggeri totali portati a bordo nel periodo.</li>
    <li><strong>Capacità massima</strong> — posti disponibili totali nel periodo (capienza del tour × partenze effettivamente usate).</li>
    <li><strong>Occupazione media</strong> — riempimento medio di tutti i tour (verde ≥ 70%, giallo ≥ 50%, rosso sotto).</li>
    <li><strong>Giorno più affollato</strong> — il giorno della settimana con più prenotazioni in media.</li>
</ul>
<ul>
    <li><strong>Occupazione per tour</strong> — per ogni tour: % di riempimento, prenotazioni, passeggeri, media a bordo. La % = passeggeri ÷ capacità massima.</li>
    <li><strong>Andamento giornaliero</strong> — passeggeri a bordo giorno per giorno.</li>
    <li><strong>Per giorno settimana</strong> — quali giorni della settimana vendono di più.</li>
    <li><strong>Popolarità fasce orarie</strong> — gli orari con più imbarchi (prenotazioni e passeggeri).</li>
</ul>

<div class="guide-tip">
    <strong>Perché un tour "uso esclusivo" può sballare l'occupazione.</strong> La capacità massima conta solo le partenze
    realmente prenotate; un noleggio dell'intero catamarano con pochi passeggeri risulta a bassa occupazione anche se la barca
    è interamente impegnata.
</div>

<h2>Esporta CSV</h2>
<p>Ogni scheda esporta i dati grezzi del periodo, da aprire in Excel:</p>
<ul>
    <li>Da Overview/Prenotazioni → elenco prenotazioni (numero, date, tour, cliente, posti, totale, stato).</li>
    <li>Da Ricavi → elenco pagamenti reali (data, prenotazione, gateway, importo, riferimento Stripe).</li>
    <li>Da Occupazione → elenco passeggeri per escursione (data, orario, tour, cliente, posti).</li>
</ul>
