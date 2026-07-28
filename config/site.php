<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Credito di realizzazione ("powered by") nel footer
    |--------------------------------------------------------------------------
    |
    | Nome e URL dello studio che ha realizzato il sito, mostrati accanto al
    | copyright nel footer pubblico. Qui e non nella Blade così il link si
    | aggiorna in un punto solo.
    |
    | Il nome è un nome proprio: non passa dai file di lingua. La formula
    | "powered by" invece sta in lang/{locale}/common.php (chiave
    | footer.powered_by) anche se al momento è identica nelle due lingue.
    |
    | Per nascondere il credito basta svuotare 'name'.
    |
    | 'logo' è il percorso relativo a public/. Se il file manca, il footer
    | ricade automaticamente sul nome testuale. 'logo_width'/'logo_height'
    | sono le dimensioni INTRINSECHE del file (servono a riservare lo spazio
    | ed evitare il layout shift): la dimensione a schermo la decide il CSS.
    |
    */

    'vendor' => [
        'name' => env('SITE_VENDOR_NAME', 'TechAlps'),
        'url' => env('SITE_VENDOR_URL', 'https://techalps.it'),
        'logo' => env('SITE_VENDOR_LOGO', 'images/techalps-white.png'),
        'logo_width' => 150,
        'logo_height' => 34,
    ],

];
