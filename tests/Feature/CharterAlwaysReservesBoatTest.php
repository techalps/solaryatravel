<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourCatamaranBlock;
use App\Models\TourDeparture;
use App\Models\TourPeriod;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Una crociera privata (tour "su richiesta") è SEMPRE barca intera: la riserva
 * del catamarano non è una scelta dell'operatore ma una conseguenza del tipo
 * di tour.
 *
 * Finché dipendeva dall'interruttore "Riserva il catamarano (uso esclusivo)",
 * dimenticarlo lasciava la barca in vendita. In produzione 26 charter su 106
 * sono nati senza riserva: la barca restava prenotabile da frontend e B2B per
 * 11 dei suoi 12 posti, perché un charter pesa 1 SOLO posto a database (il
 * prezzo è un totale secco) e i soli seats non la proteggono.
 *
 * Caso reale: SLY-2026-00293, Cor Caroli, 12/08/2026 09:00-17:00, 2.055 €,
 * confermata e senza alcun blocco. Il badge mostrava "Cor Caroli 11/12".
 */
class CharterAlwaysReservesBoatTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{tour: Tour, dep: TourDeparture, cat: Catamaran, altra: Catamaran, date: string} */
    private function scenario(): array
    {
        $date = now()->addDays(30)->toDateString();

        $cat = Catamaran::create([
            'name' => 'Charter'.uniqid(), 'slug' => 'ch-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $altra = Catamaran::create([
            'name' => 'Altra'.uniqid(), 'slug' => 'al-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);

        // Il tour "su richiesta" = Private Cruise: prezzo manuale, barca intera.
        $tour = Tour::create([
            'name' => 'Private'.uniqid(), 'slug' => 'priv-'.uniqid(),
            'is_active' => true, 'booking_on_request' => true, 'duration_hours' => 8,
        ]);
        $tour->catamarans()->attach([$cat->id, $altra->id]);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['09:00'],
            'base_price' => 100,
        ]);

        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => $date,
            'start_time' => '09:00:00', 'end_time' => '17:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        return compact('tour', 'dep', 'cat', 'altra', 'date');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    }

    /**
     * Payload base. Su un tour privato partenza e ritorno sono obbligatori, quindi
     * fanno parte del payload minimo valido.
     */
    private function payload(array $s, array $extra = []): array
    {
        return array_merge([
            'tour_id' => $s['tour']->id,
            'tour_departure_id' => (string) $s['dep']->id,
            'catamaran_id' => $s['cat']->id,
            'adults' => [[
                'first_name' => 'Mario', 'last_name' => 'Rossi',
                'doc_type' => 'carta_identita', 'doc_number' => 'AB123456',
            ]],
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'total_price' => 2055,
            'payment_method' => 'manual',
            // Tour privato: partenza e ritorno sempre indicati.
            'block_start_date' => $s['date'],
            'block_start_time' => '09:00',
            'block_end_date' => $s['date'],
            'block_end_time' => '17:00',
        ], $extra);
    }

    public function test_un_charter_dal_form_normale_crea_comunque_la_riserva(): void
    {
        $s = $this->scenario();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->payload($s))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $booking = Booking::where('tour_id', $s['tour']->id)->latest('id')->first();
        $this->assertNotNull($booking, 'La prenotazione deve essere creata.');

        $blocchi = TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->get();
        $this->assertCount(1, $blocchi,
            'Un charter deve nascere SEMPRE con la riserva del catamarano, anche senza spuntare l\'interruttore.');
        $this->assertSame((int) $s['cat']->id, (int) $blocchi->first()->catamaran_id);
    }

    public function test_la_barca_di_un_charter_non_e_piu_vendibile_dal_pubblico(): void
    {
        $s = $this->scenario();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->payload($s))
            ->assertSessionHasNoErrors();

        $service = app(BookingService::class);

        // Il badge non deve più offrire quella barca (né 12/12 né 11/12: assente).
        $badge = collect($service->catamaranAvailabilityList($s['dep']))
            ->firstWhere('id', $s['cat']->id);
        $this->assertNull($badge,
            'La barca noleggiata in esclusiva non deve comparire fra quelle disponibili.');

        // E non deve essere assegnabile nemmeno per un posto.
        $assegnata = collect($service->distributeSeats($s['tour'], $s['dep'], 1) ?? [])
            ->pluck('catamaran_id')->map(fn ($id) => (int) $id);
        $this->assertFalse($assegnata->contains((int) $s['cat']->id),
            'Nessun posto può essere assegnato sulla barca di un charter.');
    }

    /**
     * REGOLA 1 — tour privato: partenza e ritorno (date + orari) sono OBBLIGATORI.
     * Sono il contratto col cliente, non un dettaglio facoltativo.
     */
    public function test_su_un_tour_privato_partenza_e_ritorno_sono_obbligatori(): void
    {
        $s = $this->scenario();

        foreach (['block_start_date', 'block_start_time', 'block_end_date', 'block_end_time'] as $campo) {
            $payload = $this->payload($s);
            unset($payload[$campo]);

            $this->actingAs($this->admin())
                ->post(route('admin.bookings.store'), $payload)
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertSame(0, Booking::where('tour_id', $s['tour']->id)->count(),
                "Senza {$campo} la crociera privata non deve essere creata.");
        }
    }

    /**
     * La riserva rispetta la fascia indicata: fuori da partenza→ritorno il
     * catamarano resta prenotabile (una private del mattino non brucia la serata).
     */
    public function test_fuori_dalla_fascia_indicata_la_barca_resta_prenotabile(): void
    {
        $s = $this->scenario();   // partenza/ritorno 09:00-17:00 dal payload

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->payload($s))
            ->assertSessionHasNoErrors();

        $booking = Booking::where('tour_id', $s['tour']->id)->latest('id')->first();
        $blocco = TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->first();

        $this->assertSame('09:00', \Carbon\Carbon::parse($blocco->start_time)->format('H:i'));
        $this->assertSame('17:00', \Carbon\Carbon::parse($blocco->end_time)->format('H:i'));

        // DENTRO la fascia (anche solo parzialmente) → riservata.
        foreach ([['09:00', '17:00'], ['16:00', '19:00']] as [$da, $a]) {
            $this->assertContains(
                (int) $s['cat']->id,
                array_map('intval', TourCatamaranBlock::blockedCatamaranIdsOn($s['date'], $da, $a)),
                "Fascia {$da}-{$a}: si sovrappone alla riserva, la barca deve risultare occupata."
            );
        }

        // FUORI dalla fascia → ancora prenotabile.
        foreach ([['18:30', '22:30'], ['06:00', '08:30']] as [$da, $a]) {
            $this->assertNotContains(
                (int) $s['cat']->id,
                array_map('intval', TourCatamaranBlock::blockedCatamaranIdsOn($s['date'], $da, $a)),
                "Fascia {$da}-{$a}: fuori dalla riserva, la barca deve restare prenotabile."
            );
        }
    }

    /**
     * REGOLA 2 — tour NORMALE + uso esclusivo: date e orari NON si scelgono.
     * Valgono quelli della partenza a calendario, come in una prenotazione normale.
     * Eventuali block_* inviati dal client vengono ignorati.
     */
    public function test_su_un_tour_normale_l_uso_esclusivo_segue_gli_orari_del_tour(): void
    {
        $s = $this->scenario();

        // Tour NORMALE (non su richiesta) con partenza a calendario 10:00-13:00.
        $tour = Tour::create([
            'name' => 'Daily'.uniqid(), 'slug' => 'daily-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 3,
        ]);
        $tour->catamarans()->attach($s['cat']->id);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['10:00'],
            'base_price' => 100,
        ]);
        $dep = TourDeparture::create([
            'tour_id' => $tour->id,
            'departure_date' => $s['date'],
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'tour_departure_id' => (string) $dep->id,
                'block_catamaran_day' => '1',
                'catamaran_ids' => [$s['cat']->id],
                // Date/orari "sbagliati" inviati a mano: devono essere IGNORATI.
                'block_start_date' => \Carbon\Carbon::parse($s['date'])->addDays(5)->toDateString(),
                'block_start_time' => '20:00',
                'block_end_date' => \Carbon\Carbon::parse($s['date'])->addDays(6)->toDateString(),
                'block_end_time' => '23:00',
                'adults' => [[
                    'first_name' => 'Luca', 'last_name' => 'Verdi',
                    'doc_type' => 'carta_identita', 'doc_number' => 'CD999888',
                ]],
                'customer_first_name' => 'Luca',
                'customer_last_name' => 'Verdi',
                'customer_email' => 'luca'.uniqid().'@example.com',
                'payment_method' => 'manual',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $booking = Booking::where('tour_id', $tour->id)->latest('id')->first();
        $blocco = TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->first();

        $this->assertNotNull($blocco, 'La riserva deve esistere anche sul tour normale.');
        $this->assertSame($s['date'], $blocco->start_date->format('Y-m-d'),
            'La data deve essere quella della partenza a calendario, non quella inviata.');
        $this->assertSame($s['date'], $blocco->end_date->format('Y-m-d'));
        $this->assertSame('10:00', \Carbon\Carbon::parse($blocco->start_time)->format('H:i'),
            'L\'orario deve essere quello del tour (10:00), non le 20:00 inviate.');
        $this->assertSame('13:00', \Carbon\Carbon::parse($blocco->end_time)->format('H:i'),
            'L\'orario di fine deve essere quello del tour (13:00).');
    }

    /**
     * Sulla Private le date e gli orari indicati fanno fede, anche su più giorni:
     * è il caso "partenza il 12 alle 18:30, ritorno il 13 alle 10:00".
     */
    public function test_sulla_private_valgono_le_date_e_gli_orari_indicati_a_mano(): void
    {
        $s = $this->scenario();
        $ritorno = \Carbon\Carbon::parse($s['date'])->addDay()->toDateString();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->payload($s, [
                'tour_departure_id' => 'virt:'.$s['date'].':18:30',
                'catamaran_ids' => [$s['cat']->id],
                'block_start_date' => $s['date'],
                'block_start_time' => '18:30',
                'block_end_date' => $ritorno,
                'block_end_time' => '10:00',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $booking = Booking::where('tour_id', $s['tour']->id)->latest('id')->first();
        $blocco = TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->first();

        $this->assertNotNull($blocco, 'La riserva deve esistere.');
        $this->assertSame($s['date'], $blocco->start_date->format('Y-m-d'));
        $this->assertSame($ritorno, $blocco->end_date->format('Y-m-d'));
        $this->assertSame('18:30', \Carbon\Carbon::parse($blocco->start_time)->format('H:i'));
        $this->assertSame('10:00', \Carbon\Carbon::parse($blocco->end_time)->format('H:i'));

        // Il mattino del primo giorno (prima delle 18:30) resta prenotabile.
        $this->assertNotContains(
            (int) $s['cat']->id,
            array_map('intval', TourCatamaranBlock::blockedCatamaranIdsOn($s['date'], '09:00', '17:00')),
            'Prima dell\'ora di partenza la barca deve restare prenotabile.'
        );
        // Il giorno del ritorno, dopo le 10:00, torna libera.
        $this->assertNotContains(
            (int) $s['cat']->id,
            array_map('intval', TourCatamaranBlock::blockedCatamaranIdsOn($ritorno, '11:00', '18:00')),
            'Dopo l\'ora di ritorno la barca deve tornare prenotabile.'
        );
    }

    /**
     * Il charter non deve poter nascere sopra una prenotazione già presente su
     * quella barca: il controllo conflitti valeva solo per il modulo esclusivo.
     */
    public function test_un_charter_non_nasce_sopra_prenotazioni_esistenti(): void
    {
        $s = $this->scenario();

        // Un normale tour condivide la stessa barca nello stesso giorno/orario.
        $altroTour = Tour::create([
            'name' => 'Daily'.uniqid(), 'slug' => 'daily-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 8,
        ]);
        $altroTour->catamarans()->attach($s['cat']->id);
        $altraDep = TourDeparture::create([
            'tour_id' => $altroTour->id,
            'departure_date' => $s['date'],
            'start_time' => '09:00:00', 'end_time' => '17:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        $esistente = Booking::create([
            'tour_id' => $altroTour->id,
            'tour_departure_id' => $altraDep->id,
            'booking_date' => $s['date'],
            'customer_first_name' => 'Anna', 'customer_last_name' => 'Bianchi',
            'customer_email' => 'anna'.uniqid().'@example.com',
            'seats' => 2, 'base_price' => 100, 'total_amount' => 200,
            'status' => \App\Enums\BookingStatus::CONFIRMED, 'payment_type' => 'full',
        ]);
        foreach ([1, 2] as $n) {
            $esistente->seatRecords()->create([
                'seat_number' => $n, 'catamaran_id' => $s['cat']->id,
                'price_paid' => 100, 'is_primary' => $n === 1,
            ]);
        }

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->payload($s))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0,
            TourCatamaranBlock::where('tour_id', $s['tour']->id)->count(),
            'Nessuna riserva deve essere creata se la barca è già occupata.');
    }
}
