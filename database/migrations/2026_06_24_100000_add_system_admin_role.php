<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aggiunge il ruolo "system_admin" (tecnico) all'enum users.role.
 * system_admin = poteri gestionali del super_admin PIÙ accesso a Sistema
 * (log, deploy, migrazioni), che il super_admin non vede più.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'super_admin', 'system_admin') NOT NULL DEFAULT 'customer'");
    }

    public function down(): void
    {
        // Riporta eventuali system_admin a super_admin prima di restringere l'enum.
        DB::statement("UPDATE users SET role = 'super_admin' WHERE role = 'system_admin'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'super_admin') NOT NULL DEFAULT 'customer'");
    }
};
