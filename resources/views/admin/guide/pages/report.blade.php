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
<h2>I tre criteri (leggi prima questo)</h2>
<p>
    La stessa prenotazione può cadere in <strong>mesi diversi</strong> a seconda della domanda che ti stai facendo.
    Per questo i report mostrano <strong>tre colonne separate</strong>, ognuna con la propria base di calcolo scritta
    sotto al titolo. <strong>Non vanno mai sommate fra loro.</strong>
</p>
<ul>
    <li><strong>Venduto · per data di prenotazione</strong> — quanto è stato <em>venduto</em> nel periodo, a prescindere da quando si parte. Risponde a: «come sta andando la raccolta ordini?»</li>
    <li><strong>Partenze · per data escursione</strong> — quanto valgono le escursioni che <em>partono</em> nel periodo, a prescindere da quando sono state prenotate. Risponde a: «quanto vale il mese operativo?»</li>
    <li><strong>Incassato · per data di incasso</strong> — quanti soldi sono <em>entrati davvero</em> nel periodo, rata per rata. Risponde a: «quanto ho incassato questo mese?»</li>
</ul>
<p class="text-muted">
    Esempio concreto: prenotazione da €1.000 fatta il <strong>10 luglio</strong> per un'uscita del <strong>20 agosto</strong>,
    con acconto di €300 a luglio e saldo di €700 ad agosto. Nei report di <strong>luglio</strong> vedrai €1.000 nel venduto
    (per data prenotazione) e €300 nell'incassato. Nei report di <strong>agosto</strong> vedrai €1.000 nelle partenze e
    €700 nell'incassato. Se il cliente anticipa il saldo a luglio, l'intero €1.000 risulta incassato a luglio.
</p>

<h2>Venduto vs Incassato</h2>
<p>
    Il <strong>venduto</strong> è il <strong>totale della prenotazione</strong>, non quanto è stato effettivamente
    pagato. Una prenotazione con solo acconto versato (o bonifico ancora in attesa) conta già per l'<strong>intero
    importo</strong>.
</p>
<p>
    L'<strong>incassato</strong> è quanto è davvero entrato in cassa, <strong>alla data in cui è entrato</strong>.
    La differenza sulle partenze del periodo è quello che resta <strong>da incassare</strong>.
</p>

<h2>«Da incassare»: due situazioni diverse</h2>
<p>
    Questa voce è <strong>scomposta in due</strong>, perché sommarle nascondeva il problema vero:
</p>
<ul>
    <li><strong>Saldi aperti</strong> — l'acconto è stato incassato, il saldo no. È un <em>credito reale</em> verso il cliente.</li>
    <li><strong>Nessun pagamento registrato</strong> — la prenotazione non ha alcun incasso a sistema. Spesso significa che il denaro è stato incassato in banchina e <strong>non è mai stato registrato</strong>: non è un credito, è un dato mancante da sanare.</li>
</ul>
<p>
    Sotto trovi l'elenco delle prenotazioni con <strong>partenza già effettuata</strong> e residuo aperto: sono quelle
    da controllare per prime. Attenzione: il «da incassare» si riferisce a <strong>tutte le partenze del periodo</strong>,
    non solo a oggi — quindi non si azzera a fine mese.
</p>
<p class="text-muted">
    Nota: se una prenotazione risulta incassata ma <em>senza</em> un pagamento registrato, quell'importo non compare
    nella colonna «Incassato», perché privo di data. Un avviso in fondo alla sezione te lo segnala.
</p>

<hr>

<h2>Scheda «Overview»</h2>
<p>La panoramica del periodo. In alto le <strong>tre colonne</strong> dei criteri (vedi sopra), ciascuna col proprio confronto rispetto al <strong>periodo precedente pieno</strong> (mese su mese, anno su anno).</p>
<p>Sotto:</p>
<ul>
    <li><strong>Andamento giornaliero</strong> — le tre curve a confronto giorno per giorno: venduto per data prenotazione, valore delle partenze, incassi reali. Servono a vedere gli scostamenti, non vanno sommate.</li>
    <li><strong>Da incassare</strong> — la scomposizione descritta sopra, con l'elenco delle partenze già effettuate ancora scoperte.</li>
    <li><strong>Top tour</strong> — i 5 tour con più prenotazioni nel periodo.</li>
    <li><strong>Per stato</strong> — torta con la ripartizione delle prenotazioni per stato.</li>
</ul>

<h2>Scheda «Ricavi»</h2>
<p>Anche qui in alto trovi le <strong>tre colonne</strong> dei criteri, seguite dalla scomposizione del «da incassare». Poi:</p>
<ul>
    <li><strong>Canale di vendita</strong> — diretto vs agenzie, con le provvigioni scorporate e il netto che resta a Solarya (per data escursione).</li>
    <li><strong>Rimborsi</strong> — soldi <strong>realmente rimborsati</strong>, alla data del rimborso. Nella colonna «Incassato» sono già scalati dal netto.</li>
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

<h2>Esportazioni</h2>
<p>Nel menu a sinistra, sotto i report, ci sono due pulsanti.</p>
<p>
    <strong>Esporta tutto (Excel)</strong> — scarica un unico file <strong>.xlsx</strong> del periodo scelto,
    con un foglio per ogni report:
</p>
<ul>
    <li><strong>Riepilogo</strong> — i numeri chiave divisi nei <strong>tre blocchi</strong> (Venduto per data prenotazione, Partenze per data escursione, Incassato per data di incasso), più il «da incassare» scomposto in saldi aperti e incassi non registrati.</li>
    <li><strong>Ricavi giornalieri</strong> — venduto e incassato giorno per giorno.</li>
    <li><strong>Ricavi per tour</strong> — venduto e incassato per ogni tour.</li>
    <li><strong>Prenotazioni</strong> — elenco completo: numero, date, tour, cliente, email, posti, stato, tipo pagamento, venduto, incassato, saldo residuo.</li>
    <li><strong>Occupazione</strong> — riempimento per tour (capacità, partenze, passeggeri, %).</li>
</ul>
<p>
    <strong>Esporta CSV (questa scheda)</strong> — il vecchio export rapido in CSV della sola scheda aperta,
    utile per importazioni veloci.
</p>
