<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\TourCatamaranBlock;
use Illuminate\Console\Command;

/**
 * Trova (e su richiesta rimuove) i blocchi di uso esclusivo "orfani": riserve
 * di catamarano che restano attive pur non avendo più una prenotazione valida.
 *
 * Perché esistono: fino alla correzione, annullare o rimborsare una prenotazione
 * NON rilasciava i suoi blocchi. Il catamarano restava così invendibile su quella
 * data, e le due schermate admin (prenotazione normale / uso esclusivo) davano
 * risultati opposti.
 *
 * Da qui in avanti i blocchi vengono rilasciati automaticamente
 * (BookingService::releaseExclusiveBlocks), ma quelli già creati vanno ripuliti:
 *
 *   php artisan blocks:clean-orphans            # elenca, non modifica nulla
 *   php artisan blocks:clean-orphans --fix      # rimuove i blocchi orfani
 */
class CleanOrphanCatamaranBlocks extends Command
{
    protected $signature = 'blocks:clean-orphans {--fix : Rimuove i blocchi orfani (senza questa opzione fa solo un elenco)}';

    protected $description = 'Elenca o rimuove i blocchi catamarano senza una prenotazione attiva collegata';

    /** Stati in cui la prenotazione non occupa più il catamarano. */
    private const INACTIVE = ['cancelled', 'refunded', 'no_show'];

    public function handle(): int
    {
        $blocks = TourCatamaranBlock::orderBy('start_date')->get();

        if ($blocks->isEmpty()) {
            $this->info('Nessun blocco presente.');

            return self::SUCCESS;
        }

        $orphans = [];

        foreach ($blocks as $block) {
            $reason = $this->orphanReason($block);

            if ($reason !== null) {
                $orphans[] = ['block' => $block, 'why' => $reason];
            }
        }

        $this->newLine();
        $this->line('Blocchi totali: <comment>'.$blocks->count().'</comment> — orfani: '
            .(count($orphans) ? '<error>'.count($orphans).'</error>' : '<info>0</info>'));
        $this->newLine();

        if (empty($orphans)) {
            $this->info('✔ Nessun blocco orfano: ogni riserva ha una prenotazione attiva.');

            return self::SUCCESS;
        }

        $this->table(
            ['Catamarano', 'Dal', 'Al', 'Prenotazione', 'Motivo'],
            array_map(fn ($o) => [
                '#'.$o['block']->catamaran_id,
                $o['block']->start_date->format('Y-m-d').' '.($o['block']->start_time ?: '00:00'),
                $o['block']->end_date->format('Y-m-d').' '.($o['block']->end_time ?: '24:00'),
                $this->bookingNumber($o['block']) ?: '(nessuno)',
                $o['why'],
            ], $orphans)
        );

        if (! $this->option('fix')) {
            $this->newLine();
            $this->warn('Nessuna modifica effettuata. Per rimuoverli: php artisan blocks:clean-orphans --fix');

            return self::SUCCESS;
        }

        $removed = 0;
        foreach ($orphans as $o) {
            $o['block']->delete();
            $removed++;
        }

        $this->newLine();
        $this->info("✔ Rimossi {$removed} blocchi orfani: i catamarani tornano prenotabili su quelle date.");

        return self::SUCCESS;
    }

    /**
     * Motivo per cui il blocco è orfano, o null se è legittimo.
     */
    private function orphanReason(TourCatamaranBlock $block): ?string
    {
        $number = $this->bookingNumber($block);

        // Blocco creato a mano (nessun numero prenotazione nel reason): è una
        // riserva voluta dall'operatore, non la tocchiamo.
        if ($number === null) {
            return null;
        }

        $booking = Booking::where('booking_number', $number)->first();

        if (! $booking) {
            return 'prenotazione inesistente';
        }

        if (in_array($booking->status->value, self::INACTIVE, true)) {
            return 'prenotazione '.$booking->status->value;
        }

        return null;
    }

    /** Numero prenotazione scritto nel campo reason ("… #SLY-2026-00044"). */
    private function bookingNumber(TourCatamaranBlock $block): ?string
    {
        return preg_match('/#(\S+)/', (string) $block->reason, $m) ? $m[1] : null;
    }
}
