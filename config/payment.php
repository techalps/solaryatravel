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
