<?php

/*
|--------------------------------------------------------------------------
| Etichette delle fasce stagionali (periodi tariffari)
|--------------------------------------------------------------------------
|
| Le fasce stagionali sono generate da date a DB e l'etichetta inserita
| dall'admin è tipicamente il nome del mese in italiano ("Giugno", "Luglio").
| Quei nomi NON vanno nel dizionario db.php: si rendono con Carbon nel locale
| attivo, così ogni stagione futura funziona senza aggiungere voci a mano.
|
| Autoloadato via composer.json ("autoload.files").
|
*/

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

if (! function_exists('season_label')) {
    /**
     * Etichetta di un periodo tariffario nel locale attivo.
     *
     * Se l'etichetta a DB è (solo) un nome di mese italiano — il caso normale,
     * perché le stagioni sono mensili — viene rigenerata da Carbon sulla data
     * di inizio: "Giugno" → "June". Un'etichetta descrittiva diversa
     * ("Alta stagione") passa invece dal dizionario db.php via tdb().
     */
    function season_label(?string $label, CarbonInterface|string|null $from = null): string
    {
        $label = $label !== null ? trim($label) : '';

        if ($label === '') {
            return $from !== null ? season_month_name($from) : '';
        }

        // Etichetta = nome di mese italiano? Allora è derivata dalle date:
        // la rendiamo con Carbon invece di cercarla nel dizionario.
        if ($from !== null && season_is_italian_month($label)) {
            return season_month_name($from);
        }

        return (string) tdb($label);
    }
}

if (! function_exists('season_month_name')) {
    /**
     * Nome del mese di una data, nel locale attivo e con l'iniziale maiuscola
     * (in inglese Carbon la restituisce già maiuscola).
     */
    function season_month_name(CarbonInterface|string $date): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        $name = $date->copy()->locale(app()->getLocale())->isoFormat('MMMM');

        return Str::ucfirst($name);
    }
}

if (! function_exists('season_is_italian_month')) {
    /**
     * Vero se la stringa è (solo) un nome di mese italiano.
     */
    function season_is_italian_month(string $label): bool
    {
        static $months = [
            'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
            'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre',
        ];

        return in_array(mb_strtolower(trim($label)), $months, true);
    }
}
