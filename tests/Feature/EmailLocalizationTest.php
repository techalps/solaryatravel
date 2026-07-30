<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Email trasazionali nella lingua della prenotazione.
 *
 * Prima i template avevano l'italiano scritto dentro e nessun Mailable guardava
 * `bookings.locale`: un cliente che prenotava in inglese, spagnolo o francese
 * riceveva comunque email in italiano. Ora la lingua della prenotazione viene
 * applicata da App\Mail\Concerns\SendsInBookingLocale e i testi stanno in
 * lang/{locale}/emails.php.
 *
 * Le email agli ADMIN restano in italiano: le legge lo staff.
 */
class EmailLocalizationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBooking(string $locale): Booking
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Solarya Test Cruise', 'slug' => 'giro-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false,
        ]);
        $tour->catamarans()->attach($cat->id);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'm'.uniqid().'@example.com',
            'seats' => 2,
            'base_price' => 200,
            'total_amount' => 200,
            'amount_paid' => 100,
            'balance_amount' => 100,
            'balance_due_at' => now()->addDays(5),
            'locale' => $locale,
            'payment_type' => 'full',
        ]);
    }

    /** @return array<string, callable> */
    private function mailFactories(): array
    {
        return [
            'payment_link' => fn (Booking $b) => new \App\Mail\BookingPaymentLink($b),
            'awaiting_transfer' => fn (Booking $b) => new \App\Mail\BookingAwaitingTransfer($b, 200.0),
            'balance_reminder' => fn (Booking $b) => new \App\Mail\BookingBalanceReminder($b),
            'tickets' => fn (Booking $b) => new \App\Mail\BookingTickets($b),
            'reminder_48h' => fn (Booking $b) => new \App\Mail\BookingReminder48h($b),
            'reminder_24h' => fn (Booking $b) => new \App\Mail\BookingReminder24h($b),
            'cancelled' => fn (Booking $b) => new \App\Mail\BookingCancelled($b, 'test'),
            'refunded' => fn (Booking $b) => new \App\Mail\BookingRefunded($b, 100.0),
        ];
    }

    public function test_l_oggetto_segue_la_lingua_della_prenotazione(): void
    {
        $expected = [
            'it' => 'Completa il pagamento',
            'en' => 'Complete the payment',
            'es' => 'Completa el pago',
            'fr' => 'Finalisez le paiement',
        ];

        foreach ($expected as $locale => $needle) {
            $booking = $this->makeBooking($locale);
            app()->setLocale($locale);

            $subject = (new \App\Mail\BookingPaymentLink($booking))->envelope()->subject;

            $this->assertStringContainsString($needle, $subject, "Oggetto errato per '{$locale}'.");
        }
    }

    public function test_il_corpo_segue_la_lingua_della_prenotazione(): void
    {
        $expected = [
            'it' => ['Ciao', 'Totale da pagare', 'Paga ora con carta'],
            'en' => ['Hi', 'Total to pay', 'Pay now by card'],
            'es' => ['Hola', 'Total a pagar', 'Pagar ahora con tarjeta'],
            'fr' => ['Bonjour', 'Total à payer', 'Payer par carte'],
        ];

        foreach ($expected as $locale => $needles) {
            $booking = $this->makeBooking($locale);
            app()->setLocale($locale);

            $html = (new \App\Mail\BookingPaymentLink($booking))->render();

            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $html, "'{$needle}' assente in '{$locale}'.");
            }
        }
    }

    public function test_nessuna_email_straniera_contiene_italiano(): void
    {
        // Parole italiane che tradirebbero un template non convertito.
        $italian = [
            'Ciao ', 'Prenotazione', 'Totale', 'Passeggeri', 'Partecipanti',
            'Buon viaggio', 'Suggerimento', 'Salda', 'Causale',
            'Coordinate bancarie', 'Motivazione', 'Stampalo', 'Paga ora con carta',
        ];

        foreach (['en', 'es', 'fr'] as $locale) {
            $booking = $this->makeBooking($locale);
            app()->setLocale($locale);

            foreach ($this->mailFactories() as $name => $factory) {
                $html = $factory($booking)->render();

                // Le URL contengono /prenotazione/ per forza (rotte italiane):
                // non è testo visibile, quindi non conta.
                $visible = preg_replace('~https?://\S+~', '', $html);

                foreach ($italian as $word) {
                    $this->assertStringNotContainsString(
                        $word,
                        $visible,
                        "L'email '{$name}' in '{$locale}' contiene ancora «{$word}»."
                    );
                }
            }
        }
    }

    public function test_tutte_le_email_si_renderizzano_in_ogni_lingua(): void
    {
        foreach (['it', 'en', 'es', 'fr'] as $locale) {
            $booking = $this->makeBooking($locale);
            app()->setLocale($locale);

            foreach ($this->mailFactories() as $name => $factory) {
                $html = $factory($booking)->render();

                $this->assertNotEmpty($html, "Render vuoto per '{$name}' in '{$locale}'.");
                // Una chiave non risolta comparirebbe grezza nell'email.
                $this->assertStringNotContainsString('emails.', $html, "Chiave non tradotta in '{$name}' ({$locale}).");
            }
        }
    }

    public function test_una_lingua_sconosciuta_ricade_sull_italiano(): void
    {
        // Dati storici o lingua non in catalogo: meglio l'italiano che una
        // lingua senza traduzioni.
        foreach (['', 'zz', 'pt-BR'] as $weird) {
            $booking = $this->makeBooking('it');
            $booking->locale = $weird;

            $mail = new \App\Mail\BookingPaymentLink($booking);

            $this->assertSame('it', $mail->locale, "Fallback errato per «{$weird}».");
        }
    }

    public function test_la_lingua_resta_quella_scelta_anche_se_disattivata(): void
    {
        // Se il cliente ha prenotato in spagnolo e poi lo spagnolo viene
        // disattivato in admin, la SUA email deve restare in spagnolo.
        \Illuminate\Support\Facades\Cache::put('app_settings', ['active_locales' => ['it', 'en']], 60);

        $booking = $this->makeBooking('es');
        $mail = new \App\Mail\BookingPaymentLink($booking);

        $this->assertSame('es', $mail->locale);

        \Illuminate\Support\Facades\Cache::forget('app_settings');
    }

    public function test_le_date_seguono_la_lingua_dell_email(): void
    {
        // Regressione: i promemoria formattavano la data con locale('it')
        // fisso, quindi in un'email francese si leggeva "mercoledì".
        $booking = $this->makeBooking('fr');
        app()->setLocale('fr');

        $html = (new \App\Mail\BookingReminder24h($booking))->render();

        foreach (['lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'] as $day) {
            $this->assertStringNotContainsString($day, $html, "Giorno italiano «{$day}» in un'email francese.");
        }
    }

    public function test_le_email_admin_restano_in_italiano(): void
    {
        // Le legge lo staff: non devono seguire la lingua del cliente.
        $booking = $this->makeBooking('fr');
        app()->setLocale('fr');

        $subject = (new \App\Mail\AdminNewBooking($booking))->envelope()->subject;

        $this->assertStringContainsString('Nuova prenotazione', $subject);
    }
}
