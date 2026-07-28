<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aggiunge il ruolo "skipper" all'enum users.role.
 *
 * Skipper = ruolo operativo di bordo: entra nell'area admin ma vede SOLO la
 * sezione Imbarco, per scansionare i QR dei biglietti dei passeggeri. Nessun
 * accesso a prenotazioni, incassi, report o impostazioni (il confinamento è in
 * App\Http\Middleware\SkipperAreaMiddleware).
 *
 * Senza questa migrazione l'assegnazione del ruolo fallirebbe in silenzio:
 * MySQL tronca il valore non ammesso dall'enum e l'utente resterebbe senza
 * ruolo valido.
 */
return new class extends Migration
{
    public function up(): void
    {
        // L'ALTER ENUM è MySQL-only. Su SQLite (test) la colonna role è TEXT
        // senza vincolo enum, quindi 'skipper' è già valido: saltiamo.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'super_admin', 'system_admin', 'skipper', 'b2b') NOT NULL DEFAULT 'customer'");
        }
    }

    public function down(): void
    {
        // Riporta eventuali skipper a customer prima di restringere l'enum:
        // altrimenti l'ALTER fallirebbe (o azzererebbe il ruolo).
        DB::statement("UPDATE users SET role = 'customer' WHERE role = 'skipper'");

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'super_admin', 'system_admin', 'b2b') NOT NULL DEFAULT 'customer'");
        }
    }
};
