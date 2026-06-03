<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Invio centralizzato delle notifiche all'amministratore.
 *
 * Usa il mailer SMTP dedicato "smtp_admin" se configurato nelle Impostazioni,
 * altrimenti ricade sul mailer di default. Applica anche il mittente (from)
 * dedicato agli avvisi di sistema, se impostato.
 */
class AdminMailer
{
    public static function send(Mailable $mailable): void
    {
        $to = Settings::adminNotificationEmail();

        // Mittente dedicato alle notifiche di sistema (se configurato).
        $fromAddress = trim((string) Settings::get('admin_mail_from_address', ''));
        $fromName = trim((string) Settings::get('admin_mail_from_name', '')) ?: config('mail.from.name');
        if ($fromAddress !== '') {
            $mailable->from($fromAddress, $fromName);
        }

        // Mailer dedicato se "smtp_admin" è stato registrato (host admin presente),
        // altrimenti il mailer di default.
        $mailer = config('mail.mailers.smtp_admin') ? 'smtp_admin' : config('mail.default');

        Mail::mailer($mailer)->to($to)->send($mailable);
    }
}
