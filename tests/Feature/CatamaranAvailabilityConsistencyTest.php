<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingSeat;
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
 * Coerenza fra le due letture di disponibilità dei catamarani in admin.
 *
 * Segnalazione: con tutte le imbarcazioni occupate, creando una prenotazione
 * NORMALE risultavano ancora 2 barche disponibili, mentre attivando l'USO
 * ESCLUSIVO risultavano tutte bloccate (e viceversa nel caso del 1 settembre).
 *
 * Causa: le due strade contano cose diverse.
 *  - normale  → posti liberi del catamarano SU QUELLA riga tour_departures
 *               (Catamaran::seatsBookedOnDeparture($departureId));
 *  - esclusiva→ conflitti GLOBALI per catamarano+data, su QUALSIASI tour
 *               (BookingService::conflictingBookingsForBlock()).
 *
 * Quindi un catamarano occupato da un ALTRO tour nello stesso giorno/orario
 * risulta libero alla prenotazione normale e occupato all'uso esclusivo.
 */
class CatamaranAvailabilityConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    }

    /**
     * Due tour che condividono lo STESSO catamarano, con partenza lo stesso
     * giorno alla stessa ora: è la configurazione reale (una flotta, più tour).
     *
     * @return array{cat: Catamaran, tourA: Tour, depA: TourDeparture, tourB: Tour, depB: TourDeparture, date: string}
     */
    private function scenario(): array
    {
        $date = now()->addDays(30)->toDateString();

        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 10, 'is_active' => true,
        ]);

        $tours = [];
        $deps = [];
        foreach (['A', 'B'] as $k) {
            $tour = Tour::create([
                'name' => 'Tour '.$k, 'slug' => 'tour-'.strtolower($k).'-'.uniqid(),
                'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 3,
            ]);
            $tour->catamarans()->attach($cat->id);
            TourPeriod::create([
                'tour_id' => $tour->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(60)->toDateString(),
                'weekdays' => [1, 2, 3, 4, 5, 6, 7],
                'times' => ['10:00'],
                'base_price' => 100,
            ]);
            $deps[$k] = TourDeparture::create([
                'tour_id' => $tour->id,
                'departure_date' => $date,
                'start_time' => '10:00:00', 'end_time' => '13:00:00',
                'status' => 'scheduled', 'price_modifier' => 1.0,
            ]);
            $tours[$k] = $tour;
        }

        return [
            'cat' => $cat,
            'tourA' => $tours['A'], 'depA' => $deps['A'],
            'tourB' => $tours['B'], 'depB' => $deps['B'],
            'date' => $date,
        ];
    }

    /** Riempie il catamarano con una prenotazione sul tour indicato. */
    private function fillBoat(Tour $tour, TourDeparture $dep, Catamaran $cat, int $seats): Booking
    {
        $booking = Booking::create([
            'tour_id' => $tour->id,
            'tour_departure_id' => $dep->id,
            'booking_date' => $dep->departure_date,
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'mario'.uniqid().'@example.com',
            'seats' => $seats,
            'base_price' => 100,
            'total_amount' => 100 * $seats,
            'status' => BookingStatus::CONFIRMED,
            'payment_type' => 'full',
        ]);

        for ($i = 0; $i < $seats; $i++) {
            BookingSeat::create([
                'booking_id' => $booking->id,
                'seat_number' => $i + 1,
                'catamaran_id' => $cat->id,
                'is_primary' => $i === 0,
                'price_paid' => 100,
                'qr_code' => strtoupper(uniqid('QR')),
            ]);
        }

        return $booking;
    }

    /**
     * Rischio di OVERBOOKING sul sito pubblico: la capienza residua di una
     * partenza contava i posti solo sulla propria riga tour_departures, quindi
     * una barca già piena per un altro tour dello stesso giorno risultava
     * ancora vendibile. La barca è fisica: i posti sono gli stessi.
     */
    public function test_la_capienza_residua_non_vende_posti_di_una_barca_gia_piena_su_altro_tour(): void
    {
        $s = $this->scenario();

        // Il catamarano (10 posti) è pieno per il TOUR B.
        $this->fillBoat($s['tourB'], $s['depB'], $s['cat'], 10);

        $service = app(BookingService::class);

        // Capienza residua della partenza del TOUR A: la flotta condivide quella
        // stessa barca, quindi non ci sono posti da vendere.
        $this->assertSame(
            0,
            $service->remainingCapacity($s['depA']),
            'OVERBOOKING: la barca è piena per un altro tour nello stesso giorno, '
            .'ma la partenza risulta ancora vendibile.'
        );
    }

    /**
     * Slot DISGIUNTI nello stesso giorno: Daily Escape la mattina e Sunset
     * Escape la sera possono usare la stessa barca. Il conteggio è per fascia
     * oraria, non per giornata: le riserve a giornata intera restano gestite
     * dai blocchi di uso esclusivo.
     */
    public function test_slot_orari_disgiunti_condividono_la_stessa_barca(): void
    {
        $date = now()->addDays(30)->toDateString();

        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 10, 'is_active' => true,
        ]);

        // Daily 10:00-17:00
        $daily = Tour::create([
            'name' => 'Daily', 'slug' => 'daily-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 7,
        ]);
        $daily->catamarans()->attach($cat->id);
        $depDaily = TourDeparture::create([
            'tour_id' => $daily->id, 'departure_date' => $date,
            'start_time' => '10:00:00', 'end_time' => '17:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        // Sunset 18:00-21:00 (nessuna sovrapposizione)
        $sunset = Tour::create([
            'name' => 'Sunset', 'slug' => 'sunset-'.uniqid(),
            'is_active' => true, 'booking_on_request' => false, 'duration_hours' => 3,
        ]);
        $sunset->catamarans()->attach($cat->id);
        $depSunset = TourDeparture::create([
            'tour_id' => $sunset->id, 'departure_date' => $date,
            'start_time' => '18:00:00', 'end_time' => '21:00:00',
            'status' => 'scheduled', 'price_modifier' => 1.0,
        ]);

        // Il Daily riempie la barca la mattina.
        $this->fillBoat($daily, $depDaily, $cat, 10);

        $service = app(BookingService::class);

        // La mattina è piena…
        $this->assertSame(0, $service->remainingCapacity($depDaily));
        // …ma la sera la barca è di nuovo vendibile.
        $this->assertSame(
            10,
            $service->remainingCapacity($depSunset),
            'Slot disgiunti: il Sunset deve poter usare la barca liberata dal Daily.'
        );
    }

    /**
     * Causa a monte dei blocchi orfani: annullare o rimborsare una prenotazione
     * non rilasciava le sue riserve di uso esclusivo, quindi il catamarano
     * restava invendibile su quella data per sempre. In produzione erano 4 i
     * blocchi in questo stato, due dei quali sul 1 settembre segnalato.
     */
    public function test_annullare_una_prenotazione_rilascia_le_riserve_esclusive(): void
    {
        $s = $this->scenario();
        $booking = $this->fillBoat($s['tourB'], $s['depB'], $s['cat'], 4);

        TourCatamaranBlock::create([
            'tour_id' => $s['tourB']->id,
            'catamaran_id' => $s['cat']->id,
            'start_date' => $s['date'],
            'end_date' => $s['date'],
            'start_time' => '10:00',
            'end_time' => '13:00',
            'reason' => 'Riservato da prenotazione admin #'.$booking->booking_number,
        ]);

        $this->assertSame(1, TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->count());

        app(BookingService::class)->cancel($booking, 'test');

        $this->assertSame(
            0,
            TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->count(),
            'Annullando la prenotazione il blocco di uso esclusivo deve essere rilasciato.'
        );

        // E la barca torna disponibile per quello slot.
        $blocked = TourCatamaranBlock::blockedCatamaranIdsOn($s['date'], '10:00', '13:00');
        $this->assertNotContains((int) $s['cat']->id, $blocked);
    }

    public function test_rimborsare_da_admin_rilascia_le_riserve_esclusive(): void
    {
        $s = $this->scenario();
        $booking = $this->fillBoat($s['tourB'], $s['depB'], $s['cat'], 4);

        TourCatamaranBlock::create([
            'tour_id' => $s['tourB']->id,
            'catamaran_id' => $s['cat']->id,
            'start_date' => $s['date'],
            'end_date' => $s['date'],
            'start_time' => '10:00',
            'end_time' => '13:00',
            'reason' => 'Riservato da prenotazione admin #'.$booking->booking_number,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.refund', $booking), ['amount' => 100])
            ->assertRedirect();

        $this->assertSame(
            0,
            TourCatamaranBlock::where('reason', 'like', '%#'.$booking->booking_number.'%')->count(),
            'Rimborsando la prenotazione il blocco di uso esclusivo deve essere rilasciato.'
        );
    }

    public function test_le_due_letture_concordano_quando_la_barca_e_piena_sullo_stesso_tour(): void
    {
        $s = $this->scenario();
        // Riempie il catamarano sul TOUR A (10 posti su 10).
        $this->fillBoat($s['tourA'], $s['depA'], $s['cat'], 10);

        $admin = $this->admin();

        // Lettura "prenotazione normale" per il tour A.
        $normale = $this->actingAs($admin)
            ->getJson(route('admin.bookings.departures.json', $s['tourA']))
            ->assertOk()
            ->json();

        $slot = collect($normale['departures'] ?? $normale)
            ->firstWhere('iso_date', $s['date']);

        $this->assertNotNull($slot, 'Lo slot del giorno deve essere presente.');
        $this->assertSame(0, (int) $slot['available'], 'Barca piena: 0 posti liberi.');

        // Lettura "uso esclusivo": deve concordare (non prenotabile).
        $esclusiva = $this->actingAs($admin)
            ->getJson(route('admin.bookings.catamaran-availability', $s['tourA'])
                .'?start='.$s['date'].'&end='.$s['date'].'&start_time=10:00&end_time=13:00')
            ->assertOk()
            ->json();

        $boat = collect($esclusiva['catamarans'])->firstWhere('id', $s['cat']->id);
        $this->assertNotNull($boat);
        $this->assertFalse($boat['available'], 'Barca piena: non deve risultare bloccabile.');
    }

    /**
     * IL BUG: catamarano occupato da un ALTRO tour nello stesso giorno/orario.
     *
     * L'uso esclusivo lo vede occupato (conflitto globale), la prenotazione
     * normale lo vede libero perché conta i posti solo sulla PROPRIA riga
     * tour_departures. È l'incoerenza segnalata.
     */
    /**
     * Seconda direzione dell'incoerenza (caso 1 settembre): un BLOCCO di uso
     * esclusivo non collegato a una prenotazione attiva — es. la prenotazione è
     * stata annullata ma il blocco è rimasto, o il blocco è stato creato a mano.
     *
     * La prenotazione normale escludeva la barca (data "tutta prenotata") mentre
     * l'uso esclusivo la dava libera, perché ricava i conflitti dal numero
     * prenotazione scritto nel campo reason del blocco: senza prenotazione
     * attiva corrispondente, nessun conflitto.
     */
    public function test_blocco_orfano_non_deve_dare_letture_opposte(): void
    {
        $s = $this->scenario();
        $admin = $this->admin();

        // Blocco esclusivo che NON corrisponde a nessuna prenotazione attiva.
        TourCatamaranBlock::create([
            'tour_id' => $s['tourB']->id,
            'catamaran_id' => $s['cat']->id,
            'start_date' => $s['date'],
            'end_date' => $s['date'],
            'start_time' => '10:00',
            'end_time' => '13:00',
            'reason' => 'Uso esclusivo #SLY-INESISTENTE',
        ]);

        // Prenotazione normale: la barca è bloccata → 0 posti.
        $normale = $this->actingAs($admin)
            ->getJson(route('admin.bookings.departures.json', $s['tourA']))
            ->assertOk()->json();
        $slot = collect($normale['departures'] ?? $normale)->firstWhere('iso_date', $s['date']);
        $this->assertNotNull($slot);
        $normaleLibera = (int) $slot['available'] > 0;

        // Uso esclusivo: cosa dice?
        $esclusiva = $this->actingAs($admin)
            ->getJson(route('admin.bookings.catamaran-availability', $s['tourA'])
                .'?start='.$s['date'].'&end='.$s['date'].'&start_time=10:00&end_time=13:00')
            ->assertOk()->json();
        $boat = collect($esclusiva['catamarans'])->firstWhere('id', $s['cat']->id);
        $this->assertNotNull($boat);
        $esclusivaLibera = (bool) $boat['available'];

        // Le due letture devono CONCORDARE, qualunque sia l'esito.
        $this->assertSame(
            $normaleLibera,
            $esclusivaLibera,
            'Le due schermate danno esiti opposti sullo stesso catamarano/data: '
            .'normale='.($normaleLibera ? 'libera' : 'occupata')
            .', esclusiva='.($esclusivaLibera ? 'libera' : 'occupata')
        );
    }

    public function test_barca_occupata_da_un_altro_tour_deve_risultare_occupata_anche_alla_prenotazione_normale(): void
    {
        $s = $this->scenario();
        // Il catamarano è pieno per il TOUR B.
        $this->fillBoat($s['tourB'], $s['depB'], $s['cat'], 10);

        $admin = $this->admin();

        // Uso esclusivo sul TOUR A: vede il conflitto globale → non disponibile.
        $esclusiva = $this->actingAs($admin)
            ->getJson(route('admin.bookings.catamaran-availability', $s['tourA'])
                .'?start='.$s['date'].'&end='.$s['date'].'&start_time=10:00&end_time=13:00')
            ->assertOk()
            ->json();

        $boat = collect($esclusiva['catamarans'])->firstWhere('id', $s['cat']->id);
        $this->assertNotNull($boat);
        $this->assertFalse(
            $boat['available'],
            'Uso esclusivo: la barca è occupata da un altro tour, non è bloccabile.'
        );

        // Prenotazione normale sul TOUR A: DEVE vedere 0 posti liberi.
        $normale = $this->actingAs($admin)
            ->getJson(route('admin.bookings.departures.json', $s['tourA']))
            ->assertOk()
            ->json();

        $slot = collect($normale['departures'] ?? $normale)->firstWhere('iso_date', $s['date']);
        $this->assertNotNull($slot);

        $this->assertSame(
            0,
            (int) $slot['available'],
            'INCOERENZA: la barca è già piena per un altro tour nello stesso slot, '
            .'ma la prenotazione normale la conta come libera.'
        );
    }

    /**
     * Il BADGE per catamarano (frontend e B2B) deve concordare con la frase
     * "Posti disponibili per questa data" che gli sta sopra.
     *
     * Caso reale del 12/08/2026 segnalato dall'agenzia: il badge diceva
     * "Cor Caroli 12/12" mentre la frase diceva 11. Il badge contava solo la
     * propria riga tour_departures (seatsBookedOnDeparture) e non vedeva il
     * posto già venduto sulla Private Cruise nella stessa fascia oraria.
     */
    public function test_il_badge_per_catamarano_concorda_con_i_posti_disponibili_totali(): void
    {
        $s = $this->scenario();

        // UN solo posto venduto sul TOUR B, stessa barca e stessa fascia oraria.
        $this->fillBoat($s['tourB'], $s['depB'], $s['cat'], 1);

        $service = app(BookingService::class);

        $badge = collect($service->catamaranAvailabilityList($s['depA']))
            ->firstWhere('id', $s['cat']->id);

        $this->assertNotNull($badge, 'La barca deve comparire nel badge.');
        $this->assertSame(9, $badge['free'],
            'Il badge deve mostrare 9/10: un posto è già venduto su un altro tour nella stessa fascia.');

        // La somma del badge deve coincidere con la frase sopra.
        $this->assertSame(
            $service->remainingCapacity($s['depA']),
            (int) collect($service->catamaranAvailabilityList($s['depA']))->sum('free'),
            'Badge e frase "Posti disponibili per questa data" devono dire la stessa cosa.'
        );
    }

    /**
     * OVERBOOKING vero: distributeSeats() è ciò che assegna davvero i posti.
     * Contando solo la propria riga tour_departures assegnava l'intera capienza
     * a una barca già occupata da un altro tour in orario sovrapposto.
     */
    public function test_la_distribuzione_non_assegna_posti_gia_venduti_su_un_altro_tour(): void
    {
        $s = $this->scenario();

        // 4 posti già venduti sul TOUR B: sulla barca da 10 ne restano 6.
        $this->fillBoat($s['tourB'], $s['depB'], $s['cat'], 4);

        $service = app(BookingService::class);

        $this->assertNull(
            $service->distributeSeats($s['tourA'], $s['depA'], 10),
            'OVERBOOKING: 10 posti assegnati a una barca che ne ha solo 6 liberi in quella fascia.'
        );

        // I 6 posti realmente liberi devono invece essere assegnabili.
        $ok = $service->distributeSeats($s['tourA'], $s['depA'], 6);
        $this->assertIsArray($ok);
        $this->assertSame(6, array_sum(array_column($ok, 'seats')));
    }

    /**
     * La richiesta di Solarya: un catamarano riservato in USO ESCLUSIVO deve
     * risultare come se avesse tutti i posti occupati, e non deve essere
     * prenotabile né da frontend né da B2B (che condividono lo stesso form).
     */
    public function test_un_catamarano_in_uso_esclusivo_non_e_prenotabile_dal_pubblico(): void
    {
        $s = $this->scenario();

        TourCatamaranBlock::create([
            'tour_id' => $s['tourB']->id,          // riservato su un ALTRO tour
            'catamaran_id' => $s['cat']->id,
            'start_date' => $s['date'],
            'end_date' => $s['date'],
            'start_time' => '10:00:00', 'end_time' => '13:00:00',
            'reason' => 'Riservato da prenotazione admin #SLY-9999-00001',
        ]);

        $service = app(BookingService::class);

        $this->assertSame(0, $service->remainingCapacity($s['depA']),
            'La barca riservata non deve offrire posti.');
        $this->assertSame([], $service->catamaranAvailabilityList($s['depA']),
            'La barca riservata non deve comparire fra quelle disponibili.');
        $this->assertNull($service->distributeSeats($s['tourA'], $s['depA'], 1),
            'Nemmeno un posto può essere assegnato su una barca in uso esclusivo.');
    }
}
