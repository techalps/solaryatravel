<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; height: 100%; }
    body { font-family: 'Helvetica', sans-serif; }

    /* Sfondo pieno a tutta pagina (DomPDF: background-color su body affidabile) */
    body { background-color: #0d3b66; }

    .topband { height: 14mm; background-color: #ffd166; width: 100%; }

    .inner { padding: 14mm 18mm 0 18mm; text-align: center; }

    .brand {
        font-size: 30pt; font-weight: bold; letter-spacing: 3px;
        text-transform: uppercase; color: #ffffff;
    }
    .brand .sea { color: #ffd166; }
    .brand-logo { width: 78mm; height: auto; }
    .tagline {
        font-size: 11pt; letter-spacing: 5px; text-transform: uppercase;
        color: #cfe8ff; margin-top: 4mm;
    }

    .claim {
        margin-top: 16mm;
        font-size: 26pt; font-weight: bold; line-height: 1.3; color: #ffffff;
    }
    .claim .hl { color: #ffd166; }
    .subclaim { margin-top: 6mm; font-size: 13pt; color: #dcefff; }

    /* card bianca con il QR */
    .qrcard {
        width: 96mm; margin: 14mm auto 0 auto;
        background-color: #ffffff; border-radius: 6mm;
        border: 2mm solid #ffd166;
        padding: 8mm 8mm 6mm 8mm;
    }
    .qrcard img { width: 74mm; height: 74mm; display: block; margin: 0 auto; }
    .qrcard .scan {
        margin-top: 5mm; font-size: 13pt; font-weight: bold;
        color: #0d3b66; letter-spacing: 1px; text-transform: uppercase;
    }
    .qrcard .url {
        margin-top: 2mm; font-size: 8.5pt; color: #5b7a99;
    }

    .agencyline { margin-top: 12mm; font-size: 12pt; color: #ffffff; }
    .agencyline strong { color: #ffd166; }

    .footer {
        position: absolute; bottom: 0; left: 0; right: 0;
        padding: 6mm 18mm; text-align: center;
        font-size: 9pt; color: #bcdcff; letter-spacing: 1px;
    }
</style>
</head>
<body>
    <div class="topband"></div>
    <div class="inner">
        @if(!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" alt="Solarya Travel" class="brand-logo">
        @else
            <div class="brand">Solarya<span class="sea"> Travel</span></div>
        @endif
        <div class="tagline">Escursioni in catamarano</div>

        <div class="claim">
            Vivi il mare.<br><span class="hl">Prenota la tua escursione</span>
        </div>
        <div class="subclaim">Inquadra il QR con la fotocamera e prenota in pochi tap.</div>

        <div class="qrcard">
            <img src="{{ $qrDataUri }}" alt="QR prenotazione">
            <div class="scan">Scansiona &amp; prenota</div>
            <div class="url">{{ $referralUrl }}</div>
        </div>

        <div class="agencyline">
            a cura di <strong>{{ $agency->agency_name ?: $agency->name }}</strong>
        </div>
    </div>

    <div class="footer">
        SOLARYA TRAVEL &nbsp;·&nbsp; escursioni e crociere in catamarano &nbsp;·&nbsp; solaryatravel.com
    </div>
</body>
</html>
