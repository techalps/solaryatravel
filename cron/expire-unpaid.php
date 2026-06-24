<?php

/**
 * Wrapper cron per OVH (hosting condiviso). Vedi note in send-reminders.php.
 *
 * Voce cron OVH (percorso relativo alla home, lingua PHP 8.x, frequenza: ogni ora):
 *   www/cron/expire-unpaid.php
 *
 * Annulla le prenotazioni non pagate scadute e libera i posti riservati.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('bookings:expire-unpaid');

exit($status);
