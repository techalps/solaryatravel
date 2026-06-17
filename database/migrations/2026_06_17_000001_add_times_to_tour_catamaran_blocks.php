<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orari di blocco/sblocco del catamarano (uso esclusivo con precisione oraria).
        // Nullable: i blocchi "a giornata intera" non hanno orari.
        Schema::table('tour_catamaran_blocks', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('start_date');
            $table->time('end_time')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('tour_catamaran_blocks', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
