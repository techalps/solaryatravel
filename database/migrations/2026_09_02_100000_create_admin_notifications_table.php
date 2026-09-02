<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifiche operative per l'admin (nuove prenotazioni, pagamenti, scadenze…).
 *
 * Due tabelle e non una: la notifica è UNA (l'evento è accaduto una volta),
 * ma letta/eliminata sono per SINGOLO admin. Con un flag sulla notifica, il
 * primo che la apre la spegnerebbe per tutti — e "per ogni admin il letta/non
 * letta" non funzionerebbe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();

            // Tipo dell'evento (booking_created, payment_received, …): decide
            // icona, colore e se far comparire il toast.
            $table->string('type', 40);
            $table->string('title');
            $table->string('body', 500)->nullable();

            // Prenotazione collegata, quando c'è: dà il link "vai alla scheda".
            // nullOnDelete perché la notifica resta leggibile nello storico
            // anche se la prenotazione viene cancellata.
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // Autore dell'azione: serve a NON notificare chi l'ha provocata
            // ("a meno che non l'abbia fatta io").
            $table->foreignId('caused_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('data')->nullable();
            $table->timestamps();

            // Il badge legge le non lette recenti: indice sul tempo.
            $table->index('created_at');
            $table->index(['type', 'created_at']);
        });

        Schema::create('admin_notification_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Una riga per (notifica, admin): evita doppioni se due richieste
            // concorrenti provano a segnare la stessa notifica come letta.
            $table->unique(['admin_notification_id', 'user_id'], 'ans_notif_user_unique');

            // Conteggio delle non lette per un admin: filtra su entrambe.
            $table->index(['user_id', 'read_at', 'deleted_at'], 'ans_user_read_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_states');
        Schema::dropIfExists('admin_notifications');
    }
};
