<?php

namespace App\Console\Commands;

use App\Models\Tour;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * Elenca i contenuti dei tour a DB che NON hanno una traduzione in
 * lang/{locale}/db.php.
 *
 * Serve a verificare a colpo d'occhio la copertura del dizionario quando il
 * cliente aggiunge o modifica contenuti dall'admin: ogni riga stampata è un
 * testo che il frontend inglese mostrerebbe ancora in italiano (fallback).
 *
 *   php artisan i18n:missing
 *   php artisan i18n:missing --locale=en
 *   php artisan i18n:missing --stub     # stampa le voci pronte da incollare
 */
class I18nMissing extends Command
{
    protected $signature = 'i18n:missing
                            {--locale= : Lingua da verificare (default: la prima non-default configurata)}
                            {--stub : Stampa le voci mancanti già formattate per lang/{locale}/db.php}';

    protected $description = 'Elenca i contenuti dei tour senza traduzione nel dizionario lang/{locale}/db.php';

    public function handle(): int
    {
        $locale = (string) ($this->option('locale') ?: $this->defaultTargetLocale());

        // Le lingue verificabili sono quelle del CATALOGO (locales.names), non
        // solo config('locales.supported'): quest'ultima è il default statico e
        // non tiene conto delle lingue attivate in admin. Con il solo elenco
        // 'supported' il comando rifiutava 'es'/'fr' anche a dizionario presente.
        $catalogue = array_keys((array) config('locales.names', []));
        $available = $catalogue !== [] ? $catalogue : (array) config('locales.supported', ['it']);

        if (! in_array($locale, $available, true)) {
            $this->error("Lingua «{$locale}» non in catalogo. Disponibili: ".implode(', ', $available));

            return self::FAILURE;
        }

        if ($locale === (string) config('locales.default', 'it')) {
            $this->error("«{$locale}» è la lingua di default: non ha un dizionario da verificare.");

            return self::FAILURE;
        }

        // tdb() traduce solo quando il locale attivo non è quello di default.
        App::setLocale($locale);

        $dictionary = trans('db');

        if (! is_array($dictionary)) {
            $this->error("lang/{$locale}/db.php non trovato o non è un array.");

            return self::FAILURE;
        }

        $missing = [];
        $total = 0;

        $tours = Tour::with(['periods.ageBrackets', 'ageBrackets'])->get();

        if ($tours->isEmpty()) {
            $this->warn('Nessun tour a database.');

            return self::SUCCESS;
        }

        foreach ($tours as $tour) {
            foreach ($this->translatableStrings($tour) as $field => $values) {
                foreach ((array) $values as $value) {
                    if (! is_string($value) || trim($value) === '') {
                        continue;
                    }

                    $total++;

                    // Una voce è coperta se tdb() restituisce qualcosa di diverso
                    // dall'originale: è esattamente il criterio usato dal frontend.
                    if (tdb($value) !== $value) {
                        continue;
                    }

                    $key = i18n_normalize_db_key($value);
                    $missing[$key] ??= [];
                    $missing[$key][] = $tour->slug.' › '.$field;
                }
            }
        }

        $covered = $total - array_sum(array_map('count', $missing));

        $this->newLine();
        $this->line("Dizionario: <comment>lang/{$locale}/db.php</comment> (".count($dictionary).' voci)');
        $this->line("Stringhe trovate nei tour: <comment>{$total}</comment> — tradotte: <info>{$covered}</info>, mancanti: "
            .(count($missing) ? '<error>'.array_sum(array_map('count', $missing)).'</error>' : '<info>0</info>'));
        $this->newLine();

        if (empty($missing)) {
            $this->info('✔ Tutti i contenuti dei tour sono coperti dal dizionario.');

            return self::SUCCESS;
        }

        if ($this->option('stub')) {
            $this->line('// Voci da aggiungere a lang/'.$locale.'/db.php:');
            $this->newLine();

            foreach (array_keys($missing) as $key) {
                $this->line("    '".str_replace("'", "\\'", $key)."' => '',");
            }

            $this->newLine();

            return self::FAILURE;
        }

        $this->warn('Contenuti senza traduzione (il frontend '.strtoupper($locale).' mostrerà l\'italiano):');
        $this->newLine();

        $rows = [];

        foreach ($missing as $key => $where) {
            $rows[] = [
                implode(', ', array_unique($where)),
                Str::limit($key, 90),
            ];
        }

        $this->table(['Tour › campo', 'Testo italiano'], $rows);
        $this->line('Suggerimento: <comment>php artisan i18n:missing --locale='.$locale.' --stub</comment> stampa le voci pronte da incollare.');

        return self::FAILURE;
    }

    /**
     * Prima lingua configurata diversa da quella di default.
     */
    protected function defaultTargetLocale(): string
    {
        $default = (string) config('locales.default', 'it');

        foreach ((array) config('locales.supported', [$default]) as $locale) {
            if ($locale !== $default) {
                return $locale;
            }
        }

        return $default;
    }

    /**
     * Tutti i testi di un tour che il frontend passa da tdb().
     *
     * Deve restare allineato alle Blade pubbliche: tours/show, tours/index,
     * components/tour-card, livewire/public/booking-form.
     *
     * @return array<string, array<int, string|null>|string|null>
     */
    protected function translatableStrings(Tour $tour): array
    {
        $strings = [
            // Il nome del tour NON va tradotto: "Solarya Daily Escape",
            // "Solarya Sunset Escape", "Solarya Private Cruise" sono nomi
            // propri e restano identici nelle due lingue. Escluso dal report.
            'description_short' => $tour->description_short,
            'description' => $tour->description,
            'itinerary' => $tour->itinerary,
            'departure_point' => $tour->departure_point,
            'meta_title' => $tour->meta_title,
            'meta_description' => $tour->meta_description,
            'included' => (array) $tour->included,
            'excluded' => (array) $tour->excluded,
        ];

        // Etichette dei periodi stagionali e delle fasce d'età.
        $periodLabels = [];
        $bracketLabels = [];

        foreach ($tour->periods as $period) {
            // I nomi di mese ("Giugno", "Luglio") sono resi da Carbon nel
            // locale attivo (season_label), non dal dizionario: non sono
            // "mancanti". Solo le etichette descrittive vanno tradotte.
            if ($period->label && ! season_is_italian_month($period->label)) {
                $periodLabels[] = $period->label;
            }

            foreach ($period->ageBrackets as $bracket) {
                if ($bracket->label) {
                    $bracketLabels[] = $bracket->label;
                }
            }
        }

        foreach ($tour->ageBrackets as $bracket) {
            if ($bracket->label) {
                $bracketLabels[] = $bracket->label;
            }
        }

        $strings['periods.label'] = array_values(array_unique($periodLabels));
        $strings['ageBrackets.label'] = array_values(array_unique($bracketLabels));

        return $strings;
    }
}
