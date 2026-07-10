<?php

namespace App\Http\Controllers;

use App\Support\Geo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint pubblici per le select a cascata del luogo di emissione documento
 * (Stato → Provincia → Comune). Serviti come JSON con cache HTTP forte: i
 * dataset sono statici e cambiano solo con un rilascio.
 */
class GeoController extends Controller
{
    /** Comuni di una provincia italiana (per sigla, es. TO). */
    public function comuni(Request $request, string $sigla): JsonResponse
    {
        $comuni = Geo::comuniByProvince($sigla);

        return response()
            ->json(['sigla' => strtoupper($sigla), 'comuni' => $comuni])
            ->setPublic()
            ->setMaxAge(86400)
            ->setSharedMaxAge(86400);
    }
}
