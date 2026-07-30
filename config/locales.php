<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lingue del frontend pubblico
    |--------------------------------------------------------------------------
    |
    | L'italiano è servito sulle URL storiche SENZA prefisso (/, /tour/{slug},
    | /prenota) per non rompere SEO e link esistenti; ogni altra lingua vive
    | sotto il proprio prefisso (/en/...).
    |
    | L'admin, il canale B2B e le email transazionali restano solo in italiano.
    |
    */

    'default' => 'it',

    /*
    | Lingue attive di DEFAULT, usate finché il cliente non fa una scelta in
    | admin → Impostazioni. Da quel momento vale l'elenco salvato in
    | settings.json ('active_locales'): vedi App\Support\Locales::active().
    */
    'supported' => ['it', 'en'],

    /*
    | CATALOGO delle lingue selezionabili in admin, con l'etichetta usata dallo
    | switcher nell'header/footer. Aggiungerne una qui la rende
    | attivabile con una spunta; per una resa completa servono poi il file di
    | interfaccia lang/{codice}/ e la bandiera SVG (partials/public/flags).
    | I contenuti dei tour li traduce il cliente dall'admin.
    */
    'names' => [
        'it' => 'Italiano',
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
    ],

    'short' => [
        'it' => 'IT',
        'en' => 'EN',
        'de' => 'DE',
        'fr' => 'FR',
        'es' => 'ES',
    ],

    /*
    | Bandiera mostrata nel selettore lingua, come SVG inline
    | (resources/views/partials/public/flags/{code}.blade.php).
    |
    | NON usiamo le emoji bandiera (🇮🇹): su Windows non esiste il font
    | emoji per le regional indicator e il browser mostra due lettere in un
    | riquadro ("IT") invece della bandiera. L'SVG rende identico su tutti i
    | sistemi.
    |
    | Attenzione: la bandiera è un PAESE, non una lingua. Per l'inglese non
    | esiste una bandiera "giusta" (la clientela è internazionale, non solo
    | britannica): per questo nel selettore la bandiera è sempre accompagnata
    | dalla sigla della lingua, che è l'informazione vera.
    */
    'flags' => [
        'it' => 'it',
        'en' => 'gb',
        'de' => 'de',
        'fr' => 'fr',
        'es' => 'es',
    ],

    /*
    | Locale Open Graph (og:locale / og:locale:alternate).
    */
    'og' => [
        'it' => 'it_IT',
        'en' => 'en_GB',
        'de' => 'de_DE',
        'fr' => 'fr_FR',
        'es' => 'es_ES',
    ],

    /*
    | hreflang="x-default": punta all'INGLESE perché il target primario del
    | sito è la clientela turistica straniera. Se in futuro il traffico
    | organico italiano diventasse prevalente, basta cambiare qui.
    */
    'x_default' => 'en',

];
