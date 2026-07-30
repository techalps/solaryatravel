<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Traduzioni dei contenuti gestite dal cliente.
 *
 * I testi dei tour e degli extra si traducono dall'admin (colonna JSON
 * 'translations'); l'italiano resta nelle colonne normali ed è il fallback.
 * Le lingue disponibili si attivano da Impostazioni → Lingue.
 */
class ContentTranslationsTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    }

    /** @param array<int, string> $locales */
    private function activeLocales(array $locales): void
    {
        Cache::put('app_settings', ['active_locales' => $locales], 3600);
    }

    /** Contenuto di settings.json prima del test, da ripristinare dopo. */
    private ?string $settingsBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        // SettingsController scrive sul file REALE storage/app/settings.json:
        // senza backup un test che salva le impostazioni lascerebbe l'ambiente
        // modificato (è già capitato: le lingue attive erano rimaste alterate).
        $path = storage_path('app/settings.json');
        $this->settingsBackup = is_file($path) ? file_get_contents($path) : null;
    }

    protected function tearDown(): void
    {
        $path = storage_path('app/settings.json');

        if ($this->settingsBackup !== null) {
            file_put_contents($path, $this->settingsBackup);
        } elseif (is_file($path)) {
            @unlink($path);
        }

        Cache::forget('app_settings');
        parent::tearDown();
    }

    private function makeTour(array $attributes = []): Tour
    {
        $cat = Catamaran::create([
            'name' => 'Cat'.uniqid(), 'slug' => 'cat-'.uniqid(),
            'capacity' => 10, 'is_active' => true,
        ]);

        $tour = Tour::create(array_merge([
            'name' => 'Solarya Daily Escape',
            'slug' => 'daily-'.uniqid(),
            'description_short' => 'Crociera fullday tra acque cristalline',
            'description' => 'Salpa con noi e lasciati conquistare.',
            'itinerary' => 'Molara – Cala Brandinchi',
            'departure_point' => 'Cala Dei Sardi',
            'included' => ['Frutta Mista', 'Light Lunch', 'Aperitivo'],
            'excluded' => ['Bevande extra'],
            'is_active' => true,
            'booking_on_request' => false,
        ], $attributes));

        $tour->catamarans()->attach($cat->id);

        return $tour;
    }

    // ---------------------------------------------------------------- lettura

    public function test_senza_traduzione_si_mostra_l_italiano(): void
    {
        $tour = $this->makeTour();

        app()->setLocale('en');

        $this->assertSame('Solarya Daily Escape', $tour->t('name'));
        $this->assertSame('Crociera fullday tra acque cristalline', $tour->t('description_short'));
    }

    public function test_la_traduzione_del_cliente_vince(): void
    {
        $tour = $this->makeTour();
        $tour->setTranslations('en', ['description_short' => 'Full-day cruise in crystal waters']);
        $tour->save();

        app()->setLocale('en');
        $this->assertSame('Full-day cruise in crystal waters', $tour->t('description_short'));

        // L'italiano non viene toccato.
        app()->setLocale('it');
        $this->assertSame('Crociera fullday tra acque cristalline', $tour->t('description_short'));
    }

    public function test_un_campo_svuotato_torna_all_italiano(): void
    {
        $tour = $this->makeTour();
        $tour->setTranslations('en', ['description' => 'Set sail with us.']);
        $tour->save();

        app()->setLocale('en');
        $this->assertSame('Set sail with us.', $tour->t('description'));

        // Svuotare il campo = "usa l'italiano": la chiave viene rimossa.
        $tour->setTranslations('en', ['description' => '']);
        $tour->save();
        $tour->refresh();

        $this->assertSame('Salpa con noi e lasciati conquistare.', $tour->t('description'));
        $this->assertNull($tour->translationFor('en', 'description'));
    }

    public function test_le_liste_ricadono_sull_italiano_voce_per_voce(): void
    {
        $tour = $this->makeTour();

        // Solo la 1ª e la 3ª voce tradotte: la 2ª resta italiana, nell'ordine.
        $tour->setTranslations('en', ['included' => ['Mixed fruit', '', 'Aperitif']]);
        $tour->save();

        app()->setLocale('en');

        $this->assertSame(['Mixed fruit', 'Light Lunch', 'Aperitif'], $tour->t('included'));
    }

    public function test_i_campi_non_traducibili_sono_ignorati(): void
    {
        $tour = $this->makeTour();

        // 'slug' non è fra i campi traducibili: non deve finire nel JSON.
        $tour->setTranslations('en', ['slug' => 'hacked-slug', 'name' => 'Daily Escape']);
        $tour->save();
        $tour->refresh();

        $this->assertNull($tour->translationFor('en', 'slug'));
        $this->assertSame('Daily Escape', $tour->translationFor('en', 'name'));
    }

    public function test_le_altre_lingue_non_si_perdono_salvandone_una(): void
    {
        $tour = $this->makeTour();
        $tour->setTranslations('en', ['name' => 'Daily Escape']);
        $tour->setTranslations('de', ['name' => 'Tagesausflug']);
        $tour->save();
        $tour->refresh();

        // Modificare l'inglese non deve toccare il tedesco.
        $tour->setTranslations('en', ['name' => 'Daily Cruise']);
        $tour->save();
        $tour->refresh();

        $this->assertSame('Daily Cruise', $tour->translationFor('en', 'name'));
        $this->assertSame('Tagesausflug', $tour->translationFor('de', 'name'));
    }

    public function test_il_progresso_conta_solo_i_campi_valorizzati_in_italiano(): void
    {
        // Tour con 3 soli campi testuali valorizzati.
        $tour = $this->makeTour([
            'description' => '', 'itinerary' => '', 'excluded' => [],
            'meta_title' => '', 'meta_description' => '',
        ]);

        $before = $tour->translationProgress('en');
        $this->assertSame(0, $before['done']);
        // name, description_short, departure_point, included → 4
        $this->assertSame(4, $before['total']);

        $tour->setTranslations('en', ['name' => 'Daily Escape']);
        $tour->save();

        $this->assertSame(1, $tour->translationProgress('en')['done']);
    }

    // ------------------------------------------------------------- frontend

    public function test_il_sito_inglese_mostra_la_traduzione_del_cliente(): void
    {
        $this->activeLocales(['it', 'en']);

        $tour = $this->makeTour(['slug' => 'tradotto-'.uniqid()]);
        $tour->setTranslations('en', [
            'description_short' => 'Full-day cruise in crystal waters',
            'included' => ['Mixed fruit', 'Light lunch', 'Aperitif'],
        ]);
        $tour->save();

        $html = $this->get('/en/tour/'.$tour->slug)->assertOk()->getContent();

        $this->assertStringContainsString('Full-day cruise in crystal waters', $html);
        $this->assertStringContainsString('Mixed fruit', $html);
        // L'italiano non deve comparire per i campi tradotti.
        $this->assertStringNotContainsString('Crociera fullday tra acque cristalline', $html);
    }

    public function test_il_sito_italiano_resta_invariato(): void
    {
        $this->activeLocales(['it', 'en']);

        $tour = $this->makeTour(['slug' => 'italiano-'.uniqid()]);
        $tour->setTranslations('en', ['description_short' => 'Full-day cruise']);
        $tour->save();

        $html = $this->withHeader('Accept-Language', 'it-IT')
            ->get('/tour/'.$tour->slug)->assertOk()->getContent();

        $this->assertStringContainsString('Crociera fullday tra acque cristalline', $html);
        $this->assertStringNotContainsString('Full-day cruise', $html);
    }

    // -------------------------------------------------------- admin: salvataggio

    public function test_l_admin_salva_le_traduzioni_dal_form(): void
    {
        $this->activeLocales(['it', 'en']);
        $tour = $this->makeTour();

        $this->actingAs($this->admin())
            ->put(route('admin.tours.update', $tour), [
                'name' => $tour->name,
                'slug' => $tour->slug,
                'description_short' => $tour->description_short,
                'description' => $tour->description,
                'itinerary' => $tour->itinerary,
                'departure_point' => $tour->departure_point,
                'included' => $tour->included,
                'excluded' => $tour->excluded,
                'is_active' => 1,
                'translations' => [
                    'en' => [
                        'description_short' => 'Full-day cruise in crystal waters',
                        'itinerary' => 'Molara – Cala Brandinchi (stops)',
                    ],
                ],
            ])
            ->assertRedirect();

        $tour->refresh();

        $this->assertSame('Full-day cruise in crystal waters', $tour->translationFor('en', 'description_short'));
        $this->assertSame('Molara – Cala Brandinchi (stops)', $tour->translationFor('en', 'itinerary'));
    }

    public function test_una_lingua_non_attiva_non_puo_scrivere_traduzioni(): void
    {
        // Solo IT ed EN attive: il tedesco inviato a mano va ignorato.
        $this->activeLocales(['it', 'en']);
        $tour = $this->makeTour();

        $this->actingAs($this->admin())
            ->put(route('admin.tours.update', $tour), [
                'name' => $tour->name,
                'slug' => $tour->slug,
                'is_active' => 1,
                'translations' => ['de' => ['name' => 'Tagesausflug']],
            ])
            ->assertRedirect();

        $tour->refresh();
        $this->assertNull($tour->translationFor('de', 'name'));
    }

    public function test_il_form_admin_mostra_il_tab_traduzioni(): void
    {
        $this->activeLocales(['it', 'en']);
        $tour = $this->makeTour();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('tab-translations', $html);
        $this->assertStringContainsString('translations[en][description_short]', $html);
        // Il testo italiano di riferimento è mostrato accanto al campo.
        $this->assertStringContainsString('Crociera fullday tra acque cristalline', $html);
    }

    // ---------------------------------------------------------------- lingue

    public function test_le_lingue_attive_arrivano_dalle_impostazioni(): void
    {
        $this->activeLocales(['it', 'en', 'de']);

        $this->assertSame(['it', 'en', 'de'], Locales::active());
        $this->assertSame(['en', 'de'], Locales::translatable());
        $this->assertTrue(Locales::isActive('de'));
    }

    public function test_l_italiano_e_sempre_attivo(): void
    {
        // Anche se qualcuno salvasse solo l'inglese, l'italiano resta: è la
        // lingua di default e il fallback dei contenuti.
        $this->activeLocales(['en']);

        $this->assertContains('it', Locales::active());
        $this->assertNotContains('it', Locales::translatable());
    }

    public function test_una_lingua_disattivata_non_e_raggiungibile(): void
    {
        $this->activeLocales(['it']);   // solo italiano

        $tour = $this->makeTour(['slug' => 'solo-it-'.uniqid()]);

        // /en esiste come route (registrata per tutto il catalogo) ma la lingua
        // non è attiva: SetLocale ricade sull'italiano.
        $html = $this->get('/en/tour/'.$tour->slug)->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="it"', $html);
    }


    /**
     * La normalizzazione dell'elenco lingue è verificata su Locales, senza
     * passare dal form: SettingsController scrive sul file REALE
     * storage/app/settings.json e un test che lo salva azzererebbe le chiavi
     * non incluse nel payload (è già capitato: SMTP e toggle pagamento persi).
     */
    public function test_l_elenco_lingue_e_normalizzato(): void
    {
        // Ordine sparso, duplicati e una lingua inesistente.
        $this->activeLocales(['de', 'en', 'de', 'xx']);

        $active = Locales::active();

        // Italiano sempre in testa, nessun duplicato, niente lingue fuori catalogo.
        $this->assertSame('it', $active[0]);
        $this->assertSame($active, array_unique($active));
        $this->assertNotContains('xx', $active);
        $this->assertContains('en', $active);
        $this->assertContains('de', $active);
    }

    // ----------------------------------------------------------------- extra

    public function test_anche_gli_extra_sono_traducibili(): void
    {
        $addon = Addon::create([
            'name' => 'Pranzo a bordo',
            'slug' => 'pranzo-'.uniqid(),
            'description' => 'Insalata di farro e gamberetti',
            'price' => 20,
            'price_type' => 'per_person',
            'is_active' => true,
        ]);

        $addon->setTranslations('en', [
            'name' => 'Lunch on board',
            'description' => 'Spelt salad with shrimp',
        ]);
        $addon->save();

        app()->setLocale('en');
        $this->assertSame('Lunch on board', $addon->t('name'));
        $this->assertSame('Spelt salad with shrimp', $addon->t('description'));

        app()->setLocale('it');
        $this->assertSame('Pranzo a bordo', $addon->t('name'));
    }
}
