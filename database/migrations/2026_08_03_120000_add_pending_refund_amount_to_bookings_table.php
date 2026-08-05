<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storno via bonifico ancora da eseguire.
 *
 * Quando l'admin riduce il prezzo di una prenotazione già pagata e sceglie di
 * restituire il denaro con bonifico, il movimento avviene FUORI dal sistema.
 * Serve quindi ricordare quanto è dovuto al cliente finché l'admin non
 * conferma di aver eseguito il bonifico: senza questa colonna l'impegno
 * resterebbe solo nella testa di chi l'ha preso.
 *
 * Sugli storni Stripe non serve: lì il denaro parte subito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('pending_refund_amount', 10, 2)
                ->default(0)
                ->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('pending_refund_amount');
        });
    }
};
