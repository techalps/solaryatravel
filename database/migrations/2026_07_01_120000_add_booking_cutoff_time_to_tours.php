<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orario limite di prenotazione per tour: si può prenotare fino a questo orario
 * del GIORNO PRIMA della partenza (es. 22:00 = entro le 22 del giorno precedente).
 *
 * NULL = il tour usa l'orario limite GLOBALE (impostazioni → booking_cutoff_time).
 * Così l'admin può applicare un default a tutti e sovrascriverlo per singolo tour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->time('booking_cutoff_time')->nullable()->after('booking_on_request');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('booking_cutoff_time');
        });
    }
};
