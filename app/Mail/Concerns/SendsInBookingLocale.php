<?php

namespace App\Mail\Concerns;

use App\Support\Locales;

/**
 * Invia l'email nella lingua scelta dal cliente al momento della prenotazione.
 *
 * La lingua è già salvata su `bookings.locale` (la scrive BookingService dal
 * locale attivo). Prima non veniva mai usata: i template avevano l'italiano
 * scritto dentro, quindi un cliente che prenotava in inglese o in spagnolo
 * riceveva comunque email in italiano.
 *
 * Perché `locale()` di Laravel e non `App::setLocale()` a mano: il Mailable
 * viene reso quando viene spedito — che può essere molto dopo la costruzione,
 * e su una coda, in un processo dove il locale attivo è quello di default.
 * `locale()` è il meccanismo che Laravel applica al momento del render, quindi
 * è l'unico affidabile anche con le code.
 *
 * Le email agli ADMIN restano in italiano: chi le legge è lo staff.
 */
trait SendsInBookingLocale
{
    /**
     * Aggancia la lingua della prenotazione al Mailable.
     *
     * Da chiamare nel costruttore. Se la lingua non è fra quelle attive (o è
     * vuota, come sui dati storici) si resta sul default: meglio l'italiano che
     * una lingua senza traduzioni.
     */
    protected function useBookingLocale(?string $locale): void
    {
        $this->locale($this->resolveLocale($locale));
    }

    protected function resolveLocale(?string $locale): string
    {
        $locale = trim((string) $locale);
        $default = (string) config('locales.default', 'it');

        if ($locale === '') {
            return $default;
        }

        // Il confronto è sul CATALOGO, non sulle lingue attive: se il cliente ha
        // prenotato in spagnolo e in seguito lo spagnolo viene disattivato, la
        // sua email deve restare in spagnolo — la prenotazione è già sua.
        $catalogue = array_keys((array) config('locales.names', []));

        return in_array($locale, $catalogue, true) ? $locale : $default;
    }
}
