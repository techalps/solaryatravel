<?php

/*
|--------------------------------------------------------------------------
| Percorso post-prenotazione (pagamento, conferma, biglietti, area cliente)
|--------------------------------------------------------------------------
|
| Queste pagine erano cablate in italiano: un cliente straniero navigava e
| prenotava nella sua lingua, poi dal pagamento in avanti trovava tutto in
| italiano. Sono le pagine che vede DOPO aver dato i soldi, quindi le più
| delicate da lasciare non tradotte.
|
| Riguarda: /pagamento/*, la conferma, i biglietti, "Le mie prenotazioni",
| il dettaglio prenotazione e le istruzioni per il bonifico.
|
*/

return [

    /* ---------- Voci ricorrenti in più pagine ---------- */
    'common' => [
        'booking' => 'Prenotazione',
        'home' => 'Home',
        'your_tour' => 'Il tuo tour',
        'summary' => 'Riepilogo',
        'order_summary' => 'Riepilogo ordine',
        'amounts_summary' => 'Riepilogo importi',
        'subtotal' => 'Subtotale',
        'extras' => 'Extra',
        'discount' => 'Sconto',
        'vat' => 'IVA',
        'vat_included' => 'IVA inclusa',
        'total' => 'Totale',
        'total_colon' => 'Totale:',
        'status' => 'Stato',
        'duration' => 'Durata',
        'date' => 'Data',
        'method' => 'Metodo',
        'payment' => 'Pagamento',
        'lead_booker' => 'Prenotante',
        'name' => 'Nome',
        'email' => 'Email',
        'phone' => 'Telefono',
        'special_requests' => 'Richieste speciali',
        'participants' => 'Partecipanti',
        'person' => ':count persona|:count persone',
        'participant' => ':count partecipante|:count partecipanti',
        'seat' => ':count posto|:count posti',
        'hours' => ':count ore',
        'hours_short' => ':count h',
        'adult' => 'Adulto',
        'participants_count' => 'Partecipanti',
        'deadline' => 'Scadenza',
        'actions' => 'Azioni',
        'cancelled_item' => 'Disdetto',
        'boarded' => 'Imbarcato',
    ],

    'status_labels' => [
        'pending' => 'In attesa di pagamento',
        'deposit_paid' => 'Acconto versato',
        'awaiting_transfer' => 'In attesa di bonifico',
        'confirmed' => 'Confermata',
        'checked_in' => 'Check-in effettuato',
        'completed' => 'Completata',
        'cancelled' => 'Annullata',
        'refunded' => 'Rimborsata',
        'no_show' => 'No show',
    ],

    /* ---------- /pagamento/{uuid} ---------- */
    'pay' => [
        'title' => 'Conferma e paga',
        'awaiting' => 'in attesa di pagamento',
        'lead_booker_title' => 'Intestatario prenotazione',
        'extras_included' => 'Extra inclusi:',
        'seats_held_for' => 'Posti riservati per',
        'secure_payment' => 'Pagamento sicuro',
        'stripe_note' => 'Pagamento gestito da Stripe — i tuoi dati non transitano sul nostro server.',
        'instant_confirm' => 'Conferma immediata e ricevuta inviata via email.',
        'tickets_after' => 'Biglietti con QR code consegnati subito dopo il pagamento.',
    ],

    /* ---------- Esito del pagamento ---------- */
    'success' => [
        'title' => 'Prenotazione confermata!',
        'intro' => ':name, abbiamo ricevuto il tuo pagamento.',
        'deposit_title' => 'Acconto ricevuto',
        'deposit_intro' => 'Grazie! Abbiamo registrato l\'acconto. Il tuo posto è confermato.',
        'deposit_tickets_later' => 'Riceverai i biglietti via email una volta completato il saldo.',
        'by_date' => 'entro il :date',
        'pay_balance' => 'Paga il saldo',
        'sent_to' => 'Abbiamo inviato i biglietti e la ricevuta a',
        'all_tickets' => 'Biglietti di tutti i partecipanti',
        'total_paid' => 'Totale pagato',
        'back_home' => 'Torna alla home',
        'explore_tours' => 'Esplora altri tour',
    ],

    'cancel' => [
        'title' => 'Pagamento annullato',
        'intro' => 'La tua prenotazione è ancora in attesa — puoi riprovare quando vuoi.',
        'no_charge' => 'Nessun importo è stato addebitato. La prenotazione resta valida fino alla scadenza.',
        'total_due' => 'Totale da pagare:',
    ],

    /* ---------- Saldo ---------- */
    'balance' => [
        'title' => 'Pagamento del saldo',
        'booking_total' => 'Totale prenotazione',
        'deposit_paid' => 'Acconto versato',
        'balance' => 'Saldo',
        'balance_due' => 'Saldo da pagare',
        'secure_stripe' => 'Pagamento sicuro tramite Stripe',
        'complete_payment' => 'Completa il pagamento',
    ],

    /* ---------- Bonifico ---------- */
    'transfer' => [
        'registered' => 'Prenotazione registrata',
        'title' => 'Completa il pagamento con bonifico istantaneo',
        'intro' => 'La prenotazione :number è in attesa di pagamento e i posti sono riservati. Una volta ricevuto il bonifico, la confermeremo e riceverai i biglietti via email.',
        'amount' => 'Importo da versare',
        'deposit_then_balance' => 'Acconto · saldo successivo',
        'bank_details' => 'Coordinate bancarie',
        'reference_hint' => 'Indica come :reference il numero di prenotazione:',
        'reference_word' => 'causale',
        'go_to_booking' => 'Vai alla mia prenotazione',
        'transfer' => 'Bonifico',
    ],

    /* ---------- Le mie prenotazioni ---------- */
    'my_bookings' => [
        'title' => 'Le mie prenotazioni',
        'subtitle' => 'Storico, prossime partenze e biglietti dei tuoi tour.',
        'empty_title' => 'Nessuna prenotazione ancora',
        'empty_text' => 'Scopri i nostri tour in catamarano lungo la Costiera. La prossima esperienza inizia qui.',
        'today' => 'Oggi',
        'upcoming' => 'In arrivo',
        'user_fallback' => 'Utente',
    ],

    /* ---------- Dettaglio prenotazione del cliente ---------- */
    'detail' => [
        'booked_extras' => 'Extra prenotati',
        'passenger_todo' => '— Dati da compilare —',
        'extra_fallback' => 'Extra',
        'tour_fallback' => 'Tour',
    ],

    /* ---------- Biglietti stampabili ---------- */
    'tickets' => [
        'title' => 'I tuoi biglietti',
        'one_per_passenger' => 'Ogni passeggero ha il proprio biglietto.',
        'passenger' => 'Passeggero',
        'adult' => 'Adulto',
        'seat' => 'Posto',
        'catamaran' => 'Catamarano',
        'departure' => 'Partenza',
        'boarding_point' => 'Punto d\'imbarco',
        'boarded' => 'IMBARCATO',
        'tour' => 'Tour',
    ],

];
