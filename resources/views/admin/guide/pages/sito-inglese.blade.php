<h2>Il sito è bilingue: italiano e inglese</h2>
<p>
    Il sito pubblico è disponibile in due lingue. L'italiano resta sugli indirizzi storici,
    l'inglese vive sotto il prefisso <code>/en</code>:
</p>
<ul>
    <li><code>solaryatravel.com/</code> → home in italiano</li>
    <li><code>solaryatravel.com/en</code> → home in inglese</li>
    <li><code>solaryatravel.com/tour/solarya-daily-escape</code> → tour in italiano</li>
    <li><code>solaryatravel.com/en/tour/solarya-daily-escape</code> → stesso tour in inglese</li>
</ul>
<p>
    L'indirizzo del tour (lo <em>slug</em>) è identico nelle due lingue: i link già in circolazione
    continuano a funzionare. Il visitatore cambia lingua con il selettore <strong>IT / EN</strong>
    nell'header e resta sulla stessa pagina; la scelta viene ricordata per tutta la navigazione.
    Chi arriva sulla home con un browser in inglese viene portato automaticamente sulla versione EN.
</p>

<div class="guide-warn">
    <strong>Il pannello di amministrazione resta solo in italiano</strong>, così come le email di
    conferma, i biglietti PDF e il portale agenzie. Questo intervento riguarda solo il sito pubblico.
</div>

<h2>Cosa succede quando modifichi i testi di un tour</h2>
<p>
    I testi dei tour (titolo, sottotitolo, descrizione, itinerario, voci incluso/escluso) li scrivi
    <strong>solo in italiano</strong> da <em>Tour → Modifica</em>, come hai sempre fatto. La versione
    inglese di quei testi è tenuta in un dizionario interno: a ogni testo italiano corrisponde la sua
    traduzione inglese.
</p>
<p>
    La conseguenza pratica è questa: se cambi un testo italiano, il dizionario non riconosce più la
    frase nuova e <strong>su quella singola frase il sito inglese mostra l'italiano</strong>. Nulla si
    rompe e nessuna pagina va in errore — semplicemente quel pezzo di testo resta in italiano finché
    non viene aggiunta la traduzione corrispondente.
</p>

<div class="guide-warn">
    Quindi: <strong>dopo aver modificato o aggiunto testi di un tour, segnalalo a chi cura il sito</strong>
    così può aggiungere la traduzione inglese. Vale anche quando crei un tour nuovo.
</div>

<h2>Cosa non va tradotto (e resta uguale)</h2>
<ul>
    <li><strong>Nomi dei tour</strong>: Solarya Daily Escape, Solarya Sunset Escape, Solarya Private Cruise.</li>
    <li><strong>Nomi di luoghi</strong>: Piscina di Molara, Cala Girgolu, Cala Brandinchi, Capo Coda Cavallo,
        La Cinta, Puntaldia, Tavolara, San Teodoro, Cala Dei Sardi.</li>
    <li><strong>Prodotti tipici</strong>: pane Guttiau, taralli, stracciatella, Prosecco.</li>
</ul>
<p>
    Anche i <strong>nomi dei periodi tariffari</strong> non richiedono nulla da parte tua: se il periodo
    si chiama <em>Giugno</em>, sul sito inglese compare automaticamente <em>June</em>, con le date nel
    formato inglese (<em>1 – 30 June 2026</em> invece di <em>01/06/2026 – 30/06/2026</em>).
</p>

<h2>Come verificare la copertura delle traduzioni</h2>
<p>
    Chi cura il sito ha un comando che elenca i testi ancora senza traduzione inglese, così si vede a
    colpo d'occhio cosa manca dopo le tue modifiche:
</p>
<div class="guide-tip">
    <code>php artisan i18n:missing --locale=en</code>
</div>
<p>
    Se non segnala nulla, tutti i contenuti dei tour sono coperti anche in inglese.
</p>

<h2>Prezzi e prenotazioni</h2>
<p>
    Prezzi, tariffe, disponibilità e regole di prenotazione sono gli stessi nelle due lingue: c'è un
    solo listino e un solo calendario. Un cliente che prenota dal sito inglese arriva nella stessa
    lista prenotazioni di sempre, e riceve le comunicazioni in italiano.
</p>
