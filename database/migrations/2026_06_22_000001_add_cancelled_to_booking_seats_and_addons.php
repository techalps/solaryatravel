<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disdetta di singoli partecipanti / extra: l'elemento resta (storico)
        // ma non conta più nei posti, nei totali e nella disponibilità.
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('boarded_by');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Schema::table('booking_addons', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('total_price');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
        Schema::table('booking_addons', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
