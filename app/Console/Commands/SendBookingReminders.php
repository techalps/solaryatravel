<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Mail\BookingReminder24h;
use App\Mail\BookingReminder48h;
use App\Mail\BookingBalanceReminder;
use App\Mail\BookingAwaitingTransfer;
use App\Models\Booking;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Invia reminder 48h/24h, saldo acconto e bonifico non ricevuto per i tour imminenti';

    public function handle(): int
    {
        $this->sendReminder48h();
        $this->sendReminder24h();
        $this->sendBalanceReminders();
        $this->sendBankTransferReminders();
        return self::SUCCESS;
    }

    /**
     * Reminder a 48h: solo se mancano i dati di qualche partecipante.
     */
    protected function sendReminder48h(): void
    {
        $windowStart = Carbon::now()->addHours(47);
        $windowEnd = Carbon::now()->addHours(49);

        $bookings = Booking::where('status', BookingStatus::CONFIRMED)
            ->whereBetween('booking_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->whereNull('reminder_48h_sent_at')
            ->get();

        foreach ($bookings as $booking) {
            try {
                Mail::to($booking->customer_email)->send(new BookingReminder48h($booking));
                $booking->update(['reminder_48h_sent_at' => now()]);
                $this->info("Reminder 48h inviato per {$booking->booking_number}");
            } catch (\Throwable $e) {
                Log::error('Reminder 48h fallito', [
                    'booking' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reminder a 24h: sempre, con riepilogo partecipanti.
     */
    protected function sendReminder24h(): void
    {
        $windowStart = Carbon::now()->addHours(23);
        $windowEnd = Carbon::now()->addHours(25);

        $bookings = Booking::where('status', BookingStatus::CONFIRMED)
            ->whereBetween('booking_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->whereNull('reminder_24h_sent_at')
            ->with(['tour', 'departure', 'seatRecords.ageBracket'])
            ->get();

        foreach ($bookings as $booking) {
            try {
                Mail::to($booking->customer_email)->send(new BookingReminder24h($booking));
                $booking->update(['reminder_24h_sent_at' => now()]);
                $this->info("Reminder 24h inviato per {$booking->booking_number}");
            } catch (\Throwable $e) {
                Log::error('Reminder 24h fallito', [
                    'booking' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reminder saldo: prenotazioni con acconto versato (DEPOSIT_PAID) il cui
     * termine di saldo (balance_due_at) è entro le prossime 24h e non ancora
     * sollecitato. Inviato una sola volta (balance_reminder_sent_at).
     */
    protected function sendBalanceReminders(): void
    {
        $bookings = Booking::where('status', BookingStatus::DEPOSIT_PAID)
            ->where('balance_amount', '>', 0)
            ->whereNotNull('balance_due_at')
            ->where('balance_due_at', '<=', Carbon::now()->addDay())
            ->where('balance_due_at', '>=', Carbon::now())
            ->whereNull('balance_reminder_sent_at')
            ->with(['tour', 'departure'])
            ->get();

        foreach ($bookings as $booking) {
            try {
                Mail::to($booking->customer_email)->send(new BookingBalanceReminder($booking));
                $booking->update(['balance_reminder_sent_at' => now()]);
                $this->info("Reminder saldo inviato per {$booking->booking_number}");
            } catch (\Throwable $e) {
                Log::error('Reminder saldo fallito', [
                    'booking' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reminder bonifico: prenotazioni in attesa bonifico (AWAITING_TRANSFER) da
     * più di 24h e non ancora sollecitate. Inviato una sola volta
     * (bank_transfer_reminder_sent_at).
     */
    protected function sendBankTransferReminders(): void
    {
        $bookings = Booking::where('status', BookingStatus::AWAITING_TRANSFER)
            ->where('created_at', '<=', Carbon::now()->subDay())
            ->whereNull('bank_transfer_reminder_sent_at')
            ->with(['tour', 'departure'])
            ->get();

        foreach ($bookings as $booking) {
            $amountDue = $booking->payment_type === 'deposit' && $booking->deposit_amount
                ? (float) $booking->deposit_amount
                : (float) $booking->total_amount;
            try {
                Mail::to($booking->customer_email)->send(new BookingAwaitingTransfer($booking, $amountDue));
                $booking->update(['bank_transfer_reminder_sent_at' => now()]);
                $this->info("Reminder bonifico inviato per {$booking->booking_number}");
            } catch (\Throwable $e) {
                Log::error('Reminder bonifico fallito', [
                    'booking' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
