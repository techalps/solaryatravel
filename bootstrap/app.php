<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'system' => \App\Http\Middleware\SystemAdminMiddleware::class,
        ]);
        // Modalità "coming soon": blocca il sito ai non-admin (tranne il login).
        $middleware->appendToGroup('web', \App\Http\Middleware\ComingSoonMiddleware::class);
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
