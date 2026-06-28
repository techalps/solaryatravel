<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingSeat;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Rimuove i dati di test del canale B2B creati durante lo sviluppo/collaudo:
 * gli utenti agenzia con email di test e le prenotazioni a loro attribuite.
 *
 * Sicuro per default: senza --force ELENCA soltanto, non cancella nulla.
 *
 *   php artisan b2b:clean-test-data            # anteprima (sola lettura)
 *   php artisan b2b:clean-test-data --force    # elimina davvero
 *
 * Criterio di "test": utente con ruolo b2b la cui email contiene @test. o
 * @example.com. Le prenotazioni eliminate sono SOLO quelle attribuite a quegli
 * utenti (b2b_user_id), con i record collegati (seats, addon, payment).
 */
class CleanB2bTestData extends Command
{
    protected $signature = 'b2b:clean-test-data {--force : Esegue davvero la cancellazione}';

    protected $description = 'Elenca (o elimina con --force) utenti agenzia di test e relative prenotazioni';

    public function handle(): int
    {
        $testAgencies = User::where('role', 'b2b')
            ->where(function ($q) {
                $q->where('email', 'like', '%@test.%')
                    ->orWhere('email', 'like', '%@example.com');
            })
            ->get();

        if ($testAgencies->isEmpty()) {
            $this->info('Nessuna agenzia di test trovata. Niente da pulire.');
            return self::SUCCESS;
        }

        $bookings = Booking::withTrashed()
            ->whereIn('b2b_user_id', $testAgencies->pluck('id'))
            ->get();

        $this->newLine();
        $this->line('<comment>Agenzie di test individuate:</comment>');
        foreach ($testAgencies as $a) {
            $this->line("  • #{$a->id} {$a->email} — ".($a->agency_name ?: $a->name));
        }
        $this->newLine();
        $this->line('<comment>Prenotazioni attribuite a queste agenzie:</comment> '.$bookings->count());
        foreach ($bookings as $b) {
            $this->line("  • {$b->booking_number} — {$b->customer_email} — stato {$b->status->value}");
        }
        $this->newLine();

        if (! $this->option('force')) {
            $this->warn('ANTEPRIMA (sola lettura). Per eliminare davvero: php artisan b2b:clean-test-data --force');
            return self::SUCCESS;
        }

        // --- Cancellazione effettiva ---
        $bookingIds = $bookings->pluck('id');
        BookingSeat::withTrashed()->whereIn('booking_id', $bookingIds)->forceDelete();
        BookingAddon::whereIn('booking_id', $bookingIds)->delete();
        Payment::whereIn('booking_id', $bookingIds)->delete();
        Booking::withTrashed()->whereIn('id', $bookingIds)->forceDelete();

        $deletedBookings = $bookingIds->count();
        $deletedAgencies = $testAgencies->count();
        User::whereIn('id', $testAgencies->pluck('id'))->delete();

        $this->info("✓ Eliminate {$deletedBookings} prenotazioni e {$deletedAgencies} agenzie di test.");

        return self::SUCCESS;
    }
}
