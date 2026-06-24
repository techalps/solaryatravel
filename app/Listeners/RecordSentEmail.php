<?php

namespace App\Listeners;

use App\Support\BookingLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * Traccia OGNI email realmente spedita (Mailable e notifiche auth: biglietti,
 * conferme, reminder, reset password, verifica/registrazione) come evento
 * email_sent in booking_events. Si aggancia all'evento globale del mailer,
 * quindi non serve toccare i singoli punti di invio.
 */
class RecordSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message; // Symfony\Component\Mime\Email

        $to = collect($message->getTo())->map(fn ($a) => $a->getAddress())->implode(', ');
        $subject = (string) $message->getSubject();

        // Numero prenotazione se presente negli header custom (vedi sotto), altrimenti null.
        $bookingNumber = null;
        if ($message->getHeaders()->has('X-Booking-Number')) {
            $bookingNumber = $message->getHeaders()->get('X-Booking-Number')->getBodyAsString();
        }

        BookingLog::info('email_sent', 'Email inviata: ' . ($subject ?: '(senza oggetto)'), null, array_filter([
            'to' => $to ?: null,
            'subject' => $subject ?: null,
            'mailer' => $event->data['mailer'] ?? null,
            'booking_number' => $bookingNumber,
        ]));
    }
}
