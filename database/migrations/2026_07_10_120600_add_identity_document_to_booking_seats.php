<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento d'identità obbligatorio per ogni passeggero (compreso l'intestatario).
 * Per contratto tutti i partecipanti devono avere un documento valido fino alla
 * data del viaggio. I campi sono nullable a DB per non spaccare le prenotazioni
 * storiche: l'obbligo è applicato lato form (nuove prenotazioni) e in admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            // Tipo documento: carta_identita | passaporto | patente
            $table->string('doc_type', 20)->nullable()->after('tax_code');
            $table->string('doc_number', 40)->nullable()->after('doc_type');
            $table->date('doc_expiry')->nullable()->after('doc_number');
            // Luogo di emissione. Country = ISO-2 (IT, FR, ...).
            // Se IT: province = sigla (TO, MI...) + comune = denominazione.
            // Se estero: province resta null e comune diventa testo libero.
            $table->string('doc_issue_country', 2)->nullable()->after('doc_expiry');
            $table->string('doc_issue_province', 4)->nullable()->after('doc_issue_country');
            $table->string('doc_issue_place', 120)->nullable()->after('doc_issue_province');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn([
                'doc_type',
                'doc_number',
                'doc_expiry',
                'doc_issue_country',
                'doc_issue_province',
                'doc_issue_place',
            ]);
        });
    }
};
