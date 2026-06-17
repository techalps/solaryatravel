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

<h2>Metodi di pagamento</h2>
<ul>
    <li><strong>Carta (Stripe)</strong> — pagamento online con link inviato via email.</li>
    <li><strong>Bonifico</strong> — se attivo nelle impostazioni; al cliente vengono inviate le istruzioni.</li>
    <li><strong>Acconto</strong> — se attivo, il cliente versa una percentuale e salda in seguito.</li>
</ul>

<h2>Quando registri tu una prenotazione</h2>
<p>Lo stato che scegli al momento della creazione determina cosa succede:</p>
<ul>
    <li><strong>In attesa</strong> → email con link di pagamento al cliente.</li>
    <li><strong>Confermata</strong> → biglietti inviati subito (pagamento già incassato a parte).</li>
    <li>Altri stati → nessuna email automatica.</li>
</ul>

<div class="guide-tip">
    Le opzioni "acconto" e "bonifico" si attivano dalle <strong>Impostazioni</strong>: quando sono spente,
    i relativi stati e scelte non compaiono.
</div>
