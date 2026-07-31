<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Criteri UNICI dei report, condivisi da ReportController e ReportExportService.
 *
 * Nasce da un'incoerenza reale: la pagina Ricavi contava per DATA ESCURSIONE con
 * i soli stati che fanno ricavo, mentre il foglio Excel "Prenotazioni" elencava
 * per DATA CREAZIONE includendo anche le ANNULLATE. Sommare la colonna "Venduto"
 * dell'export non tornava mai col totale dei ricavi (nei dati reali: 4.320 € vs
 * 4.000 €, differenza = una prenotazione annullata da 320 €).
 *
 * I criteri convivono, perché rispondono a domande diverse:
 *
 *   - COMPETENZA (bookings.booking_date): "quanto valgono le partenze di questo
 *     periodo". È il criterio dei ricavi.
 *   - RACCOLTA (bookings.created_at): "quanto ho venduto in questo periodo, per
 *     qualunque data di partenza". È il criterio dei conteggi di prenotazioni.
 *   - CASSA (payments.paid_at): "quanti soldi sono ENTRATI in questo periodo".
 *     È l'unico che gestisce correttamente il pagamento rateale: prenoto a
 *     luglio per agosto, acconto a luglio e saldo ad agosto → l'acconto pesa su
 *     luglio e il saldo su agosto. Se il saldo viene anticipato a luglio, cade
 *     in luglio. Gli altri due criteri non possono farlo, perché
 *     `bookings.amount_paid` è un cumulativo senza data.
 *
 * La regola per non riconfondersi: ogni cifra monetaria esclude sempre le
 * annullate/rimborsate (solo stati di ricavo) e ogni riquadro dichiara a schermo
 * il criterio che sta usando. Le annullate si contano a parte, mai nel venduto.
 * Non sommare MAI numeri di criteri diversi: misurano cose differenti e in
 * periodi diversi contengono le stesse prenotazioni contate una volta sola.
 */
class ReportCriteria
{
    /** Etichette dei tre criteri, da mostrare accanto ai numeri. */
    public const LABEL_COMPETENZA = 'per data escursione';
    public const LABEL_RACCOLTA = 'per data di prenotazione';
    public const LABEL_CASSA = 'per data di incasso';

