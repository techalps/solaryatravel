<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;

/**
 * Criteri UNICI dei report, condivisi da ReportController e ReportExportService.
 *
 * Nasce da un'incoerenza reale: la pagina Ricavi contava per DATA ESCURSIONE con
 * i soli stati che fanno ricavo, mentre il foglio Excel "Prenotazioni" elencava
 * per DATA CREAZIONE includendo anche le ANNULLATE. Sommare la colonna "Venduto"
 * dell'export non tornava mai col totale dei ricavi (nei dati reali: 4.320 € vs
 * 4.000 €, differenza = una prenotazione annullata da 320 €).
 *
 * I due criteri restano entrambi, perché rispondono a domande diverse:
 *
 *   - COMPETENZA (booking_date): "quanto valgono le partenze di questo periodo".
 *     È il criterio dei ricavi.
 *   - RACCOLTA (created_at): "quanto ho venduto in questo periodo, per qualunque
 *     data di partenza". È il criterio dei conteggi di prenotazioni.
 *
 * La regola per non riconfondersi: ogni cifra monetaria esclude sempre le
 * annullate/rimborsate (solo stati di ricavo) e ogni riquadro dichiara a schermo
 * il criterio che sta usando. Le annullate si contano a parte, mai nel venduto.
 */
class ReportCriteria
{
    /** Etichette dei due criteri, da mostrare accanto ai numeri. */
    public const LABEL_COMPETENZA = 'per data escursione';
    public const LABEL_RACCOLTA = 'per data di creazione';

    /**
     * Prenotazioni che fanno RICAVO con partenza nel periodo (competenza).
     * Esclude annullate, rimborsate e no-show.
     */
    public static function revenue($start, $end): Builder
    {
        return Booking::query()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->whereBetween('booking_date', [$start, $end]);
    }

    /**
     * Prenotazioni CREATE nel periodo che fanno ricavo (raccolta).
     * Stessi stati della competenza: così anche qui il venduto non include mai
     * le annullate.
     */
    public static function collected($start, $end): Builder
    {
        return Booking::query()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Tutte le prenotazioni create nel periodo, QUALUNQUE stato: serve ai
     * conteggi (quante ne sono arrivate, quante annullate) e all'elenco di
     * dettaglio. Non usare per sommare denaro.
     */
    public static function created($start, $end): Builder
    {
        return Booking::query()->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Ripartizione diretto vs agenzie sulle prenotazioni che fanno ricavo nel
     * periodo (competenza). Le commissioni B2B non comparivano in nessun report:
     * senza di esse il "venduto" sovrastima quanto resta effettivamente a Solarya.
     *
     * @return array{
     *     direct: array{bookings:int, seats:int, gross:float, collected:float},
     *     agency: array{bookings:int, seats:int, gross:float, collected:float, commission:float},
     *     total: array{bookings:int, seats:int, gross:float, collected:float, commission:float, net:float}
     * }
     */
    public static function channelBreakdown($start, $end): array
    {
        $rows = self::revenue($start, $end)
            ->selectRaw('b2b_user_id IS NOT NULL as is_agency')
            ->selectRaw('COUNT(*) as bookings, SUM(seats) as seats, SUM(total_amount) as gross, SUM(amount_paid) as collected, SUM(COALESCE(commission_amount, 0)) as commission')
            ->groupBy('is_agency')
            ->get()
            ->keyBy(fn ($r) => ((int) $r->is_agency) === 1 ? 'agency' : 'direct');

        $pick = fn (?object $r) => [
            'bookings' => (int) ($r->bookings ?? 0),
            'seats' => (int) ($r->seats ?? 0),
            'gross' => (float) ($r->gross ?? 0),
            'collected' => (float) ($r->collected ?? 0),
            'commission' => (float) ($r->commission ?? 0),
        ];

        $direct = $pick($rows['direct'] ?? null);
        $agency = $pick($rows['agency'] ?? null);

        // Sul diretto la commissione non si applica: azzerata per non confondere.
        $direct['commission'] = 0.0;

        return [
            'direct' => $direct,
            'agency' => $agency,
            'total' => [
                'bookings' => $direct['bookings'] + $agency['bookings'],
                'seats' => $direct['seats'] + $agency['seats'],
                'gross' => $direct['gross'] + $agency['gross'],
                'collected' => $direct['collected'] + $agency['collected'],
                'commission' => $agency['commission'],
                // Netto = venduto meno le provvigioni riconosciute alle agenzie.
                'net' => $direct['gross'] + $agency['gross'] - $agency['commission'],
            ],
        ];
    }
}
