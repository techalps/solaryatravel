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

<h2>Creare una prenotazione manuale</h2>
<p>
    Da <strong>Prenotazioni → Nuova prenotazione</strong> puoi registrare una prenotazione per telefono,
    walk-in o agenzia. Sono ammesse anche <strong>date passate</strong> per registrazioni retroattive.
</p>

<div class="guide-step"><span class="guide-step-num">1</span><div><strong>Seleziona il tour.</strong> Compaiono le opzioni e le date disponibili.</div></div>
<div class="guide-step"><span class="guide-step-num">2</span><div><strong>Scegli data e orario di partenza.</strong> Vengono mostrate solo le date prenotabili (stesse del sito), salvo l'uso esclusivo (vedi capitolo dedicato).</div></div>
<div class="guide-step"><span class="guide-step-num">3</span><div><strong>Aggiungi i partecipanti.</strong> Adulti e bambini: per ogni bambino inserisci la data di nascita, così il sistema applica la riduzione corretta in base all'età.</div></div>
<div class="guide-step"><span class="guide-step-num">4</span><div><strong>(Opzionale) Scegli il catamarano.</strong> Lasciando "Automatico" il sistema assegna la barca con più posti liberi.</div></div>
<div class="guide-step"><span class="guide-step-num">5</span><div><strong>Compila i dati del cliente</strong> e, se serve, un codice sconto.</div></div>
<div class="guide-step"><span class="guide-step-num">6</span><div><strong>Scegli lo stato</strong> della prenotazione e conferma.</div></div>

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
