<?php

return [

    'nav' => [
        'home' => 'Home',
        'tours' => 'Tour',
        'whatsapp' => 'Scrivici su WhatsApp',
        'login' => 'Accedi',
        'book_now' => 'Prenota Ora',
    ],

    'search' => [
        'tour_label' => 'Tour:',
        'all_tours' => 'Tutti i tour',
        'departure_date' => 'Data partenza:',
        'guests' => 'Ospiti:',
        'add_guests' => '+ Aggiungi ospiti',
        'adults' => 'Adulti',
        'children' => 'Bambini',
        'ok' => 'Ok',
        'search' => 'Cerca',
    ],

    'footer' => [
        'tagline' => 'Esperienze esclusive in catamarano lungo le coste della Sardegna. Comfort, eleganza e servizio impeccabile per momenti indimenticabili in mare.',
        'quick_links' => 'Link Rapidi',
        'information' => 'Informazioni',
        'book_online' => 'Prenota Online',
        'privacy' => 'Privacy Policy',
        'terms' => 'Termini e Condizioni',
        'cookie_policy' => 'Cookie Policy',
        'cookie_prefs' => 'Preferenze cookie',
        'contacts' => 'Contatti',
        'address' => 'Via Toscanini 9/C 07026 Olbia (SS)',
        'copyright' => 'Copyright © :year Solarya Travel │ Tutti i diritti riservati',
        'powered_by' => 'powered by :vendor',
    ],

    'account' => [
        'my_profile' => 'Il mio profilo',
        'my_bookings' => 'Le mie prenotazioni',
        'logout' => 'Esci',
    ],

    'a11y' => [
        'close' => 'Chiudi',
        'language' => 'Lingua',
    ],

    /*
     * Banner e pannello preferenze cookie (GDPR / Garante Privacy).
     */
    'cookie' => [
        'banner_label' => 'Informativa cookie',
        'banner_title' => '🍪 Rispettiamo la tua privacy',
        'banner_text' => 'Utilizziamo cookie tecnici necessari al funzionamento del sito e, previo tuo consenso, cookie di statistica e marketing (Google Analytics, Meta Pixel). Puoi accettare, rifiutare o personalizzare le tue scelte. Maggiori dettagli nella :policy.',
        'policy_link' => 'Cookie Policy',
        'customize' => 'Personalizza',
        'reject' => 'Rifiuta',
        'accept_all' => 'Accetta tutto',
        'prefs_title' => 'Preferenze cookie',
        'necessary_name' => 'Cookie necessari',
        'always_on' => 'Sempre attivi',
        'necessary_desc' => 'Indispensabili per la navigazione, l\'autenticazione e la gestione delle prenotazioni. Non possono essere disattivati.',
        'statistics_name' => 'Cookie statistici',
        'statistics_desc' => 'Ci aiutano a capire come viene usato il sito in forma aggregata (Google Analytics 4 con IP anonimizzato).',
        'marketing_name' => 'Cookie di marketing',
        'marketing_desc' => 'Utilizzati per misurare le campagne pubblicitarie e mostrare annunci pertinenti (Meta Pixel, Google Ads).',
        'reject_all' => 'Rifiuta tutto',
        'save' => 'Salva preferenze',
    ],

    /*
     * Stringhe esposte al JavaScript del frontend via window.i18n.
     * Vedi layouts/public.blade.php (@json(__('common.js'))).
     */
    'js' => [
        'adult' => 'adulto',
        'adults' => 'adulti',
        'child' => 'bambino',
        'children' => 'bambini',
        'add_guests' => '+ Aggiungi ospiti',
        'select_date' => 'Seleziona una data',
        'date_placeholder' => 'gg/mm/aaaa',
    ],

];
