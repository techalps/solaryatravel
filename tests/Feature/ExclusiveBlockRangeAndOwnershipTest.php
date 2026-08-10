<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
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
 * Due difetti dell'uso esclusivo, entrambi sui blocchi:
 *
 * 1) PERIODO MULTI-GIORNO controllato solo sul primo giorno. La schermata
 *    "catamarani disponibili" valutava i blocchi con blockedCatamaranIdsOn($start):
 *    un periodo che INGLOBA una riserva esistente partendo da un giorno libero
 *    (09→14/08 su una riserva 11→12/08) la dava per disponibile.
 *
 * 2) BLOCCO CONDIVISO fra prenotazioni. Era un firstOrCreate su
 *    (tour, catamarano, start_date, end_date): se esisteva già un blocco con quelle
 *    quattro colonne, il nuovo non veniva creato e orari e reason restavano quelli
 *    vecchi. La seconda prenotazione restava senza riserva propria e alla sua
 *    cancellazione releaseExclusiveBlocks() non trovava nulla (o liberava l'altra).
 */
class ExclusiveBlockRangeAndOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    }

    /** @return array{tour: Tour, cat: Catamaran, date: string} */
    private function scenarioPrivato(): array
    {
        $date = now()->addDays(40)->toDateString();

        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
        ]);
        $tour = Tour::create([
            'name' => 'Private'.uniqid(), 'slug' => 'priv-'.uniqid(),
            'is_active' => true, 'booking_on_request' => true, 'duration_hours' => 8,
        ]);
        $tour->catamarans()->attach($cat->id);
        TourPeriod::create([
            'tour_id' => $tour->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'times' => ['09:00'],
            'base_price' => 100,
        ]);

        return compact('tour', 'cat', 'date');
    }

    // ===== 1) Periodo multi-giorno =====

    /**
     * Una riserva 11→12 non deve risultare "disponibile" per un periodo 09→14 che
     * la ingloba, solo perché il 09 è libero.
     */
    public function test_un_periodo_che_ingloba_una_riserva_non_la_dichiara_disponibile(): void
    {
        $s = $this->scenarioPrivato();
        $g = fn (int $n) => \Carbon\Carbon::parse($s['date'])->addDays($n)->toDateString();

        // Riserva esistente: giorni +2 → +3 (i giorni +0 e +5 sono liberi).
        TourCatamaranBlock::create([
            'tour_id' => $s['tour']->id,
            'catamaran_id' => $s['cat']->id,
            'start_date' => $g(2), 'end_date' => $g(3),
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'reason' => 'Riservato da prenotazione admin #SLY-9999-00001',
        ]);

        // Periodo richiesto +0 → +5: ingloba la riserva, partendo da un giorno libero.
        $json = $this->actingAs($this->admin())
            ->getJson(route('admin.bookings.catamaran-availability', $s['tour'])
                .'?start='.$g(0).'&end='.$g(5).'&start_time=09:00&end_time=18:00')
            ->assertOk()
            ->json();

        $boat = collect($json['catamarans'])->firstWhere('id', $s['cat']->id);
        $this->assertNotNull($boat);
        $this->assertFalse($boat['available'],
            'La barca è riservata nei giorni interni del periodo: non può risultare disponibile.');
    }

    /**
     * Non deve però diventare troppo restrittiva: su un GIORNO SINGOLO le fasce
     * disgiunte restano compatibili (mattina riservata, pomeriggio vendibile).
     */
    public function test_su_un_giorno_singolo_le_fasce_disgiunte_restano_compatibili(): void
    {
        $s = $this->scenarioPrivato();

        TourCatamaranBlock::create([
            'tour_id' => $s['tour']->id,
            'catamaran_id' => $s['cat']->id,
            'start_date' => $s['date'], 'end_date' => $s['date'],
            'start_time' => '09:00:00', 'end_time' => '12:30:00',
            'reason' => 'Riservato da prenotazione admin #SLY-9999-00002',
        ]);

        $json = $this->actingAs($this->admin())
            ->getJson(route('admin.bookings.catamaran-availability', $s['tour'])
                .'?start='.$s['date'].'&end='.$s['date'].'&start_time=14:00&end_time=18:00')
            ->assertOk()
            ->json();

        $boat = collect($json['catamarans'])->firstWhere('id', $s['cat']->id);
        $this->assertTrue($boat['available'],
            'Il pomeriggio è libero: la riserva copre solo 09:00-12:30.');
    }

    /** Un periodo che si sovrappone solo in coda va comunque intercettato. */
    public function test_una_sovrapposizione_parziale_in_coda_viene_intercettata(): void
    {
        $s = $this->scenarioPrivato();
        $g = fn (int $n) => \Carbon\Carbon::parse($s['date'])->addDays($n)->toDateString();

        TourCatamaranBlock::create([
            'tour_id' => $s['tour']->id,
            'catamaran_id' => $s['cat']->id,
            'start_date' => $g(4), 'end_date' => $g(6),
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
            'reason' => 'Riservato da prenotazione admin #SLY-9999-00003',
        ]);

        // Richiesto +2 → +5: gli ultimi giorni cadono dentro la riserva.
        $json = $this->actingAs($this->admin())
            ->getJson(route('admin.bookings.catamaran-availability', $s['tour'])
                .'?start='.$g(2).'&end='.$g(5).'&start_time=09:00&end_time=18:00')
            ->assertOk()
            ->json();

        $boat = collect($json['catamarans'])->firstWhere('id', $s['cat']->id);
        $this->assertFalse($boat['available'], 'La coda del periodo cade dentro la riserva.');
    }

    // ===== 2) Ogni prenotazione ha la SUA riserva =====

    /**
     * Due prenotazioni a uso esclusivo sulla stessa barca e sullo stesso giorno
     * (fasce diverse) devono avere ognuna il proprio blocco, con i propri orari.
     */
    public function test_due_prenotazioni_sulla_stessa_barca_hanno_riserve_distinte(): void
    {
        $s = $this->scenarioPrivato();

        $payload = fn (string $da, string $a) => [
            'tour_id' => $s['tour']->id,
            'tour_departure_id' => 'virt:'.$s['date'].':'.$da,
            'catamaran_ids' => [$s['cat']->id],
            'block_start_date' => $s['date'],
            'block_start_time' => $da,
            'block_end_date' => $s['date'],
            'block_end_time' => $a,
            'adults' => [[
                'first_name' => 'Mario', 'last_name' => 'Rossi',
                'doc_type' => 'carta_identita', 'doc_number' => 'AB'.uniqid(),
            ]],
            'customer_first_name' => 'Mario',
            'customer_last_name' => 'Rossi',
            'customer_email' => 'm'.uniqid().'@example.com',
            'total_price' => 1000,
            'payment_method' => 'manual',
        ];

        $admin = $this->admin();

        // Mattina.
        $this->actingAs($admin)->post(route('admin.bookings.store'), $payload('09:00', '12:30'))
            ->assertSessionHasNoErrors();
        // Sera: fascia disgiunta, sulla stessa barca e stesso giorno.
        $this->actingAs($admin)->post(route('admin.bookings.store'), $payload('18:30', '22:30'))
            ->assertSessionHasNoErrors();

        $bookings = Booking::where('tour_id', $s['tour']->id)->orderBy('id')->get();
        $this->assertCount(2, $bookings, 'Entrambe le prenotazioni devono esistere.');

        foreach ($bookings as $b) {
            $blocchi = TourCatamaranBlock::where('reason', 'like', '%#'.$b->booking_number.'%')->get();
            $this->assertCount(1, $blocchi,
                "La prenotazione {$b->booking_number} deve avere la PROPRIA riserva.");
        }

        // Gli orari devono essere quelli di ciascuna, non quelli della prima.
        $orari = TourCatamaranBlock::where('catamaran_id', $s['cat']->id)->get()
            ->map(fn ($x) => \Carbon\Carbon::parse($x->start_time)->format('H:i'))
            ->sort()->values()->all();
        $this->assertSame(['09:00', '18:30'], $orari,
            'Ogni riserva conserva i propri orari: la seconda non deve ereditare quelli della prima.');
    }

    /**
     * E la cancellazione di una NON deve liberare la barca dell'altra: era la
     * conseguenza peggiore del blocco condiviso.
     */
    public function test_annullare_una_prenotazione_non_libera_la_riserva_dell_altra(): void
    {
        $s = $this->scenarioPrivato();

        $crea = function (string $da, string $a) use ($s) {
            $booking = Booking::create([
                'tour_id' => $s['tour']->id,
                'tour_departure_id' => TourDeparture::create([
                    'tour_id' => $s['tour']->id,
                    'departure_date' => $s['date'],
                    'start_time' => $da.':00', 'end_time' => $a.':00',
                    'status' => 'scheduled', 'price_modifier' => 1.0,
                ])->id,
                'booking_date' => $s['date'],
                'customer_first_name' => 'X', 'customer_last_name' => 'Y',
                'customer_email' => 'x'.uniqid().'@example.com',
                'seats' => 1, 'base_price' => 1000, 'total_amount' => 1000,
                'status' => BookingStatus::CONFIRMED, 'payment_type' => 'full',
            ]);
            $booking->refresh();
            TourCatamaranBlock::create([
                'tour_id' => $s['tour']->id,
                'catamaran_id' => $s['cat']->id,
                'start_date' => $s['date'], 'end_date' => $s['date'],
                'start_time' => $da.':00', 'end_time' => $a.':00',
                'reason' => 'Riservato da prenotazione admin #'.$booking->booking_number,
            ]);

            return $booking;
        };

        $mattina = $crea('09:00', '12:30');
        $sera = $crea('18:30', '22:30');

        app(BookingService::class)->cancel($mattina, 'Test');

        $this->assertSame(0,
            TourCatamaranBlock::where('reason', 'like', '%#'.$mattina->booking_number.'%')->count(),
            'La riserva della prenotazione annullata va rilasciata.');
        $this->assertSame(1,
            TourCatamaranBlock::where('reason', 'like', '%#'.$sera->booking_number.'%')->count(),
            'La riserva dell\'ALTRA prenotazione deve restare intatta.');
    }
}
