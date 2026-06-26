<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Canale B2B — referral (Flusso B), lato sito pubblico.
 *
 * Se una richiesta arriva con ?ref=TOKEN e il token corrisponde a un'agenzia b2b,
 * salva l'id agenzia in un cookie breve (48h). Alla creazione di una prenotazione
 * sul sito, BookingForm legge questo cookie e attribuisce la vendita all'agenzia
 * (solo per quella prenotazione, non a vita).
 *
 * Modifica ADDITIVA: non altera il flusso esistente: in assenza di ?ref non fa
 * nulla. È l'unico punto in cui il canale B2B tocca il sito cliente.
 */
class CaptureReferralMiddleware
{
    /** Nome del cookie e durata (minuti): 48h, coerente con "solo questa visita". */
    public const COOKIE = 'b2b_ref';
    private const TTL_MINUTES = 48 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('ref');

        if (is_string($token) && $token !== '') {
            $agencyId = User::where('role', 'b2b')
                ->where('referral_token', $token)
                ->value('id');

            if ($agencyId) {
                // Coda il cookie sulla response (firmato/criptato da Laravel di default).
                Cookie::queue(Cookie::make(self::COOKIE, (string) $agencyId, self::TTL_MINUTES));
                $request->attributes->set('b2b_ref_agency_id', $agencyId);
            }
        }

        return $next($request);
    }
}
