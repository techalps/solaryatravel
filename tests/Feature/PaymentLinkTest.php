<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\BookingPaymentLink;
use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Link di pagamento inviato per email.
 *
 * Bug segnalato da un'agenzia: il link arrivava già SCADUTO. Causa: veniva
 * spedito il link Stripe diretto, con sessione valida 30 minuti, generata al
 * momento dell'invio. Fra accodamento SMTP, ritardi di consegna e il tempo che
 * il cliente legge la posta, i 30 minuti erano già finiti; e il reinvio
 * riproponeva la stessa sessione morta.
 *
 * Soluzione: l'email punta a una pagina NOSTRA per uuid, che crea la sessione
 * Stripe al click. Il link non può scadere.
 */
class PaymentLinkTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBooking(array $attributes = []): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 10, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Giro', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'status' => BookingStatus::PENDING,
            'payment_type' => 'full',
        ], $attributes));
    }

    public function test_email_punta_alla_pagina_di_pagamento_non_a_stripe(): void
    {
        $booking = $this->makeBooking();

        $mail = new BookingPaymentLink($booking);

        // Deve puntare al NOSTRO dominio, non a checkout.stripe.com: una URL
        // Stripe messa in una email può arrivare quando è già scaduta.
        $this->assertSame(route('payment.show', $booking->uuid), $mail->payUrl());
        $this->assertStringNotContainsString('stripe.com', $mail->payUrl());
    }

    public function test_email_non_richiede_una_sessione_stripe_alla_creazione(): void
    {
        $booking = $this->makeBooking();

        // Il secondo parametro è opzionale: costruire la mail non deve
        // richiedere una chiamata a Stripe (che in test non è configurata).
        $mail = new BookingPaymentLink($booking);

        $this->assertNull($mail->checkoutUrl);
        $this->assertNotEmpty($mail->payUrl());
    }

    public function test_con_acconto_versato_il_link_porta_alla_pagina_del_saldo(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::DEPOSIT_PAID,
            'payment_type' => 'deposit',
            'deposit_amount' => 60,
            'balance_amount' => 140,
        ]);

        // /pagamento/{uuid} accetta solo le prenotazioni "pending": per una con
        // acconto già versato il cliente va mandato alla pagina del saldo,
        // altrimenti clicca e non paga nulla.
        $mail = new BookingPaymentLink($booking);

        $this->assertSame(route('booking.balance', $booking->uuid), $mail->payUrl());
    }

    public function test_il_template_email_usa_il_link_permanente(): void
    {
        $booking = $this->makeBooking();

        $html = view('emails.bookings.payment-link', [
            'booking' => $booking,
            'payUrl' => route('payment.show', $booking->uuid),
            'checkoutUrl' => null,
        ])->render();

        $this->assertStringContainsString(route('payment.show', $booking->uuid), $html);
        $this->assertStringNotContainsString('checkout.stripe.com', $html);
    }

    public function test_la_pagina_di_pagamento_e_raggiungibile_dal_link(): void
    {
        $booking = $this->makeBooking();

        // Il link dell'email deve rispondere: se questa pagina non fosse
        // raggiungibile il cliente resterebbe bloccato come col link scaduto.
        $this->get(route('payment.show', $booking->uuid))->assertOk();
    }

    public function test_durata_sessione_estesa_per_i_link_via_email(): void
    {
        // La durata "email" deve essere sensibilmente maggiore di quella usata
        // per il checkout dal sito, ed entro il massimo ammesso da Stripe (24h).
        $site = (int) config('payment.stripe.checkout_expiry_minutes');
        $email = (int) config('payment.stripe.checkout_expiry_email_minutes');

        $this->assertGreaterThan($site, $email);
        $this->assertLessThanOrEqual(1440, $email);
        $this->assertGreaterThanOrEqual(30, $site);
    }
}
