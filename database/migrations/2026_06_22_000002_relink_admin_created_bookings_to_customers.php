<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Le prenotazioni create da admin venivano erroneamente attribuite all'account
     * dell'admin (user_id = admin). Le ricolleghiamo al CLIENTE corretto in base
     * all'email, oppure le scolleghiamo (null) se l'email non ha un account.
     * Le prenotazioni in cui l'admin è davvero il cliente (stessa email) restano.
     */
    public function up(): void
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get()->keyBy('id');
        if ($admins->isEmpty()) {
            return;
        }

        Booking::whereIn('user_id', $admins->keys())->chunkById(200, function ($bookings) use ($admins) {
            foreach ($bookings as $b) {
                $admin = $admins[$b->user_id] ?? null;
                if (! $admin) {
                    continue;
                }
                // L'admin ha prenotato per sé: lascia invariato.
                if (strcasecmp((string) $b->customer_email, (string) $admin->email) === 0) {
                    continue;
                }
                // Collega al cliente registrato con quell'email, altrimenti scollega.
                $customerId = User::where('email', $b->customer_email)->value('id');
                $b->update(['user_id' => $customerId]); // può essere null
            }
        });
    }

    public function down(): void
    {
        // Non reversibile in modo sicuro (non sappiamo quale admin l'aveva creata).
    }
};
