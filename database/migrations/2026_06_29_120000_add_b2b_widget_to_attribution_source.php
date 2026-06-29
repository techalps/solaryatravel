<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Canale B2B agenzie — widget incorporabile.
 *
 * Aggiunge il valore 'b2b_widget' all'enum attribution_source di bookings, per
 * distinguere le vendite generate dal widget incorporato sul sito dell'agenzia
 * da quelle del link/QR referral ('b2b_referral') e del portale ('b2b_portal').
 *
 * NB: ALTER TABLE ... ENUM è MySQL-specifico (coerente con la migration che ha
 * creato la colonna). I test girano su MySQL, non su SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE bookings MODIFY attribution_source "
            . "ENUM('admin', 'customer', 'b2b_portal', 'b2b_referral', 'b2b_widget') "
            . "NOT NULL DEFAULT 'customer'"
        );
    }

    public function down(): void
    {
        // Riporta eventuali righe 'b2b_widget' a 'b2b_referral' prima di
        // rimuovere il valore, così l'ALTER non fallisce.
        DB::statement("UPDATE bookings SET attribution_source = 'b2b_referral' WHERE attribution_source = 'b2b_widget'");
        DB::statement(
            "ALTER TABLE bookings MODIFY attribution_source "
            . "ENUM('admin', 'customer', 'b2b_portal', 'b2b_referral') "
            . "NOT NULL DEFAULT 'customer'"
        );
    }
};
