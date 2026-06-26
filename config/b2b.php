<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host del canale B2B
    |--------------------------------------------------------------------------
    |
    | Hostname (eventualmente con porta) su cui è servita l'area agenzie.
    | Il routing per host e il middleware di gating ruolo↔dominio si basano su
    | questo valore. In locale include la porta (es. b2b.solaryatravel:8890),
    | in produzione il dominio puro (es. b2b.solaryatravel.com).
    |
    | Serve un secondo document root: la cartella /b2b con il suo index.php,
    | a cui il webserver punta questo host.
    |
    */

    'host' => env('B2B_HOST', 'b2b.solaryatravel:8890'),

    /*
    | Solo hostname senza porta. È quanto serve a Route::domain() e al confronto
    | host: Laravel matcha contro request()->getHost(), che NON include la porta.
    | La porta (in 'host') serve solo a comporre gli URL assoluti di redirect.
    */
    'domain' => \Illuminate\Support\Str::before(env('B2B_HOST', 'b2b.solaryatravel:8890'), ':'),

];

