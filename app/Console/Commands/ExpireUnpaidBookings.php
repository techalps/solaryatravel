<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Annulla automaticamente le prenotazioni con bonifico istantaneo non confermate
 * entro la scadenza (payment_deadline), liberando i posti riservati.
 *
 * Gira ogni ora (vedi routes/console.php).
 */
class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';

    protected $description = 'Annulla le prenotazioni con bonifico istantaneo scadute (non confermate entro il termine) e libera i posti';

    public function handle(BookingService $bookingService): int
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::AWAITING_TRANSFER)
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Nessuna prenotazione bonifico scaduta.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $booking) {
            try {
                $bookingService->cancel(
                    $booking,
                    'Bonifico istantaneo non confermato entro il termine: prenotazione scaduta automaticamente.'
                );
                $count++;
                $this->line("Annullata prenotazione scaduta #{$booking->booking_number}");
            } catch (\Throwable $e) {
                Log::warning('Impossibile annullare la prenotazione scaduta ' . $booking->booking_number . ': ' . $e->getMessage());
            }
        }

        $this->info("Prenotazioni bonifico scadute annullate: {$count}.");

        return self::SUCCESS;
    }
}
