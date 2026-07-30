<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracciamento e analisi (cookie banner)
    |--------------------------------------------------------------------------
    | Gli script vengono caricati solo previo consenso dell'utente tramite il
    | banner cookie. Lasciare vuoto un ID disattiva il relativo strumento.
    | La verifica Search Console è un meta tag e non installa cookie.
    |
    | IMPORTANTE — mai in locale. Il tracciamento è attivo SOLO in produzione:
    | in locale/staging le visite di sviluppo falserebbero le statistiche (e in
    | GA4 non si ripuliscono). Il controllo sta qui e non nelle viste, così vale
    | per GTM, GA4 e Meta Pixel in un colpo solo: anche con gli ID presenti in
    | .env (comodo per allineare gli ambienti) fuori produzione non viene
    | emesso nessun tag. Per una prova in locale: 'tracking.enabled' => true.
    */
    'tracking' => [
        'enabled' => env('TRACKING_ENABLED', env('APP_ENV') === 'production'),
        'gtm_id' => env('GTM_ID'),                            // GTM-XXXXXXX
        'ga4_id' => env('GOOGLE_ANALYTICS_ID'),               // G-XXXXXXXXXX
        'meta_pixel_id' => env('META_PIXEL_ID'),              // ID numerico
        'search_console' => env('GOOGLE_SITE_VERIFICATION'),  // token meta verifica
    ],

];