    /** Spiegazione estesa, per i tooltip/sottotitoli delle colonne. */
    public const HELP_COMPETENZA = 'Valore delle escursioni che PARTONO nel periodo, indipendentemente da quando sono state prenotate o pagate.';
    public const HELP_RACCOLTA = 'Valore di quanto è stato PRENOTATO nel periodo, indipendentemente da quando si parte o si paga.';
    public const HELP_CASSA = 'Denaro ENTRATO davvero nel periodo, rata per rata. Un saldo pagato ad agosto conta ad agosto anche se la prenotazione è di luglio.';

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
     * Blocco di metriche omogeneo per UN criterio, così la vista affiancata
     * confronta mele con mele: stessi stati, stesse formule, cambia solo il
     * campo data (booking_date = competenza, created_at = raccolta).
     *
     * @param 'competenza'|'raccolta' $basis
     * @return array{
     *     basis:string, label:string, help:string, date_field:string,
     *     bookings:int, seats:int, gross:float, collected:float,
     *     outstanding:float, commission:float, net:float, avg:float,
     *     cancelled:int
     * }
     */
    public static function metrics(string $basis, $start, $end): array
    {
        $field = $basis === 'raccolta' ? 'created_at' : 'booking_date';

        $row = Booking::query()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->whereBetween($field, [$start, $end])
            ->selectRaw('COUNT(*) as bookings, SUM(seats) as seats')
            ->selectRaw('SUM(total_amount) as gross, SUM(amount_paid) as collected')
            ->selectRaw('SUM(COALESCE(commission_amount, 0)) as commission')
            ->first();

        // Le annullate si contano a parte, mai nel venduto (vedi nota in testa).
        $cancelled = Booking::query()
            ->whereIn('status', [BookingStatus::CANCELLED, BookingStatus::REFUNDED])
            ->whereBetween($field, [$start, $end])
            ->count();

        $bookings = (int) ($row->bookings ?? 0);
        $gross = (float) ($row->gross ?? 0);
        $collected = (float) ($row->collected ?? 0);
        $commission = (float) ($row->commission ?? 0);

        return [
            'basis' => $basis,
            'label' => $basis === 'raccolta' ? self::LABEL_RACCOLTA : self::LABEL_COMPETENZA,
            'help' => $basis === 'raccolta' ? self::HELP_RACCOLTA : self::HELP_COMPETENZA,
            'date_field' => $field,
            'bookings' => $bookings,
            'seats' => (int) ($row->seats ?? 0),
            'gross' => $gross,
            'collected' => $collected,
            'outstanding' => max(0, $gross - $collected),
            'commission' => $commission,
            'net' => $gross - $commission,
            'avg' => $bookings > 0 ? $gross / $bookings : 0.0,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * CASSA: denaro effettivamente entrato nel periodo, rata per rata.
     *
     * Perché serve un terzo criterio. Competenza e raccolta guardano entrambe
     * la PRENOTAZIONE, e `bookings.amount_paid` è un totale cumulativo SENZA
     * data: non sa dire quanto è entrato a luglio e quanto ad agosto. Con una
     * prenotazione di luglio per agosto, acconto a luglio e saldo ad agosto,
     * qualunque criterio basato sulla prenotazione mette tutto nello stesso
     * mese. La data esiste solo su `payments.paid_at`, una riga per rata: è lì
     * che va calcolato l'incassato. Se il saldo viene anticipato a luglio,
     * cade automaticamente in luglio — senza alcun caso particolare.
     *
     * I rimborsi si scalano dal mese in cui sono stati EROGATI (refunded_at),
     * così il netto di cassa è quello vero.
     *
     * Nota sui dati: le prenotazioni incassate fuori sistema e mai registrate
     * (nessuna riga in `payments`) qui valgono zero — correttamente, perché a
     * sistema quel denaro non risulta. Sono esposte a parte da
     * unregisteredCollections(), per non nasconderle dietro un totale.
     *
     * @return array{
     *     basis:string, label:string, help:string,
     *     gross_in:float, refunds:float, net:float,
     *     payments:int, by_gateway:\Illuminate\Support\Collection
     * }
     */
    public static function cash($start, $end): array
    {
        $in = Payment::query()
            ->where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(amount), 0) as total')
            ->first();

        $refunds = (float) Payment::query()
            ->where('status', PaymentStatus::REFUNDED)
            ->whereBetween('refunded_at', [$start, $end])
            ->sum(DB::raw('COALESCE(refunded_amount, amount)'));

        $byGateway = Payment::query()
            ->where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('gateway, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('gateway')
            ->orderByDesc('total')
            ->get();

        $grossIn = (float) ($in->total ?? 0);

        return [
            'basis' => 'cassa',
            'label' => self::LABEL_CASSA,
            'help' => self::HELP_CASSA,
            'gross_in' => $grossIn,
            'refunds' => $refunds,
            'net' => $grossIn - $refunds,
            'payments' => (int) ($in->c ?? 0),
            'by_gateway' => $byGateway,
        ];
    }

    /**
     * Le tre viste affiancate. È la struttura che alimenta i riquadri:
     * mai un numero senza il suo criterio dichiarato.
     *
     * @return array{competenza:array, raccolta:array, cassa:array}
     */
    public static function bothViews($start, $end): array
    {
        return [
            'competenza' => self::metrics('competenza', $start, $end),
            'raccolta' => self::metrics('raccolta', $start, $end),
            'cassa' => self::cash($start, $end),
        ];
    }

    /**
     * Prenotazioni valide con incasso dichiarato (amount_paid > 0) ma NESSUN
     * pagamento registrato: denaro che il gestionale dà per incassato senza
     * poterlo datare, quindi invisibile alla vista di cassa. Vanno sanate
     * registrando il pagamento, non nascoste in un totale.
     *
     * @return array{count:int, amount:float}
     */
    public static function unregisteredCollections($start, $end): array
    {
        $row = Booking::query()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->whereBetween('booking_date', [$start, $end])
            ->where('amount_paid', '>', 0)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', PaymentStatus::SUCCEEDED))
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(amount_paid), 0) as amount')
            ->first();

        return [
            'count' => (int) ($row->c ?? 0),
            'amount' => (float) ($row->amount ?? 0),
        ];
    }

    /**
     * Scomposizione del "da incassare" sulle partenze GIÀ EFFETTUATE.
     *
     * Nasce da un caso reale: a fine luglio il report mostrava 17.340 € "da
     * incassare" su 120 partenze tutte concluse. Sommare tutto in un'unica voce
     * confondeva due situazioni molto diverse:
     *
     *   - saldi aperti: acconto incassato, saldo mai versato (credito vero);
     *   - nessun pagamento registrato: viaggio erogato senza alcun incasso a
     *     sistema, tipicamente contanti in banchina mai registrati dall'admin.
     *
     * @return array{
     *     partial: array{count:int, amount:float},
     *     unpaid: array{count:int, amount:float},
     *     total: array{count:int, amount:float}
     * }
     */
    public static function outstandingBreakdown($start, $end): array
    {
        $rows = Booking::query()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->whereBetween('booking_date', [$start, $end])
            ->whereRaw('total_amount - COALESCE(amount_paid, 0) > 0')
            ->selectRaw('COALESCE(amount_paid, 0) = 0 as nothing_paid')
            ->selectRaw('COUNT(*) as c, SUM(total_amount - COALESCE(amount_paid, 0)) as amount')
            ->groupBy('nothing_paid')
            ->get()
            ->keyBy(fn ($r) => ((int) $r->nothing_paid) === 1 ? 'unpaid' : 'partial');

        $pick = fn (?object $r) => [
            'count' => (int) ($r->c ?? 0),
            'amount' => (float) ($r->amount ?? 0),
        ];

        $partial = $pick($rows['partial'] ?? null);
        $unpaid = $pick($rows['unpaid'] ?? null);

        return [
            'partial' => $partial,
            'unpaid' => $unpaid,
            'total' => [
                'count' => $partial['count'] + $unpaid['count'],
                'amount' => $partial['amount'] + $unpaid['amount'],
            ],
        ];
    }

    /**
     * Prenotazioni con partenza già avvenuta e residuo aperto: l'anomalia da
     * sanare (o il denaro incassato e non registrato). Oggi non le vede nessuno.
     *
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    public static function pastDepartureOutstanding($start, $end, int $limit = 50)
    {
        return Booking::query()
            ->whereIn('status', BookingStatus::revenueStatusValues())
            ->whereBetween('booking_date', [$start, $end])
            ->whereDate('booking_date', '<', now()->toDateString())
            ->whereRaw('total_amount - COALESCE(amount_paid, 0) > 0')
            ->orderByDesc('booking_date')
            ->limit($limit)
            ->get();
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
