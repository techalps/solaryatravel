<?php

/*
|--------------------------------------------------------------------------
| Helper di traduzione per i contenuti provenienti dal database
|--------------------------------------------------------------------------
|
| I testi dei tour (titoli, descrizioni, itinerari, voci incluso/escluso,
| etichette delle fasce d'età) vivono a DB e sono gestiti dall'admin, che
| resta monolingua italiano. Per servire il frontend inglese senza toccare
| lo schema usiamo un dizionario "testo italiano => English text" in
| lang/{locale}/db.php.
|
| Vantaggi rispetto a colonne _en o a una tabella translations:
|   - zero migration, zero impatto sull'admin;
|   - se il cliente modifica un testo IT, quella singola stringa torna
|     all'italiano invece di rompersi o svuotarsi;
|   - la migrazione futura a colonne traducibili parte da qui: il
|     dizionario È già la sorgente dei testi inglesi.
|
| Autoloadato via composer.json ("autoload.files").
|
*/

use Illuminate\Support\Str;

if (! function_exists('i18n_normalize_db_key')) {
    /**
     * Normalizza una stringa per il confronto con le chiavi di lang/{locale}/db.php.
     *
     * I valori a DB arrivano da un editor: possono differire dalla chiave del
     * dizionario per apostrofi tipografici (' vs ’), spazi doppi, &nbsp; o
     * newline. Senza normalizzazione il match esatto fallirebbe e l'inglese
     * ricadrebbe silenziosamente sull'italiano.
     */
    function i18n_normalize_db_key(string $text): string
    {
        // Entità HTML più comuni introdotte dagli editor (&nbsp;, &amp;, &#39;…).
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Apostrofi e virgolette tipografiche → varianti ASCII.
        $text = str_replace(
            ["\u{2019}", "\u{2018}", "\u{02BC}", "\u{FF07}", "\u{201C}", "\u{201D}", "\u{00A0}"],
            ["'", "'", "'", "'", '"', '"', ' '],
            $text
        );

        // Trattini lunghi/corti unificati: en-dash e em-dash restano distinti nei
        // testi ma i vari "minus"/"non-breaking hyphen" no.
        $text = str_replace(["\u{2011}", "\u{2212}"], '-', $text);

        // Collassa ogni sequenza di whitespace (inclusi newline) in un singolo spazio.
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}

if (! function_exists('tdb')) {
    /**
     * Traduce un contenuto proveniente dal DB.
     *
     * Due forme d'uso:
     *
     *   tdb($tour, 'description')  → traduzione salvata dal cliente in admin
     *                                (colonna JSON translations), con fallback
     *                                all'italiano. È la forma da preferire.
     *
     *   tdb($testo)                → vecchio dizionario statico lang/{locale}/db.php.
     *                                Mantenuta perché copre i contenuti già
     *                                tradotti da noi finché il cliente non li
     *                                riscrive dall'admin: senza questo fallback
     *                                il sito inglese tornerebbe in italiano di
     *                                colpo al rilascio.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $subject
     */
    function tdb($subject, ?string $field = null): mixed
    {
        // Forma modello+campo: la traduzione del cliente vince sul dizionario.
        if ($field !== null) {
            if (! $subject instanceof \Illuminate\Database\Eloquent\Model) {
                return null;
            }

            $locale = app()->getLocale();
            $original = $subject->getAttribute($field);

            if ($locale === (string) config('locales.default', 'it')) {
                return $original;
            }

            // 1) Traduzione inserita dal cliente.
            if (method_exists($subject, 'translationFor')) {
                $own = $subject->translationFor($locale, $field);
                $hasOwn = is_array($own) ? $own !== [] : trim((string) $own) !== '';

                if ($hasOwn) {
                    return $own;
                }
            }

            // 2) Ripiego sul dizionario storico (solo per i testi semplici).
            if (is_string($original)) {
                return tdb($original);
            }

            if (is_array($original)) {
                return tdb_list($original);
            }

            return $original;
        }

        $text = $subject;

        if (! is_string($text) || trim($text) === '' || app()->getLocale() === (string) config('locales.default', 'it')) {
            return $text;
        }

        $dictionary = trans('db');

        if (! is_array($dictionary)) {
            return $text;
        }

        $key = i18n_normalize_db_key($text);

        // Match diretto (chiave del dizionario già in forma normalizzata).
        if (array_key_exists($key, $dictionary)) {
            return $dictionary[$key];
        }

        // Match sulle chiavi normalizzate: copre le differenze di apostrofi e
        // spazi anche quando sono nel dizionario e non nel DB.
        $normalized = i18n_normalized_dictionary($dictionary);

        if (array_key_exists($key, $normalized)) {
            return $normalized[$key];
        }

        if (config('app.debug')) {
            logger()->channel('single')->info('[i18n] Missing DB translation', [
                'locale' => app()->getLocale(),
                'text' => Str::limit($key, 120),
            ]);
        }

        return $text;
    }
}

if (! function_exists('i18n_normalized_dictionary')) {
    /**
     * Versione del dizionario con le chiavi normalizzate, memoizzata per
     * request: tdb() viene chiamato molte volte per pagina.
     *
     * @param  array<string, string>  $dictionary
     * @return array<string, string>
     */
    function i18n_normalized_dictionary(array $dictionary): array
    {
        static $cache = [];

        $locale = app()->getLocale();

        if (! isset($cache[$locale])) {
            $normalized = [];

            foreach ($dictionary as $it => $en) {
                $normalized[i18n_normalize_db_key((string) $it)] = $en;
            }

            $cache[$locale] = $normalized;
        }

        return $cache[$locale];
    }
}

if (! function_exists('tdb_list')) {
    /**
     * Applica tdb() a ogni voce di una lista (es. included/excluded del tour).
     *
     * @param  iterable<int|string, string|null>|null  $items
     * @return array<int, string>
     */
    function tdb_list(?iterable $items): array
    {
        if ($items === null) {
            return [];
        }

        $out = [];

        foreach ($items as $item) {
            if (is_string($item) || $item === null) {
                $translated = tdb($item);

                if ($translated !== null && $translated !== '') {
                    $out[] = $translated;
                }
            }
        }

        return $out;
    }
}
