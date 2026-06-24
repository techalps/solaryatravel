<?php

/**
 * Wrapper cron per OVH (hosting condiviso).
 *
 * Il pannello OVH accetta come comando SOLO un percorso di file (niente argomenti
 * né spazi), quindi non si può chiamare direttamente "artisan bookings:send-reminders".
 * Questo script fa da ponte: avvia Laravel ed esegue il comando.
 *
 * Voce cron OVH (percorso relativo alla home, lingua PHP 8.x, frequenza: ogni ora):
 *   www/cron/send-reminders.php
 *
 * Invia i reminder 48h/24h, il sollecito saldo (seconda rata) e il sollecito bonifico.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('bookings:send-reminders');

exit($status);
