<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canale B2B agenzie — parte 2 di 2.
 *
 * Estende bookings per l'attribuzione all'agenzia e la provvigione.
 *
 * Decisioni bloccate recepite:
 * - Solarya incassa sempre; all'agenzia riconosce una % (provvigione).
 * - commission_amount = % sul prezzo finale totale IVA inclusa (= total_amount),
 *   identico a quello del cliente in autonomia. L'agenzia non incide sul prezzo.
 * - Snapshot: rate e importo salvati sulla singola prenotazione alla creazione,
 *   così i report storici non cambiano se in futuro varia la % dell'agenzia.
 * - Storno (annullamento/rimborso/penale): commission_amount=0, status=reversed.
 *   La penale è ricavo di Solarya, non base provvigionale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Agenzia a cui è attribuita la prenotazione (portal o referral). Null = vendita diretta.
            $table->foreignId('b2b_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();

            // Canale di origine dell'attribuzione.
            $table->enum('attribution_source', ['admin', 'customer', 'b2b_portal', 'b2b_referral'])
                ->default('customer')->after('source');

            // Snapshot della % al momento della creazione (non cambia se cambia la rate dell'agenzia).
            $table->decimal('commission_rate_snapshot', 5, 2)->nullable()->after('attribution_source');
            // Importo provvigione = rate% del total_amount (IVA inclusa). Azzerato su storno.
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_rate_snapshot');
            // pending = in attesa di maturazione; earned = maturata; reversed = stornata.
            $table->enum('commission_status', ['pending', 'earned', 'reversed'])
                ->nullable()->after('commission_amount');
            // Provvigione liquidata da Solarya all'agenzia (flag manuale lato admin).
            $table->boolean('commission_paid')->default(false)->after('commission_status');
            $table->timestamp('commission_paid_at')->nullable()->after('commission_paid');

            $table->index('b2b_user_id');
            $table->index('commission_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('b2b_user_id');
            $table->dropColumn([
                'attribution_source',
                'commission_rate_snapshot',
                'commission_amount',
                'commission_status',
                'commission_paid',
                'commission_paid_at',
            ]);
        });
    }
};
