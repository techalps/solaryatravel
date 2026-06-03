<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->applyMailSettings();
        $this->serveLivewireAssetsAsStaticFile();
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
    }
}
