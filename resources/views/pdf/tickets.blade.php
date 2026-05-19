<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Biglietti · {{ $booking->booking_number }}</title>
<style>
    @page { margin: 18mm 14mm; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        color: #0f172a;
        font-size: 11pt;
        margin: 0;
    }
    .header {
        border-bottom: 2px solid #0066cc;
        padding-bottom: 10px;
        margin-bottom: 18px;
    }
    .header .brand { font-size: 9pt; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; }
    .header .title { font-size: 18pt; font-weight: 800; color: #0066cc; margin-top: 4px; }
    .header .meta { font-size: 9pt; color: #475569; margin-top: 6px; }

    .info-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 18px;
    }
    .info-card table { width: 100%; border-collapse: collapse; }
    .info-card td { padding: 4px 0; font-size: 10pt; }
    .info-card td.label { color: #64748b; width: 32%; }
    .info-card td.value { font-weight: 600; }

    .ticket {
        page-break-inside: avoid;
        border: 2px dashed #0066cc;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 16px;
        background: #fdfefe;
    }
    .ticket-table { width: 100%; border-collapse: collapse; }
    .ticket-table td { vertical-align: middle; }
    .ticket-left  { width: 60%; padding-right: 12px; }
    .ticket-right { width: 40%; text-align: center; border-left: 2px dashed #cbd5e1; padding-left: 12px; }

    .ticket-eyebrow { font-size: 8pt; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; font-weight: 700; }
    .ticket-name { font-size: 16pt; font-weight: 800; color: #0066cc; margin-top: 2px; }
    .ticket-bracket {
        display: inline-block;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 5px;
        padding: 2px 8px;
        font-size: 9pt;
        color: #475569;
        margin-top: 6px;
    }
    .ticket-divider { border-top: 1px solid #e5e7eb; margin: 10px 0; }
    .ticket-row { margin-top: 6px; }
    .ticket-label { font-size: 8pt; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; font-weight: 700; }
    .ticket-value { font-size: 10pt; font-weight: 700; color: #0f172a; margin-top: 1px; }

    .qr { width: 160px; height: 160px; background: #ffffff; padding: 6px; border: 1px solid #e5e7eb; border-radius: 6px; }
    .qr-code-text {
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 8pt;
        color: #475569;
        margin-top: 6px;
        word-break: break-all;
    }

    .footer-note {
        margin-top: 8px;
        background: #fef3c7;
        border-left: 3px solid #f59e0b;
        padding: 10px 12px;
        font-size: 9pt;
        color: #78350f;
        border-radius: 4px;
    }
</style>
</head>
<body>

<div class="header">
    <div class="brand">{{ config('app.name') }}</div>
    <div class="title">I tuoi biglietti</div>
    <div class="meta">Prenotazione #{{ $booking->booking_number }} · emessa il {{ now()->format('d/m/Y H:i') }}</div>
</div>

<div class="info-card">
    <table>
        <tr>
            <td class="label">Tour</td>
            <td class="value">{{ $booking->tour->name ?? '—' }}</td>
        </tr>
        @if($booking->departure)
            <tr>
                <td class="label">Data partenza</td>
                <td class="value">{{ \Carbon\Carbon::parse($booking->departure->departure_date)->locale('it')->isoFormat('dddd D MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td class="label">Orario</td>
                <td class="value">{{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Intestatario</td>
            <td class="value">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</td>
        </tr>
        <tr>
            <td class="label">Passeggeri</td>
            <td class="value">{{ $booking->seatRecords->count() }}</td>
        </tr>
    </table>
</div>

@foreach($tickets as $t)
    @php($seat = $t['seat'])
    <div class="ticket">
        <table class="ticket-table">
            <tr>
                <td class="ticket-left">
                    <div class="ticket-eyebrow">Passeggero</div>
                    <div class="ticket-name">
                        {{ $seat->guest_full_name ?: $booking->customer_full_name }}
                    </div>
                    @if($seat->ageBracket)
                        <div class="ticket-bracket">{{ $seat->ageBracket->label }}</div>
                    @endif

                    <div class="ticket-divider"></div>

                    <div class="ticket-row">
                        <div class="ticket-label">Posto</div>
                        <div class="ticket-value">#{{ $seat->seat_number ?? $loop->iteration }}</div>
                    </div>

                    @if($seat->catamaran)
                        <div class="ticket-row">
                            <div class="ticket-label">Catamarano</div>
                            <div class="ticket-value">{{ $seat->catamaran->name }}</div>
                        </div>
                    @endif

                    @if($seat->is_primary && $seat->tax_code)
                        <div class="ticket-row">
                            <div class="ticket-label">Codice fiscale</div>
                            <div class="ticket-value" style="font-family: 'DejaVu Sans Mono', monospace; font-size: 9pt;">{{ $seat->tax_code }}</div>
                        </div>
                    @endif
                </td>
                <td class="ticket-right">
                    <img class="qr" src="{{ $t['qr_data'] }}" alt="QR biglietto">
                    <div class="qr-code-text">{{ $seat->qr_code }}</div>
                </td>
            </tr>
        </table>
    </div>
@endforeach

<div class="footer-note">
    <strong>Importante:</strong> presentati al molo almeno 15 minuti prima della partenza con questo PDF (stampato o sul cellulare). Il QR di ogni biglietto verrà scansionato singolarmente.
</div>

</body>
</html>
