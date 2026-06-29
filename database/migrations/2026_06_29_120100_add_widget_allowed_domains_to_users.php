<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canale B2B agenzie — widget incorporabile.
 *
 * Domini autorizzati a incorniciare il widget di un'agenzia. Usati per comporre
 * l'header CSP frame-ancestors sulle route del widget, così solo i siti
 * dell'agenzia possono incorporarlo con il suo token.
 *
 * Lista (un dominio per riga / array JSON). Vuota = nessuna restrizione
 * (frame-ancestors *): la sicurezza è opt-in, il widget funziona da subito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('widget_allowed_domains')->nullable()->after('referral_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('widget_allowed_domains');
        });
    }
};
