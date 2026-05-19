<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Tour fra 2 giorni</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#10b981 0%,#0066cc 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">Promemoria · 48 ore</div>
                        <div style="font-size:22px;font-weight:700;margin-top:4px;">Manca poco al tuo tour!</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">Ciao <strong>{{ $booking->customer_first_name }}</strong>,</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            il tuo tour <strong>{{ $booking->tour->name ?? '' }}</strong> è previsto per
                            @if($booking->departure)
                                <strong>{{ \Carbon\Carbon::parse($booking->departure->departure_date)->locale('it')->isoFormat('dddd D MMMM') }} alle {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</strong>.
                            @else
                                il <strong>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</strong>.
                            @endif
                        </p>

                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            Ti ricordiamo di presentarti al molo almeno <strong>15 minuti prima</strong> della partenza con il PDF dei biglietti
                            (in allegato alla mail di conferma) — stampato o sul cellulare. Verrà scansionato il QR di ciascun biglietto al momento dell'imbarco.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;padding:14px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;width:140px;">Prenotazione</td>
                                <td style="padding:4px 0;font-size:14px;font-family:ui-monospace,Menlo,monospace;">#{{ $booking->booking_number }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">Passeggeri</td>
                                <td style="padding:4px 0;font-size:14px;">{{ $booking->seatRecords->count() }}</td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;font-size:13px;color:#94a3b8;line-height:1.6;">
                            Buon viaggio!
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#94a3b8;">
                        © {{ now()->year }} {{ config('app.name') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
