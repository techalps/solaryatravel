<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Logging centralizzato del ciclo prenotazioni/pagamenti/email.
 *
 * Scrive sul canale dedicato "bookings" (storage/logs/bookings.log, rotazione
 * giornaliera) con un contesto coerente: ogni riga porta sempre un "context"
 * (la fase: booking_create, payment_webhook, email_send, ...) e, quando
 * disponibile, il booking_number. Così i log si filtrano facilmente:
 *
 *   grep '"context":"payment_webhook"' storage/logs/bookings-*.log
 *   grep 'SLY-2026-00042' storage/logs/bookings-*.log
 *
 * Esempio:
 *   BookingLog::info('payment_webhook', 'Pagamento riuscito', $booking, ['amount' => 120]);
 *   BookingLog::failure('email_send', 'Invio biglietti fallito', $booking, $e);
 */
class BookingLog
{
    private const CHANNEL = 'bookings';

    public static function info(string $context, string $message, ?Booking $booking = null, array $extra = []): void
    {
        Log::channel(self::CHANNEL)->info($message, self::context($context, $booking, $extra));
    }

    public static function warning(string $context, string $message, ?Booking $booking = null, array $extra = []): void
    {
        Log::channel(self::CHANNEL)->warning($message, self::context($context, $booking, $extra));
    }

    public static function error(string $context, string $message, ?Booking $booking = null, array $extra = []): void
    {
        Log::channel(self::CHANNEL)->error($message, self::context($context, $booking, $extra));
    }

    /**
     * Scorciatoia per loggare un'eccezione catturata in un try/catch.
     * Aggiunge automaticamente messaggio e posizione dell'errore.
     */
    public static function failure(string $context, string $message, ?Booking $booking, Throwable $e, array $extra = []): void
    {
        self::error($context, $message, $booking, array_merge($extra, [
            'error' => $e->getMessage(),
            'at' => basename($e->getFile()) . ':' . $e->getLine(),
        ]));
    }

    /**
     * Normalizza il contesto: context + booking_number (se presente) + extra.
     */
    private static function context(string $context, ?Booking $booking, array $extra): array
    {
        $base = ['context' => $context];

        if ($booking !== null) {
            $base['booking_number'] = $booking->booking_number;
            $base['status'] = $booking->status?->value;
        }

        return array_merge($base, $extra);
    }
}
