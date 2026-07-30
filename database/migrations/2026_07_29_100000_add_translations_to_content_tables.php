<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traduzioni dei contenuti gestibili dal cliente.
 *
 * Aggiunge una colonna JSON 'translations' alle tabelle dei contenuti mostrati
 * sul sito pubblico. Struttura:
 *
 *   { "en": { "description": "...", "itinerary": "..." },
 *     "de": { "description": "..." } }
 *
 * Perché JSON e non colonne per lingua (description_en, description_de, …):
 * aggiungere una lingua non richiede altre migration, e le tabelle non
 * crescono di 6-8 colonne per ogni lingua attivata.
 *
 * L'ITALIANO resta nelle colonne attuali: è la lingua di default e la sorgente
 * di fallback. Così i dati esistenti non si toccano e, se una traduzione manca,
 * il sito mostra l'italiano invece di un buco.
 *
 * Sostituisce il dizionario statico lang/en/db.php, che poteva essere aggiornato
 * solo da noi: ora è il cliente a tradurre dall'admin.
 */
return new class extends Migration
{
    /**
     * Tabelle che contengono testi mostrati al cliente finale.
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'tours',              // nome, descrizioni, itinerario, incluso/escluso, meta SEO
        'addons',             // nome e descrizione degli extra
        'tour_periods',       // etichetta del periodo tariffario
        'tour_age_brackets',  // etichetta della fascia d'età
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'translations')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->json('translations')->nullable()->comment('Traduzioni per lingua: {"en":{"campo":"valore"}}');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'translations')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('translations');
                });
            }
        }
    }
};
