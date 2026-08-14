<?php

namespace App\Support;

use App\Models\Booking;

/**
 * Composizione dei link "click to chat" di WhatsApp (wa.me).
 *
 * Due direzioni, con due numeri diversi:
 *  - adminLink()    → l'operatore scrive al cliente (numero = customer_phone)
 *  - customerLink() → il cliente scrive a Solarya (numero = whatsapp_number nelle impostazioni)
 *
 * Nessuna API e nessun costo: sono semplici URL che aprono l'app o WhatsApp Web
 * con il testo già precompilato.
 */
class WhatsApp
{
    /** Prefissi telefonici internazionali dei paesi da cui arrivano i clienti. */
    private const DIALING_CODES = [
        'IT' => '39', 'FR' => '33', 'DE' => '49', 'ES' => '34', 'GB' => '44',
        'CH' => '41', 'AT' => '43', 'BE' => '32', 'NL' => '31', 'PT' => '351',
        'IE' => '353', 'DK' => '45', 'SE' => '46', 'NO' => '47', 'FI' => '358',
        'PL' => '48', 'CZ' => '420', 'SK' => '421', 'HU' => '36', 'RO' => '40',
        'BG' => '359', 'GR' => '30', 'HR' => '385', 'SI' => '386', 'MT' => '356',
        'LU' => '352', 'EE' => '372', 'LV' => '371', 'LT' => '370', 'CY' => '357',
        'US' => '1', 'CA' => '1', 'BR' => '55', 'AR' => '54', 'MX' => '52',
        'AU' => '61', 'NZ' => '64', 'JP' => '81', 'CN' => '86', 'IN' => '91',
        'RU' => '7', 'UA' => '380', 'TR' => '90', 'IL' => '972', 'AE' => '971',
        'ZA' => '27', 'EG' => '20', 'MA' => '212', 'TN' => '216', 'SM' => '378',
    ];

    /** Prefisso usato quando il numero è locale e il paese non è noto. */
    private const DEFAULT_DIALING_CODE = '39';

    /**
     * Numero WhatsApp storico del sito, usato quando l'impostazione è vuota.
     *
     * È lo stesso numero cablato nelle viste pubbliche (header, footer, bottone
     * flottante, pagine tour): il pulsante sulle prenotazioni deve funzionare
     * come quelli, senza richiedere una configurazione aggiuntiva. Impostando
     * `whatsapp_number` dall'admin si sovrascrive questo valore.
     */
    private const FALLBACK_BUSINESS_NUMBER = '+39 345 088 4743';

    /**
     * Normalizza un numero di telefono in formato wa.me (solo cifre, con
     * prefisso internazionale e senza "+").
     *
     * Il campo customer_phone è a testo libero: arriva scritto in ogni modo
     * ("+39 345 088 4743", "0039345...", "345.088.4743"). Qui si ripulisce e,
     * se manca il prefisso internazionale, lo si deduce dal paese del cliente.
     *
     * @param  string|null  $phone   Numero così come inserito dall'utente
     * @param  string|null  $country Codice ISO-2 del paese (es. "FR")
     * @return string|null  Numero pronto per wa.me, oppure null se inutilizzabile
     */
    public static function normalizeNumber(?string $phone, ?string $country = null): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        // "0039..." e "+39..." indicano già il prefisso internazionale.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (! $hasPlus) {
            // Numero locale: si antepone il prefisso del paese del cliente.
            // Lo zero iniziale dei fissi italiani non va nel formato internazionale.
            $digits = ltrim($digits, '0');

            if ($digits === '') {
                return null;
            }

