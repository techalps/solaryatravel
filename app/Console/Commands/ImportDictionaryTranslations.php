<?php

namespace App\Console\Commands;

use App\Models\Addon;
use App\Models\Tour;
use App\Models\TourAgeBracket;
use App\Models\TourPeriod;
use App\Support\Locales;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Importa nelle colonne 'translations' i testi già presenti nel dizionario
 * statico lang/{locale}/db.php.
 *
 * Serve una volta sola, al passaggio dal dizionario (modificabile solo da noi)
 * alle traduzioni gestite dal cliente: senza questo, l'admin troverebbe tutti i
 * campi vuoti pur essendo il sito inglese già tradotto, e non saprebbe cosa è
 * stato tradotto e cosa no.
 *
 *   php artisan i18n:import-dictionary            # anteprima
 *   php artisan i18n:import-dictionary --write    # scrive
 */
class ImportDictionaryTranslations extends Command
{
    protected $signature = 'i18n:import-dictionary
                            {--locale= : Lingua da importare (default: la prima non-default attiva)}
                            {--write : Scrive le traduzioni (senza questo è solo un\'anteprima)}
                            {--overwrite : Sovrascrive anche le traduzioni già inserite dal cliente}';

    protected $description = 'Copia i testi del dizionario lang/{locale}/db.php nelle traduzioni dei contenuti';

    public function handle(): int
    {
        $locale = (string) ($this->option('locale') ?: (Locales::translatable()[0] ?? ''));

        if ($locale === '' || $locale === Locales::default()) {
            $this->error('Indica una lingua diversa da quella di default con --locale.');

            return self::FAILURE;
        }

        // tdb() traduce solo quando il locale attivo non è quello di default.
        App::setLocale($locale);

        $dictionary = trans('db');

        if (! is_array($dictionary) || $dictionary === []) {
            $this->error("Nessun dizionario in lang/{$locale}/db.php: niente da importare.");

            return self::FAILURE;
        }

        $write = (bool) $this->option('write');
        $overwrite = (bool) $this->option('overwrite');

        $this->line("Dizionario <comment>lang/{$locale}/db.php</comment>: ".count($dictionary).' voci');
        $this->line($write ? '<info>Modalità scrittura</info>' : '<comment>Anteprima</comment> (usa --write per salvare)');
        $this->newLine();

        $imported = 0;
        $skipped = 0;

        /** @var array<int, Model> $records */
        $records = collect()
            ->concat(Tour::all())
            ->concat(Addon::all())
            ->concat(TourPeriod::all())
            ->concat(TourAgeBracket::all())
            ->all();

        foreach ($records as $record) {
            $changed = [];

            foreach ($record->translatableFields() as $field) {
                $original = $record->getAttribute($field);

                // Già tradotto dal cliente: non si tocca (a meno di --overwrite).
                $existing = $record->translationFor($locale, $field);
                $hasExisting = is_array($existing) ? $existing !== [] : trim((string) $existing) !== '';

                if ($hasExisting && ! $overwrite) {
                    $skipped++;

                    continue;
                }

                $translated = $this->fromDictionary($original);

                if ($translated === null) {
                    continue; // il dizionario non copre questo testo
                }

                $changed[$field] = $translated;
            }

            if ($changed === []) {
                continue;
            }

            $label = class_basename($record).' #'.$record->getKey();
            $this->line("  <info>{$label}</info>: ".implode(', ', array_keys($changed)));
            $imported += count($changed);

            if ($write) {
                $record->setTranslations($locale, $changed);
                $record->save();
            }
        }

        $this->newLine();
        $this->line("Campi importabili: <info>{$imported}</info>");

        if ($skipped > 0) {
            $this->line("Già tradotti dal cliente (saltati): <comment>{$skipped}</comment>");
        }

        if ($imported > 0 && ! $write) {
            $this->newLine();
            $this->line('Rilancia con <comment>--write</comment> per salvare.');
        }

        return self::SUCCESS;
    }

    /**
     * Traduzione dal dizionario, o null se non coperta.
     *
     * Le liste vengono tradotte voce per voce: se nessuna voce è coperta il
     * campo si considera non traducibile e viene lasciato stare.
     */
    private function fromDictionary(mixed $original): mixed
    {
        if (is_array($original)) {
            $items = array_values(array_filter((array) $original, fn ($v) => trim((string) $v) !== ''));

            if ($items === []) {
                return null;
            }

            $out = [];
            $any = false;

            foreach ($items as $item) {
                $t = tdb((string) $item);
                // Voce non coperta: si lascia vuota, così il fallback per-voce
                // del trait mostrerà l'italiano al posto giusto.
                $out[] = $t !== $item ? $t : '';
                $any = $any || $t !== $item;
            }

            return $any ? $out : null;
        }

        if (! is_string($original) || trim($original) === '') {
            return null;
        }

        $t = tdb($original);

        return $t !== $original ? $t : null;
    }
}
