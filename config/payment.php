<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione dei gateway di pagamento
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'public_key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'secret_key' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),

        /*
        | Durata della sessione di Checkout, in minuti. Stripe ammette da 30
        | minuti a 24 ore (1440); valori fuori range vengono riportati nei limiti.
        |
        | - checkout_expiry_minutes: pagamento avviato dal sito. Il cliente è
        |   davanti allo schermo e viene rediretto subito: 30 minuti bastano e
        |   liberano prima i posti in caso di abbandono.
        |
        | - checkout_expiry_email_minutes: link inviato per EMAIL (canale agenzie
        |   e "invia link di pagamento" da admin). Qui 30 minuti erano il bug
        |   segnalato: fra accodamento SMTP, ritardi di consegna e il tempo che il
        |   cliente legge la posta, il link arrivava già scaduto. Default: 24 ore.
        */
        'checkout_expiry_minutes' => env('STRIPE_CHECKOUT_EXPIRY_MINUTES', 30),
        'checkout_expiry_email_minutes' => env('STRIPE_CHECKOUT_EXPIRY_EMAIL_MINUTES', 1440),
    ],

    // Metodi di pagamento attivi: Stripe (carta) + bonifico istantaneo
    // (gestione manuale). Nessun altro gateway è supportato.

    // Impostazioni generali
    'currency' => env('PAYMENT_CURRENCY', 'EUR'),
    'locale' => env('PAYMENT_LOCALE', 'it'),

    // Retry
    'max_retries' => 3,
    'retry_delay_seconds' => 5,
];
