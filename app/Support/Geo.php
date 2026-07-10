<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Accesso ai dataset geografici statici (resources/data): stati del mondo,
 * province italiane e comuni ISTAT. Usati per il luogo di emissione dei
 * documenti d'identità dei passeggeri.
 *
 * I file sono caricati una volta e tenuti in cache in-memory per request; i
 * comuni (110 KB) sono anche in cache applicativa perché usati raramente.
 */
class Geo
{
    private static ?array $countries = null;
    private static ?array $provinces = null;
    private static ?array $comuni = null;

    /** @return array<int,array{code:string,name:string}> */
    public static function countries(): array
    {
        return self::$countries ??= self::load('countries.json');
    }

    /** @return array<int,array{sigla:string,name:string}> */
    public static function provinces(): array
    {
        return self::$provinces ??= self::load('provinces.json');
    }

    /**
     * Mappa sigla-provincia => elenco comuni.
     *
     * @return array<string,array<int,string>>
     */
    public static function comuni(): array
    {
        return self::$comuni ??= Cache::rememberForever('geo.comuni', fn () => self::load('comuni.json'));
    }

    /** Comuni di una singola provincia (per la select a cascata). */
    public static function comuniByProvince(string $sigla): array
    {
        return self::comuni()[strtoupper($sigla)] ?? [];
    }

    /** Nome esteso di uno stato dal codice ISO-2 (es. IT => Italia). */
    public static function countryName(?string $code): ?string
    {
        if (!$code) {
            return null;
        }
        foreach (self::countries() as $c) {
            if ($c['code'] === strtoupper($code)) {
                return $c['name'];
            }
        }

        return $code;
    }

    /** Nome esteso di una provincia dalla sigla (es. TO => Torino). */
    public static function provinceName(?string $sigla): ?string
    {
        if (!$sigla) {
            return null;
        }
        foreach (self::provinces() as $p) {
            if ($p['sigla'] === strtoupper($sigla)) {
                return $p['name'];
            }
        }

        return $sigla;
    }

    private static function load(string $file): array
    {
        $path = resource_path('data/'.$file);

        return is_file($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    }
}
