<?php

use Illuminate\Support\Facades\URL;

if (! function_exists('public_site_route')) {
    /**
     * URL di una rotta del SITO PUBBLICO, sempre sull'host principale.
     *
     * Perché serve. Laravel costruisce gli URL sull'host della richiesta
     * corrente. Le rotte del sito cliente vivono in routes/web.php, registrato
     * senza vincolo di dominio, mentre il portale agenzie è vincolato a
     * config('b2b.domain') e carica solo routes/b2b.php (vedi bootstrap/app.php).
     *
     * Conseguenza: una mail generata da una richiesta sul sottodominio b2b
     * conteneva link tipo https://b2b.…/pagamento/{uuid}, che su quell'host non
     * esiste. Il cliente riceveva un 404 e non poteva pagare. Il bug si vedeva
     * solo dal canale agenzie: dal sito e dall'admin l'host era già quello
     * giusto.
     *
     * Questi link vanno al CLIENTE FINALE, che non ha nulla a che fare col
     * portale agenzie: devono puntare al sito pubblico a prescindere da chi ha
     * fatto partire l'invio.
     *
     * @param  array<string, mixed>|string|int  $parameters
     */
    function public_site_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $appUrl = config('app.url');

        if (! $appUrl) {
            return route($name, $parameters, $absolute);
        }

        URL::forceRootUrl($appUrl);

        try {
            return route($name, $parameters, $absolute);
        } finally {
            // Ripristina il comportamento predefinito (host della richiesta):
            // una pagina b2b deve continuare a generare i propri link sul
            // proprio dominio. Passando null Laravel torna a dedurlo dalla
            // richiesta corrente.
            URL::forceRootUrl(null);
        }
    }
}
