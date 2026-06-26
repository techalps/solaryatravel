<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canale B2B agenzie — parte 1 di 2.
 *
 * Aggiunge il ruolo "b2b" (agenzia rivenditrice) all'enum users.role e i campi
 * propri dell'agenzia: percentuale di commissione, ragione sociale e token di
 * referral usato per i link/QR tracciati.
 *
 * NB: la % di commissione vive QUI (a livello di utente b2b), non sul prodotto.
 * Sulla singola prenotazione se ne salva uno snapshot (vedi seconda migrazione).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'super_admin', 'system_admin', 'b2b') NOT NULL DEFAULT 'customer'");

        Schema::table('users', function (Blueprint $table) {
            // Valorizzato SOLO per il ruolo b2b. Es. 20.00 = 20%.
            $table->decimal('commission_rate', 5, 2)->nullable()->after('role');
            $table->string('agency_name')->nullable()->after('commission_rate');
            // Token opaco per i link referral (?ref=...). Unique per attribuzione certa.
            $table->string('referral_token')->nullable()->unique()->after('agency_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'agency_name', 'referral_token']);
        });

        // Riporta eventuali utenti b2b a customer prima di restringere l'enum.
        DB::statement("UPDATE users SET role = 'customer' WHERE role = 'b2b'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'super_admin', 'system_admin') NOT NULL DEFAULT 'customer'");
    }
};
