<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerLocalizedUrlGenerator();
    }

    /**
     * Sostituisce l'UrlGenerator con la variante che risolve i nomi di route
     * del frontend nella lingua attiva (vedi App\Routing\LocalizedUrlGenerator).
     *
     * Così route('tours.show', $slug) restituisce /tour/... in italiano e
     * /en/tour/... in inglese senza che le Blade sappiano nulla del prefisso.
     *
     * La registrazione ricalca quella di Illuminate\Routing\RoutingServiceProvider
     * (rebinding su 'request' + resolver della sessione compresi).
     */
    protected function registerLocalizedUrlGenerator(): void
    {
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();

            // Come nel framework: le route vengono condivise nel container così
            // che, dopo il caching, l'istanza sia la stessa usata dal router.
            $app->instance('routes', $routes);

            return new \App\Routing\LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', function ($app, $request) {
                    $app['url']->setRequest($request);
                }),
                $app['config']['app.asset_url'],
            );
        });

        $this->app->extend('url', function (\Illuminate\Routing\UrlGenerator $url, $app) {
            // Consente a UrlGenerator di risolvere pigramente le route quando
            // vengono caricate da cache dopo la creazione del generator.
            $url->setSessionResolver(fn () => $app['session'] ?? null);
            $url->setKeyResolver(fn () => $app->make('config')->get('app.key'));

            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    public function boot(): void
    {
        // Il progetto usa Bootstrap 5: la paginazione deve usare la vista Bootstrap,
        // altrimenti Laravel renderizza il markup Tailwind di default (grafica rotta).
        Paginator::useBootstrapFive();

        $this->applyMailSettings();
        $this->serveLivewireAssetsAsStaticFile();

        // Traccia ogni email realmente spedita (Mailable + notifiche auth) come
        // evento email_sent in booking_events. Punto unico, copre anche reset
        // password / verifica / registrazione.
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Mail\Events\MessageSent::class,
            \App\Listeners\RecordSentEmail::class
        );
    }

    /**
     * Su OVH la rotta dinamica /livewire/livewire.min.js (servita da PHP) sotto
     * HTTP/2 con molti stream concorrenti causa ERR_HTTP2_PROTOCOL_ERROR.
     *
     * Puntiamo lo <script src> al file statico reale in public/vendor/livewire/:
     * poiché il file esiste fisicamente, il web server lo serve direttamente
     * senza passare da PHP, eliminando il problema HTTP/2.
     *
     * Il file va pubblicato in fase di deploy con:
     *   php artisan livewire:publish --assets
     */
    protected function serveLivewireAssetsAsStaticFile(): void
    {
        $assetPath = public_path('vendor/livewire/livewire.min.js');

        // Se gli asset non sono stati pubblicati, lascia il comportamento di default.
        if (! is_file($assetPath)) {
            return;
        }

        // La URI di questa rotta diventa lo "src" dello script Livewire.
        // Corrisponde al file statico in public/, quindi il server lo serve diretto.
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/vendor/livewire/livewire.min.js', $handle);
        });
    }

    /**
     * Se in storage/app/settings.json sono configurati SMTP host + from address,
     * sovrascrive a runtime la config mail di Laravel (sopra l'env).
     */
    protected function applyMailSettings(): void
    {
        $path = storage_path('app/settings.json');
        if (!is_file($path)) {
            return;
        }

        $settings = json_decode(@file_get_contents($path), true);
        if (!is_array($settings)) {
            return;
        }

        $host = trim($settings['smtp_host'] ?? '');
        $fromAddress = trim($settings['mail_from_address'] ?? '');
        $fromName = trim($settings['mail_from_name'] ?? '');

        if ($host !== '') {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', (int) ($settings['smtp_port'] ?? 587));
            Config::set('mail.mailers.smtp.username', $settings['smtp_username'] ?? null);
            Config::set('mail.mailers.smtp.password', $settings['smtp_password'] ?? null);
            $enc = $settings['smtp_encryption'] ?? 'tls';
            Config::set('mail.mailers.smtp.encryption', $enc === '' ? null : $enc);
            Config::set('mail.mailers.smtp.scheme', $enc === 'ssl' ? 'smtps' : null);
        }

        if ($fromAddress !== '') {
            Config::set('mail.from.address', $fromAddress);
        }
        if ($fromName !== '') {
            Config::set('mail.from.name', $fromName);
        }

        // SMTP dedicato alle notifiche admin: registra un mailer "smtp_admin"
        // separato, usato dalle Mailable Adm*. Se non configurato, le notifiche
        // admin usano il mailer di default (fallback gestito in AdminMailer).
        $adminHost = trim($settings['admin_smtp_host'] ?? '');
        if ($adminHost !== '') {
            $adminEnc = $settings['admin_smtp_encryption'] ?? 'tls';
            Config::set('mail.mailers.smtp_admin', [
                'transport' => 'smtp',
                'host' => $adminHost,
                'port' => (int) ($settings['admin_smtp_port'] ?? 587),
                'username' => $settings['admin_smtp_username'] ?? null,
                'password' => $settings['admin_smtp_password'] ?? null,
                'encryption' => $adminEnc === '' ? null : $adminEnc,
                'scheme' => $adminEnc === 'ssl' ? 'smtps' : null,
                'timeout' => null,
            ]);
        }
    }
}
