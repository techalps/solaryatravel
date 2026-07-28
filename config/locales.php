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

    'supported' => ['it', 'en'],

    /*
    | Etichette dello switcher di lingua nell'header/footer.
    */
    'names' => [
        'it' => 'Italiano',
        'en' => 'English',
    ],

    'short' => [
        'it' => 'IT',
        'en' => 'EN',
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
    ],

    /*
    | Locale Open Graph (og:locale / og:locale:alternate).
    */
    'og' => [
        'it' => 'it_IT',
        'en' => 'en_GB',
    ],

    /*
    | hreflang="x-default": punta all'INGLESE perché il target primario del
    | sito è la clientela turistica straniera. Se in futuro il traffico
    | organico italiano diventasse prevalente, basta cambiare qui.
    */
    'x_default' => 'en',

];
