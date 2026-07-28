<h2>Come vengono assegnati i posti</h2>
<p>
    Ogni partecipante che occupa un posto viene assegnato a un catamarano. In una prenotazione normale
    il sistema sceglie <strong>automaticamente</strong> la barca, cercando di tenere il gruppo unito su
    un'unica imbarcazione quando possibile.
</p>

<h2>Capienza e posti disponibili</h2>
<ul>
    <li>I <strong>posti disponibili</strong> mostrati sono quelli reali: la capienza dei catamarani meno le prenotazioni già presenti.</li>
    <li>Il conteggio è per <strong>barca e fascia oraria</strong>, non per tour: una barca è fisica, quindi
        se è già piena per un tour non ha posti liberi per un altro tour nello stesso orario. Il sistema
        guarda tutte le prenotazioni della barca, su qualsiasi tour.</li>
    <li>Gli <strong>orari che non si sovrappongono</strong> restano compatibili: la stessa barca può fare
        il Daily Escape la mattina e il Sunset Escape la sera dello stesso giorno.</li>
    <li>I catamarani <strong>bloccati/riservati</strong> in quella data non contano nella disponibilità.</li>
    <li>Se in una data non c'è nessun catamarano disponibile, la prenotazione non è possibile e il sistema lo segnala.</li>
</ul>

<div class="guide-tip">
    Se una barca risulta occupata e non capisci da cosa, controlla le prenotazioni di
    <strong>tutti i tour</strong> in quella data e negli orari sovrapposti, non solo del tour che stai
    prenotando: la flotta è condivisa.
</div>

<h2>Barca bloccata senza una prenotazione</h2>
<p>
    Quando una prenotazione a uso esclusivo viene <strong>annullata o rimborsata</strong>, la riserva sul
    catamarano viene rilasciata automaticamente e la barca torna vendibile.
</p>
<p>
    Su prenotazioni annullate <em>prima</em> di questo comportamento può essere rimasta una riserva
    "appesa": la barca risulta occupata anche se la prenotazione non esiste più. Chi cura il sito può
    elencarle e ripulirle:
</p>
<div class="guide-tip">
    <code>php artisan blocks:clean-orphans</code> — elenca senza modificare nulla<br>
    <code>php artisan blocks:clean-orphans --fix</code> — rilascia le riserve appese
</div>
<p>
    Le riserve create <strong>a mano</strong> dall'operatore (senza un numero di prenotazione collegato)
    non vengono toccate: restano valide.
</p>

<h2>Gruppo diviso su più catamarani</h2>
<p>
    Se un gruppo non entra in un'unica barca ma c'è spazio sommando più catamarani, la prenotazione
    viene divisa. Sul <strong>sito</strong>, al momento di prenotare, il cliente riceve un avviso
    e può scegliere se proseguire, cambiare data o contattare il customer care.
</p>

<div class="guide-tip">
    Lo stesso catamarano può essere usato da tour diversi: la disponibilità tiene conto di tutte le
    prenotazioni e di tutti i blocchi su quella barca, indipendentemente dal tour.
</div>

<h2>Spostare un passeggero su un altro catamarano</h2>
<p>
    Dal <strong>dettaglio della prenotazione</strong>, nella tabella <em>Partecipanti</em>, ogni
    passeggero attivo ha nella colonna <strong>Catamarano</strong> un menu <strong>"Sposta su…"</strong>.
</p>
<div class="guide-step"><span class="guide-step-num">1</span><div>Apri il dettaglio della prenotazione (elenco prenotazioni → clic sul numero).</div></div>
<div class="guide-step"><span class="guide-step-num">2</span><div>Nella riga del passeggero, apri il menu <strong>"Sposta su…"</strong> nella colonna Catamarano.</div></div>
<div class="guide-step"><span class="guide-step-num">3</span><div>Il menu elenca <strong>solo i catamarani con posti liberi</strong> per quella partenza, con il numero di posti disponibili. Seleziona quello desiderato: lo spostamento è immediato.</div></div>
<div class="guide-warn">
    Non puoi spostare un passeggero su un catamarano <strong>pieno</strong> o <strong>riservato in uso
    esclusivo</strong> da un'altra prenotazione nella stessa fascia oraria: quelle barche non compaiono
    tra le destinazioni e, in ogni caso, il sistema rifiuta lo spostamento.
</div>
<p class="text-muted small">
    Per spostare un'<strong>intera riserva</strong> a uso esclusivo (posti + blocco) su un'altra barca
    completamente libera, usa invece il pulsante nella sezione "Catamarani riservati" (vedi Riservare un catamarano).
</p>

<h2>Sistemare un eventuale overbooking</h2>
<p>
    Se per errore su una data risultano più posti prenotati della capienza (o prenotazioni su una barca
    poi riservata), usa <strong>"Sposta su…"</strong> per ricollocare i passeggeri su un catamarano con
    posti liberi nella stessa data. Se non c'è spazio su nessuna barca, valuta lo spostamento di data o
    l'annullamento con il cliente.
</p>
