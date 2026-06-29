<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function (): void {
            // ORDINE IMPORTANTE: le route B2B vanno registrate PRIMA di web.php.
            // Laravel matcha in ordine di registrazione e il vincolo Route::domain
            // NON dà precedenza: se caricassimo web.php per primo, la sua "/"
            // (senza vincolo host) catturerebbe anche le richieste sull'host b2b.
            //
            // Canale B2B agenzie: servito SOLO sull'host config('b2b.host')
            // (secondo document root /b2b). Gruppo "b2b_web": stessi middleware di
            // sessione/CSRF del web ma SENZA ComingSoon/HostGate. Il gating
            // ruolo↔area è sulle singole route via middleware 'b2b'.
            \Illuminate\Support\Facades\Route::domain(config('b2b.domain'))
                ->middleware('b2b_web')
                ->group(__DIR__.'/../routes/b2b.php');

            // Sito cliente + admin (host principale).
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(__DIR__.'/../routes/web.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'system' => \App\Http\Middleware\SystemAdminMiddleware::class,
            'b2b' => \App\Http\Middleware\B2bMiddleware::class,
        ]);
        // Gruppo middleware per l'area B2B: come "web" (cookie, sessione, CSRF)
        // ma senza gli append specifici del sito principale.
        $middleware->group('b2b_web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        // Modalità "coming soon": blocca il sito ai non-admin (tranne il login).
        $middleware->appendToGroup('web', \App\Http\Middleware\ComingSoonMiddleware::class);
        // Lega host ↔ canale per il sito cliente/admin (vedi HostGateMiddleware).
        $middleware->appendToGroup('web', \App\Http\Middleware\HostGateMiddleware::class);
        // Cattura ?ref=TOKEN sul sito pubblico per l'attribuzione referral B2B.
        $middleware->appendToGroup('web', \App\Http\Middleware\CaptureReferralMiddleware::class);
        // Rende incorporabili (iframe cross-origin) SOLO le route del widget:
        // cookie SameSite=None; Secure + CSP frame-ancestors. Inerte altrove.
        // Prepend: il suo "dopo" (riscrittura cookie/header) deve girare per
        // ultimo, fuori dai cookie/session middleware.
        $middleware->prependToGroup('web', \App\Http\Middleware\WidgetEmbeddableMiddleware::class);
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Traccia in modo uniforme i fallimenti di invio email (anche quelli delle
        // notifiche auth, es. reset password, che non hanno try/catch dedicato).
        $exceptions->report(function (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e): void {
            \App\Support\BookingLog::error('email_failed', 'Invio email fallito (trasporto SMTP)', null, [
                'error' => $e->getMessage(),
            ]);
        });
    })->create();
