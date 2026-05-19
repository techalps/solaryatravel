<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Restituisce i byte PNG del QR per il contenuto dato.
     */
    public function png(string $data, int $size = 300, int $margin = 10): string
    {
        return (new Builder(
            writer: new PngWriter(),
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: $margin,
        ))->build()->getString();
    }

    /**
     * Data-URI base64 (utile per <img src="..."> in mail/PDF).
     */
    public function pngDataUri(string $data, int $size = 300, int $margin = 10): string
    {
        return 'data:image/png;base64,' . base64_encode($this->png($data, $size, $margin));
    }
}
