<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Conferma incasso bonifico.
 *
 * Caso reale SLY-2026-00209: prenotazione da 440 € con acconto di 220 € già
 * versato. L'admin clicca "bonifico ricevuto" per incassare il saldo; lo stato
 * NON passa a confermata (la condizione leggeva `balance_amount` prima
 * dell'update, un valore mai aggiornato dopo la creazione), quindi l'admin
 * riclicca e viene registrato un secondo incasso: amount_paid = 660 € su 440 €
 * dovuti. In produzione erano 5 prenotazioni per 3.380 € di eccedenza.
 */
class TransferConfirmTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBooking(array $attributes = []): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat' . uniqid(), 'slug' => 'cat-' . uniqid(),
            'capacity' => 20, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro' . uniqid(), 'slug' => 'giro-' . uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(5)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => uniqid() . '@example.com',
            'seats' => 2,
            'base_price' => 440,
            'total_amount' => 440,
            'amount_paid' => 0,
            'status' => BookingStatus::AWAITING_TRANSFER,
            'payment_type' => 'full',
        ], $attributes));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_il_secondo_click_non_registra_un_doppio_incasso(): void
    {
        // Acconto di 220 già versato, saldo di 220 da incassare: è la 209.
        $booking = $this->makeBooking([
            'status' => BookingStatus::DEPOSIT_PAID,
            'payment_type' => 'deposit',
            'deposit_amount' => 220,
            'balance_amount' => 220,
            'amount_paid' => 220,
        ]);
        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'manual', 'amount' => 220,
            'currency' => 'EUR', 'status' => PaymentStatus::SUCCEEDED, 'paid_at' => now(),
        ]);

        $admin = $this->admin();

        // Primo click: incassa il SALDO (220), non di nuovo l'acconto.
        $this->actingAs($admin)
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(440.0, (float) $booking->amount_paid, 'Deve incassare il saldo, arrivando a 440.');
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status, 'Saldata: lo stato deve passare a confermata.');
        $this->assertSame(0.0, (float) $booking->balance_amount, 'Il residuo deve azzerarsi.');

        // Secondo click (l'admin ripete perché prima non vedeva cambiamenti).
        $this->actingAs($admin)
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('error');

        $booking->refresh();
        $this->assertSame(440.0, (float) $booking->amount_paid, 'Il secondo click NON deve aggiungere nulla.');
        $this->assertSame(
            2,
            $booking->payments()->where('status', PaymentStatus::SUCCEEDED)->count(),
            'Devono restare 2 pagamenti: acconto + saldo, senza doppioni.'
        );
    }

    public function test_incasso_pieno_su_prenotazione_in_attesa_bonifico(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(440.0, (float) $booking->amount_paid);
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame(0.0, (float) $booking->balance_amount);
    }

    public function test_primo_incasso_di_un_acconto_lascia_il_saldo_aperto(): void
    {
        $booking = $this->makeBooking([
            'payment_type' => 'deposit',
            'deposit_amount' => 220,
            'balance_amount' => 220,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(220.0, (float) $booking->amount_paid, 'Primo incasso: solo l\'acconto.');
        $this->assertSame(BookingStatus::DEPOSIT_PAID, $booking->status);
        $this->assertSame(220.0, (float) $booking->balance_amount, 'Il residuo resta aperto.');
    }

    public function test_una_prenotazione_gia_saldata_rifiuta_l_incasso(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::DEPOSIT_PAID,
            'amount_paid' => 440,
            'balance_amount' => 0,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('error');

        $booking->refresh();
        $this->assertSame(440.0, (float) $booking->amount_paid);
        $this->assertSame(0, $booking->payments()->count(), 'Non deve creare alcun pagamento.');
    }

    public function test_lo_stato_non_ammesso_viene_rifiutato(): void
    {
        $booking = $this->makeBooking(['status' => BookingStatus::CANCELLED]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.confirm-transfer', $booking))
            ->assertSessionHas('error');

        $this->assertSame(0, $booking->fresh()->payments()->count());
    }
}
