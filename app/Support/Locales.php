<?php

namespace App\Support;

/**
 * Lingue del sito pubblico.
 *
 * L'elenco delle lingue ATTIVE è deciso dal cliente in admin → Impostazioni e
 * salvato in settings.json; config/locales.php resta il catalogo di quelle
 * disponibili (etichette, bandiere, og:locale) e il default tecnico.
 *
 * Punto unico: lo usano il middleware SetLocale, gli helper di routing, lo
 * switcher e i form di traduzione. Così "quali lingue esistono" ha una sola
 * risposta in tutto il progetto.
 */
class Locales
{
    /** Lingua di default: mai disattivabile, è la sorgente di fallback. */
    public static function default(): string
    {
        return (string) config('locales.default', 'it');
    }

    /**
     * Lingue attive sul sito, sempre con la default in testa.
     *
     * @return array<int, string>
     */
    public static function active(): array
    {
        $available = self::available();
        $default = self::default();

        $enabled = Settings::get('active_locales');

        // Nessuna impostazione salvata: valgono quelle dichiarate in config
        // (comportamento precedente, così il sito non cambia da solo).
        if (! is_array($enabled) || $enabled === []) {
            $enabled = (array) config('locales.supported', [$default]);
        }

        // Solo lingue previste dal catalogo, senza duplicati, default in testa.
        $enabled = array_values(array_intersect($enabled, $available));
        $enabled = array_values(array_unique(array_merge([$default], $enabled)));

        return $enabled;
    }

    /**
     * Tutte le lingue selezionabili in admin (catalogo).
     *
     * @return array<int, string>
     */
    public static function available(): array
    {
        return array_keys((array) config('locales.names', []));
    }

    /** Lingue attive diverse da quella di default (quelle da tradurre). */
    public static function translatable(): array
    {
        return array_values(array_diff(self::active(), [self::default()]));
    }

    public static function isActive(string $locale): bool
    {
        return in_array($locale, self::active(), true);
    }

    /** Nome leggibile della lingua ("Italiano", "English"). */
    public static function name(string $locale): string
    {
        return (string) config('locales.names.'.$locale, strtoupper($locale));
    }

    /** Sigla breve per lo switcher ("IT", "EN"). */
    public static function short(string $locale): string
    {
        return (string) config('locales.short.'.$locale, strtoupper($locale));
    }

    /** Codice bandiera SVG associato alla lingua (vedi partials/public/flags). */
    public static function flag(string $locale): ?string
    {
        $flag = config('locales.flags.'.$locale);

        return is_string($flag) && $flag !== '' ? $flag : null;
    }

    /**
     * hreflang="x-default": la lingua indicata in config, purché attiva;
     * altrimenti la default.
     */
    public static function xDefault(): string
    {
        $x = (string) config('locales.x_default', 'en');

        return self::isActive($x) ? $x : self::default();
    }
}
