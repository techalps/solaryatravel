<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use App\Support\B2bContext;
use Barryvdh\DomPDF\Facade\Pdf;
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

    /**
     * Pagina "Widget": codice agenzia (token) + snippet <iframe> pronto da
     * incollare sul sito dell'agenzia. Stesso meccanismo del link referral
     * (attribuzione via ?ref=TOKEN), incorporato direttamente nel sito.
     */
    public function widget(): View
    {
        $agency = B2bContext::actingAgency();
        $token = $agency->ensureReferralToken();

        // Base URL del SITO PUBBLICO (dove vive il widget), non del portale b2b.
        // Forziamo https se la richiesta corrente è sicura: APP_URL può essere
        // http in locale, ma un iframe http dentro una pagina https sarebbe
        // bloccato dal browser come mixed-content.
        $base = rtrim(config('app.url'), '/');
        if (request()->isSecure() && str_starts_with($base, 'http://')) {
            $base = 'https://'.substr($base, 7);
        }

        return view('b2b.widget', [
            'agency' => $agency,
            'token' => $token,
            'widgetUrl' => $base.'/widget?ref='.$token,
            'embedJsUrl' => $base.'/embed.js',
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
     * Volantino A4 stampabile (PDF) con QR grande e claim, da esporre in vetrina.
     */
    public function flyer(QrCodeService $qr)
    {
        $agency = B2bContext::actingAgency();
        $token = $agency->ensureReferralToken();

        $pdf = Pdf::loadView('pdf.referral-flyer', [
            'agency' => $agency,
            'referralUrl' => $this->referralUrl($token),
            // QR ad alta risoluzione embeddato come data-uri (DomPDF non risolve URL).
            'qrDataUri' => $qr->pngDataUri($this->referralUrl($token), 900, 1),
            // Logo bianco come data-uri (DomPDF non rende gli SVG; usiamo il PNG).
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('solarya-prenota-qui.pdf');
    }

    /**
     * URL referral sul SITO PUBBLICO (host principale), non sul portale b2b.
     * Il cliente prenota sul sito cliente.
     */
    private function referralUrl(string $token): string
    {
        return rtrim(config('app.url'), '/').'/prenota?ref='.$token;
    }

    /** Logo bianco come data-uri PNG per l'embed nel PDF (null se mancante). */
    private function logoDataUri(): ?string
    {
        $path = public_path('images/logo_white.png');
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
