<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Promuove un utente esistente al ruolo tecnico system_admin (o lo rimuove con
 * --revoke, riportandolo a super_admin). Da usare via CLI/SSH: la promozione
 * al ruolo tecnico NON è esposta sul web di proposito.
 *
 *   php artisan user:system-admin claudio.trappolin@emotionmedia.it
 *   php artisan user:system-admin email@x.it --revoke
 */
class MakeSystemAdmin extends Command
{
    protected $signature = 'user:system-admin {email : Email dell\'utente} {--revoke : Rimuove il ruolo (torna super_admin)}';

    protected $description = 'Promuove (o revoca) il ruolo tecnico system_admin per un utente';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Nessun utente con email {$email}.");
            return self::FAILURE;
        }

        $target = $this->option('revoke') ? 'super_admin' : 'system_admin';
        $previous = $user->role;

        if ($previous === $target) {
            $this->info("{$email} è già '{$target}'. Nessuna modifica.");
            return self::SUCCESS;
        }

        $user->update(['role' => $target]);

        $this->info("Ruolo aggiornato per {$email}: {$previous} → {$target}.");
        return self::SUCCESS;
    }
}
