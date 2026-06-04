<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La colonna bookings.status è un ENUM MySQL: va esteso con i nuovi stati
     * 'deposit_paid' e 'awaiting_transfer' introdotti per acconto e bonifico.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `bookings` MODIFY `status` ENUM(
            'pending','deposit_paid','awaiting_transfer','confirmed','checked_in','completed','cancelled','refunded','no_show'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Riporta eventuali nuovi stati a 'pending' prima di restringere l'enum.
        DB::statement("UPDATE `bookings` SET `status` = 'pending' WHERE `status` IN ('deposit_paid','awaiting_transfer')");
        DB::statement("ALTER TABLE `bookings` MODIFY `status` ENUM(
            'pending','confirmed','checked_in','completed','cancelled','refunded','no_show'
        ) NOT NULL DEFAULT 'pending'");
    }
};
