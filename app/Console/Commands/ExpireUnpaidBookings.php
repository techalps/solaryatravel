<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Annulla automaticamente le prenotazioni non pagate entro la scadenza
 * (payment_deadline), liberando i posti riservati. Copre due casi:
 *
 * - carta (stato pending): la "sessione di prenotazione" aperta sul checkout e
 *   mai completata, di norma 15 minuti;
 * - bonifico istantaneo (awaiting_transfer): non confermato entro le ore
 *   previste.
 *
 * Le prenotazioni senza payment_deadline non scadono: sono carrelli per cui il
 * checkout non è mai stato aperto.
 *
 * Questo job è la rete di sicurezza, non l'unico controllo: la scadenza è
 * verificata anche ALLA LETTURA su /pagamento, così vale anche a scheduler
 * fermo. Qui si liberano i posti dei carrelli che nessuno riapre.
 *
 * Gira ogni cinque minuti (vedi routes/console.php): con una finestra di 15
 * minuti un giro orario terrebbe i posti bloccati fino a un'ora di troppo.
 */
class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';

    protected $description = 'Annulla le prenotazioni non pagate entro il termine (carrello carta e bonifico istantaneo) e libera i posti';

    public function handle(BookingService $bookingService): int
    {
        $expired = Booking::query()->expiredCheckout()->get();

        if ($expired->isEmpty()) {
            $this->info('Nessuna prenotazione scaduta.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $booking) {
            $reason = $booking->status === BookingStatus::AWAITING_TRANSFER
                ? 'Bonifico istantaneo non confermato entro il termine: prenotazione scaduta automaticamente.'
                : 'Sessione di prenotazione scaduta: pagamento non completato entro il termine.';

            try {
                $bookingService->cancel($booking, $reason);
                $count++;
                $this->line("Annullata prenotazione scaduta #{$booking->booking_number}");
            } catch (\Throwable $e) {
                Log::warning('Impossibile annullare la prenotazione scaduta ' . $booking->booking_number . ': ' . $e->getMessage());
            }
        }

        $this->info("Prenotazioni scadute annullate: {$count}.");

        return self::SUCCESS;
    }
}
