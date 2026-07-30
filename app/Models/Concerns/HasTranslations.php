<?php

namespace App\Models\Concerns;

use App\Support\Locales;

/**
 * Contenuti tradotti dal cliente, salvati nella colonna JSON 'translations'.
 *
 *   { "en": { "description": "Set sail with us…" } }
 *
 * L'ITALIANO resta nelle colonne normali del modello: è la lingua di default e
 * la sorgente di fallback. Una traduzione mancante o vuota fa ricadere sul
 * testo italiano, così sul sito non compaiono mai campi vuoti.
 *
 * Il modello che usa il trait dichiara quali campi sono traducibili:
 *
 *   protected array $translatable = ['name', 'description', 'itinerary'];
 *
 * Uso nelle view: {{ $tour->t('description') }} — oppure tdb($tour, 'description')
 * per la forma con helper.
 */
trait HasTranslations
{
    /**
     * Campi traducibili del modello.
     *
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    /**
     * Valore di un campo nella lingua indicata (default: quella attiva).
     *
     * Ricade sul valore italiano se la traduzione manca, è vuota o il campo non
     * è fra quelli traducibili.
     */
    public function t(string $field, ?string $locale = null): mixed
    {
        $original = $this->getAttribute($field);
        $locale ??= app()->getLocale();

        // Lingua di default o campo non traducibile: si usa la colonna normale.
        if ($locale === Locales::default() || ! in_array($field, $this->translatableFields(), true)) {
            return $original;
        }

        $value = $this->translationFor($locale, $field);

        // Liste (incluso/escluso): fallback voce per voce. Se la riga tradotta è
        // vuota si mostra quella italiana, così una lista tradotta a metà resta
        // leggibile e nell'ordine giusto.
        if (is_array($value)) {
            if ($value === []) {
                return $original;
            }

            if (! is_array($original)) {
                return $value;
            }

            $merged = [];
            foreach (array_values($original) as $i => $itItem) {
                $translated = trim((string) ($value[$i] ?? ''));
                $merged[] = $translated !== '' ? $translated : $itItem;
            }

            return $merged;
        }

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return $original;
    }

    /**
     * Traduzione grezza di un campo, SENZA fallback: null se non c'è.
     * Serve ai form dell'admin, che devono distinguere "non tradotto" da
     * "tradotto identico all'italiano".
     */
    public function translationFor(string $locale, string $field): mixed
    {
        $all = $this->translations;

        if (! is_array($all)) {
            return null;
        }

        return $all[$locale][$field] ?? null;
    }

    /**
     * Salva le traduzioni di una lingua, preservando le altre.
     *
     * I valori vuoti vengono RIMOSSI invece di salvati come stringa vuota: così
     * "campo svuotato dall'admin" significa "torna all'italiano", e la colonna
     * non si riempie di stringhe inutili.
     *
     * @param  array<string, mixed>  $values  campo => traduzione
     */
    public function setTranslations(string $locale, array $values): void
    {
        $all = is_array($this->translations) ? $this->translations : [];
        $current = $all[$locale] ?? [];

        foreach ($values as $field => $value) {
            if (! in_array($field, $this->translatableFields(), true)) {
                continue; // campo non traducibile: ignorato
            }

            $isEmpty = is_array($value)
                ? empty(array_filter($value, fn ($v) => trim((string) $v) !== ''))
                : trim((string) $value) === '';

            if ($isEmpty) {
                unset($current[$field]);

                continue;
            }

            if (is_array($value)) {
                // Liste (incluso/escluso): l'indice conta, perché ogni riga
                // corrisponde alla voce italiana nella stessa posizione. Una
                // riga vuota resta vuota (= usa l'italiano per QUELLA voce)
                // invece di far scalare le successive.
                $current[$field] = array_map(fn ($v) => trim((string) $v), $value);
            } else {
                $current[$field] = trim((string) $value);
            }
        }

        if ($current === []) {
            unset($all[$locale]);
        } else {
            $all[$locale] = $current;
        }

        $this->translations = $all === [] ? null : $all;
    }

    /**
     * Quanti campi traducibili sono già tradotti in una lingua, su quanti
     * ne esistono di valorizzati in italiano. Serve all'indicatore di
     * completamento nell'admin ("3/7 tradotti").
     *
     * @return array{done: int, total: int}
     */
    public function translationProgress(string $locale): array
    {
        $done = 0;
        $total = 0;

        foreach ($this->translatableFields() as $field) {
            $original = $this->getAttribute($field);

            // Campi vuoti in italiano non sono da tradurre: non contano.
            $hasOriginal = is_array($original) ? $original !== [] : trim((string) $original) !== '';
            if (! $hasOriginal) {
                continue;
            }

            $total++;

            $translated = $this->translationFor($locale, $field);
            $hasTranslation = is_array($translated)
                ? $translated !== []
                : trim((string) $translated) !== '';

            if ($hasTranslation) {
                $done++;
            }
        }

        return ['done' => $done, 'total' => $total];
    }
}