            $digits = self::dialingCode($country).$digits;
        }

        // Un numero internazionale valido sta fra le 8 e le 15 cifre (E.164).
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        // Il solo limite E.164 non basta: con un prefisso lungo (es. +351, +380)
        // resterebbero validi numeri di 4-5 cifre, che non esistono. La parte
        // nazionale deve avere almeno 7 cifre: i cellulari italiani ne hanno 9-10,
        // i fissi più corti 8, e sotto le 7 si tratta di dati incompleti o di prova.
        //
        // Si cerca il prefisso PIÙ LUNGO che combacia: "39..." combacia sia con
        // "3" (nessun paese) sia con "39", e fermarsi al primo match darebbe una
        // parte nazionale più lunga del reale, lasciando passare numeri finti.
        $matched = '';
        foreach (self::DIALING_CODES as $code) {
            if (str_starts_with($digits, $code) && strlen($code) > strlen($matched)) {
                $matched = $code;
            }
        }

        if ($matched !== '') {
            return strlen($digits) - strlen($matched) >= 7 ? $digits : null;
        }

        // Prefisso non riconosciuto: si accetta comunque (la tabella non copre
        // tutti i paesi del mondo) purché la lunghezza complessiva sia plausibile.
        return strlen($digits) >= 10 ? $digits : null;
    }

    /** Prefisso internazionale del paese, con fallback sull'Italia. */
    public static function dialingCode(?string $country): string
    {
        $code = strtoupper(trim((string) $country));

        return self::DIALING_CODES[$code] ?? self::DEFAULT_DIALING_CODE;
    }

    /**
     * Numero WhatsApp di Solarya, dalle impostazioni con fallback sul numero
     * storico del sito. Serve per i link in cui è il cliente a scrivere a noi.
     */
    public static function businessNumber(): ?string
    {
        $configured = trim((string) Settings::get('whatsapp_number', ''));

        return self::normalizeNumber(
            $configured !== '' ? $configured : self::FALLBACK_BUSINESS_NUMBER,
            'IT'
        );
    }

    /** Link wa.me generico verso un numero, con testo precompilato. */
    public static function link(?string $number, string $message = ''): ?string
    {
        if (! $number) {
            return null;
        }

        $url = 'https://wa.me/'.$number;

        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    /**
     * Link per l'operatore: apre la chat col cliente della prenotazione,
     * con un messaggio che cita già numero prenotazione, tour e data.
     */
    public static function adminLink(Booking $booking): ?string
    {
        $number = self::normalizeNumber($booking->customer_phone, $booking->customer_country);

        return self::link($number, self::adminMessage($booking));
    }

    /**
     * Link per il cliente: apre la chat con Solarya, con un messaggio che
     * identifica la prenotazione così lo staff sa subito di cosa si parla.
     */
    public static function customerLink(Booking $booking, ?string $locale = null): ?string
    {
        return self::link(self::businessNumber(), self::customerMessage($booking, $locale));
    }

    /** Testo precompilato del messaggio operatore → cliente. */
    public static function adminMessage(Booking $booking): string
    {
        return __('whatsapp.admin_message', [
            'name' => $booking->customer_first_name,
            'booking' => $booking->booking_number,
            'tour' => self::tourName($booking),
            'date' => self::departureDate($booking),
        ], 'it');
    }

    /**
     * Testo precompilato del messaggio cliente → Solarya, nella lingua
     * della prenotazione (o in quella passata esplicitamente).
     */
    public static function customerMessage(Booking $booking, ?string $locale = null): string
    {
        return __('whatsapp.customer_message', [
            'name' => trim($booking->customer_first_name.' '.$booking->customer_last_name),
            'booking' => $booking->booking_number,
            'tour' => self::tourName($booking),
            'date' => self::departureDate($booking),
        ], $locale ?: $booking->locale ?: app()->getLocale());
    }

    /** Nome del tour, con fallback se la relazione non è caricata. */
    private static function tourName(Booking $booking): string
    {
        $tour = $booking->tour;

        return $tour ? (string) tdb($tour, 'name') : '—';
    }

    /**
     * Data della partenza in formato leggibile. Si usa la data della partenza
     * se disponibile, altrimenti la booking_date della prenotazione.
     */
    private static function departureDate(Booking $booking): string
    {
        $date = $booking->departure?->departure_date ?? $booking->booking_date;

        return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '—';
    }
}
