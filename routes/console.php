<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder check-in: gira ogni ora, manda 48h (se mancano dati) e 24h (sempre)
Schedule::command('bookings:send-reminders')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Scadenza bonifico istantaneo: annulla le prenotazioni non confermate entro il
// termine e libera i posti riservati.
Schedule::command('bookings:expire-unpaid')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
