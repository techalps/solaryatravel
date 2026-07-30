<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permesso "prenotazioni a 0€" per una singola agenzia.
 *
 * L'admin poteva già inserire ospiti a 0€ (posti omaggio). Serviva lo stesso per
 * le agenzie, ma NON per tutte: è una concessione che si abilita agenzia per
 * agenzia dalle sue impostazioni.
 *
 * Default false: nessuna agenzia esistente acquisisce il permesso con la
 * migrazione — va concesso esplicitamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_book_complimentary')
                ->default(false)
                ->after('commission_rate')
                ->comment('Agenzia autorizzata a registrare prenotazioni a 0€ (posti omaggio)');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_book_complimentary');
        });
    }
};
