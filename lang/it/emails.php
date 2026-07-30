<?php

/*
|--------------------------------------------------------------------------
| Email trasazionali al CLIENTE
|--------------------------------------------------------------------------
|
| L'email parte nella lingua scelta al momento della prenotazione, salvata su
| `bookings.locale` e applicata dal trait App\Mail\Concerns\SendsInBookingLocale.
|
| Le email agli ADMIN restano solo in italiano (le legge lo staff) e non usano
| questo file.
|
| Nei testi con :placeholder i nomi vanno mantenuti identici in ogni lingua.
|
*/

return [

    /* ---------- Elementi comuni a tutte le email ---------- */

    'common' => [
        'greeting' => 'Ciao :name,',
        'booking' => 'Prenotazione',
        'tour' => 'Tour',
        'date_departure' => 'Data e partenza',
        'date' => 'Data',
        'participants' => 'Partecipanti',
        'passengers' => 'Passeggeri',
        'total' => 'Totale',
        'questions' => 'Per qualsiasi domanda rispondi pure a questa email.',
        'rights' => 'Tutti i diritti riservati',
        'footer_help' => 'Hai domande? Rispondi a questa email o contatta lo staff.',
    ],

    /* ---------- Link di pagamento ---------- */

    'payment_link' => [
        'subject' => 'Completa il pagamento per la tua prenotazione #:number',
        'title' => 'Conferma e paga la tua prenotazione',
        'intro' => 'grazie per aver prenotato con noi! Per finalizzare la tua escursione su :tour, completa il pagamento online in modo sicuro tramite Stripe.',
        'total_due' => 'Totale da pagare',
        'cta' => 'Paga ora con carta',
        'fallback' => 'Se il pulsante non funziona, copia e incolla questo link nel browser:',
        'deadline' => 'Completa il pagamento entro il :date, altrimenti la prenotazione scade e i posti tornano disponibili.',
        'after_payment' => 'Una volta completato il pagamento, riceverai una seconda email con i tuoi biglietti e i QR code da mostrare al momento dell\'imbarco (uno per ogni passeggero).',
        'important' => 'Importante:',
        'link_expires' => 'Il link di pagamento scade il :date.',
    ],

    /* ---------- Bonifico: istruzioni ---------- */

    'awaiting_transfer' => [
        'subject' => 'Istruzioni per il bonifico · Prenotazione #:number',
        'title' => 'Completa il pagamento con bonifico',
        'intro' => 'abbiamo registrato la tua prenotazione #:number per :tour. Per confermarla, effettua un bonifico con i dati seguenti.',
        'amount' => 'Importo da versare',
        'bank_details' => 'Coordinate bancarie',
        'reference' => 'Causale: :number',
        'after' => 'Appena riceveremo il bonifico, confermeremo la prenotazione e ti invieremo i biglietti via email.',
        'hint' => 'Indica sempre il numero di prenotazione nella causale per velocizzare la verifica.',
    ],

    /* ---------- Promemoria saldo ---------- */

    'balance_reminder' => [
        'subject' => 'Salda la tua prenotazione #:number',
        'title' => 'Completa il pagamento del saldo',
        'intro' => 'si avvicina la data del tuo tour :tour. Per completare la prenotazione #:number ti ricordiamo di saldare l\'importo residuo.',
        'deposit_paid' => 'Acconto versato',
        'balance_due' => 'Saldo da pagare',
        'deadline' => '⏰ Salda entro il :date.',
        'cta' => 'Paga il saldo',
    ],

    /* ---------- Biglietti ---------- */

    'tickets' => [
        'subject' => 'I tuoi biglietti · Prenotazione #:number',
        'title' => 'Pagamento confermato — ecco i tuoi biglietti!',
        'intro' => 'il pagamento è andato a buon fine. In allegato a questa email trovi il PDF con i biglietti di tutti i passeggeri.',
        'instructions' => 'Stampalo o mostralo dal cellulare al momento dell\'imbarco: ogni biglietto ha un QR code che verrà scansionato per registrare la presenza.',
        'attachment' => '📎 In allegato: biglietti-:number.pdf',
        'tip_label' => 'Suggerimento:',
        'tip' => 'presentati al molo almeno 15 minuti prima della partenza con il PDF dei biglietti (stampato o sul cellulare).',
    ],

    /* ---------- Promemoria 48h ---------- */

    'reminder_48h' => [
        'subject' => 'Tour fra 2 giorni — Prenotazione :number',
        'eyebrow' => 'Promemoria · 48 ore',
        'title' => 'Manca poco al tuo tour!',
        'intro_with_time' => 'il tuo tour :tour è previsto per :date alle :time.',
        'intro_without_time' => 'il tuo tour :tour è previsto per il :date.',
        'instructions' => 'Ti ricordiamo di presentarti al molo almeno 15 minuti prima della partenza con il PDF dei biglietti (in allegato alla mail di conferma) — stampato o sul cellulare. Verrà scansionato il QR di ciascun biglietto al momento dell\'imbarco.',
        'closing' => 'Buon viaggio!',
    ],

    /* ---------- Promemoria 24h ---------- */

    'reminder_24h' => [
        'subject' => 'Domani parti! Promemoria check-in - :number',
        'eyebrow' => 'Domani si parte',
        'title' => '🌊 Tutto pronto per il tuo tour?',
        'intro' => 'domani parti! Ecco un riepilogo della tua prenotazione e dei partecipanti registrati.',
        'registered' => 'Partecipanti registrati (:count)',
        'lead_booker' => 'Prenotante',
        'tax_code' => 'CF',
    ],

    /* ---------- Annullamento ---------- */

    'cancelled' => [
        'subject' => 'Prenotazione annullata · #:number',
        'title' => 'Prenotazione annullata',
        'intro' => 'ti confermiamo che la tua prenotazione #:number è stata annullata.',
        'cancelled_at' => 'Annullata il',
        'reason' => 'Motivazione',
        'refund_detail' => 'Dettaglio rimborso',
        'amount_paid' => 'Importo versato',
        'refund' => 'Rimborso (:percentage%)',
        'penalty' => 'Penale trattenuta',
    ],

    /* ---------- Rimborso ---------- */

    'refunded' => [
        'subject' => 'Rimborso effettuato · Prenotazione #:number',
        'title' => 'Rimborso effettuato',
        'intro' => 'ti confermiamo che è stato disposto il rimborso relativo alla prenotazione #:number.',
        'timing' => 'L\'accredito sulla carta utilizzata per il pagamento può richiedere fino a 5–10 giorni lavorativi, in base al circuito.',
        'amount' => 'Importo rimborsato',
        'penalty' => 'Penale di storno',
        'booked_date' => 'Data prenotata',
        'note' => 'Nota',
        'closing' => 'Per qualsiasi domanda sull\'accredito o sui tempi, rispondi pure a questa email.',
    ],

    /* ---------- Benvenuto (registrazione account) ---------- */

    'welcome' => [
        'subject' => 'Benvenuto su :app',
    ],

];
