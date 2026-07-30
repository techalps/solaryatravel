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

// Scadenza pagamento: annulla i carrelli carta non pagati entro i minuti
// previsti e i bonifici non confermati entro le ore previste, liberando i posti.
// Ogni 5 minuti perché la finestra del carrello è di 15: un giro orario lascerebbe
// i posti bloccati molto oltre la scadenza.
Schedule::command('bookings:expire-unpaid')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
