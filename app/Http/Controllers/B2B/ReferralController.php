<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use App\Support\B2bContext;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Pagina "Link & QR" del Portale Agenzie (Flusso B).
 *
 * Ogni agenzia ha un referral_token: il link solaryatravel.com/prenota?ref=TOKEN
 * (e il relativo QR) si dà al cliente, che prenota in autonomia sul sito. La
 * prenotazione risultante è attribuita all'agenzia (b2b_referral), solo per
 * quella prenotazione.
 */
class ReferralController extends Controller
{
    /** Pagina con link + QR scaricabile dell'agenzia effettiva. */
    public function index(): View
    {
        $agency = B2bContext::actingAgency();
        $token = $agency->ensureReferralToken();

        return view('b2b.referral', [
            'agency' => $agency,
            'referralUrl' => $this->referralUrl($token),
        ]);
    }

    /** PNG del QR code del link referral (per anteprima e download). */
    public function qr(QrCodeService $qr): Response
    {
        $agency = B2bContext::actingAgency();
        $token = $agency->ensureReferralToken();

        $png = $qr->png($this->referralUrl($token), 600);

        return response($png)->withHeaders([
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="referral-'.$token.'.png"',
        ]);
    }

    /**
     * URL referral sul SITO PUBBLICO (host principale), non sul portale b2b.
     * Il cliente prenota sul sito cliente.
     */
    private function referralUrl(string $token): string
    {
        return rtrim(config('app.url'), '/').'/prenota?ref='.$token;
    }
}
