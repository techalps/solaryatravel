<?php

namespace Tests\Feature;

use App\Livewire\Public\BookingForm;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Termini e privacy nel form di prenotazione.
 *
 * Erano due <a href> dentro la <label> della checkbox: cliccandoli il browser
 * attivava la checkbox invece di seguire il link (comportamento standard della
 * label), quindi i documenti non si aprivano mai. Ora sono <button> che aprono
 * un modale, il che evita anche di far uscire il cliente dal form a metà
 * compilazione.
 *
 * Il contenuto arriva dagli stessi partial delle pagine /termini-condizioni e
 * /privacy-policy: un solo testo da mantenere.
 */
class LegalModalsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTourWithDeparture(): array
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 12, 'is_active' => true,
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

        return [$tour, $dep];
    }

    private function html(): string
    {
        [$tour, $dep] = $this->makeTourWithDeparture();

        return Livewire::test(BookingForm::class, ['tour' => $tour, 'departure' => $dep])->html();
    }

    public function test_i_documenti_si_aprono_in_un_modale_non_in_una_pagina(): void
    {
        $html = $this->html();

        // I trigger sono <button>: dentro la label un <a> verrebbe scavalcato
        // dal comportamento della checkbox.
        $this->assertStringContainsString('data-legal="terms"', $html);
        $this->assertStringContainsString('data-legal="privacy"', $html);
        $this->assertStringContainsString('<button type="button" class="bk-legal-link"', $html);
    }

    public function test_i_modali_contengono_il_testo_dei_documenti(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('bk-legal-terms', $html);
        $this->assertStringContainsString('bk-legal-privacy', $html);

        // Contenuto reale, non solo il contenitore: se il partial si rompe o
        // viene rinominato, il modale resterebbe vuoto senza errori visibili.
        $this->assertStringContainsString('Titolare del Servizio', $html);
        $this->assertStringContainsString('Titolare del Trattamento', $html);
    }

    public function test_le_pagine_legali_mostrano_lo_stesso_contenuto_dei_modali(): void
    {
        // Pagine e modali leggono gli stessi partial: se qualcuno reintroduce
        // una copia del testo, questo test non basta a rilevarlo — ma almeno
        // garantisce che le pagine continuino a renderizzarsi dopo l'estrazione.
        $this->get(route('terms'))->assertOk()->assertSee('Titolare del Servizio');
        $this->get(route('privacy'))->assertOk()->assertSee('Titolare del Trattamento');
    }

    public function test_i_link_legali_sono_arancioni(): void
    {
        $html = $this->html();

        // Il colore e' il secondario del tema, non un valore inventato.
        $this->assertStringContainsString('--tg-theme-secondary', $html);
        $this->assertMatchesRegularExpression('/\.bk-legal-link\s*\{[^}]*color:/s', $html);
    }
}
