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
        self::write('info', $context, $message, $booking, $extra);
    }

    public static function warning(string $context, string $message, ?Booking $booking = null, array $extra = []): void
    {
        self::write('warning', $context, $message, $booking, $extra);
    }

    public static function error(string $context, string $message, ?Booking $booking = null, array $extra = []): void
    {
        self::write('error', $context, $message, $booking, $extra);
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
     * Scrive l'evento sul file di log (canale "bookings") E sulla tabella
     * booking_events (per la dashboard). La scrittura su DB è protetta: un suo
     * errore non deve mai propagarsi al flusso prenotazione/pagamento.
     */
    private static function write(string $level, string $context, string $message, ?Booking $booking, array $extra): void
    {
        Log::channel(self::CHANNEL)->{$level}($message, self::context($context, $booking, $extra));

        try {
            \App\Models\BookingEvent::create([
                'occurred_at' => now(),
                'level' => $level,
                'context' => $context,
                'booking_number' => $booking?->booking_number,
                'booking_id' => $booking?->id,
                'status' => $booking?->status?->value,
                'message' => mb_substr($message, 0, 255),
                'meta' => $extra ?: null,
            ]);
        } catch (\Throwable $e) {
            // DB non disponibile / tabella non ancora migrata: il log su file resta.
            Log::channel(self::CHANNEL)->warning('BookingEvent non salvato su DB', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalizza il contesto per il file di log: context + booking_number + extra.
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
