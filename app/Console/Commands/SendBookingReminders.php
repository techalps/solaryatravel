<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Mail\BookingReminder24h;
use App\Mail\BookingReminder48h;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Invia reminder 48h (se mancano dati partecipanti) e 24h (sempre) per i tour imminenti';

    public function handle(): int
    {
        $this->sendReminder48h();
        $this->sendReminder24h();
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
}
