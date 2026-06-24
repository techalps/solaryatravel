<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventi del ciclo prenotazioni/pagamenti/email, scritti da App\Support\BookingLog
 * in parallelo al file di log. Alimenta la dashboard "Sistema → Log".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->index();
            $table->string('level', 16)->index();        // info | warning | error
            $table->string('context', 64)->index();      // booking_create | payment_webhook | email_send | ...
            $table->string('booking_number', 40)->nullable()->index();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('status', 32)->nullable();    // stato prenotazione al momento dell'evento
            $table->string('message', 255);
            $table->json('meta')->nullable();            // contesto extra (importi, id stripe, ...)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_events');
    }
};
