<?php

/**
 * Wrapper cron per OVH (hosting condiviso). Vedi note in send-reminders.php.
 *
 * Voce cron OVH (percorso relativo alla home, lingua PHP 8.x, frequenza: ogni ora):
 *   www/cron/scan-notifications.php
 *
 * Genera le notifiche admin che dipendono da una condizione nel tempo:
 * prenotazioni in scadenza, saldi scaduti, documenti mancanti a partenza
 * vicina. Gli eventi immediati (nuova prenotazione, incasso, annullamento)
 * NON passano da qui: li crea l'observer nell'istante in cui accadono.
 *
 * Idempotente: rieseguirlo non duplica le segnalazioni.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('admin:scan-notifications');

exit($status);
