<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Rimborso effettuato</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#0ea5e9 0%,#0066cc 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">Rimborso effettuato</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">Ciao <strong>{{ $booking->customer_first_name }}</strong>,</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            ti confermiamo che è stato disposto il rimborso relativo alla prenotazione <strong>#{{ $booking->booking_number }}</strong>.
                            L'accredito sulla carta utilizzata per il pagamento può richiedere fino a 5–10 giorni lavorativi, in base al circuito.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:14px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#0c4a6e;width:160px;">Importo rimborsato</td>
                                <td style="padding:4px 0;font-size:18px;font-weight:700;color:#0c4a6e;">€{{ number_format((float) $amount, 2, ',', '.') }}</td>
                            </tr>
                            @if((float) $booking->penalty_amount > 0)
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#0c4a6e;">Penale di storno</td>
                                <td style="padding:4px 0;font-size:14px;color:#b91c1c;">€{{ number_format((float) $booking->penalty_amount, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#0c4a6e;">Tour</td>
                                <td style="padding:4px 0;font-size:14px;">{{ $booking->tour->name ?? '' }}</td>
                            </tr>
                            @if($booking->departure)
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#0c4a6e;">Data prenotata</td>
                                <td style="padding:4px 0;font-size:14px;">{{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</td>
                            </tr>
                            @endif
                        </table>

                        @if($note)
                            <div style="background:#f8fafc;border-left:4px solid #94a3b8;padding:14px;border-radius:6px;margin-top:6px;">
                                <p style="margin:0 0 6px 0;font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">Nota</p>
                                <p style="margin:0;font-size:14px;color:#334155;line-height:1.6;">{{ $note }}</p>
                            </div>
                        @endif

                        <p style="margin:18px 0 0 0;line-height:1.6;">
                            Per qualsiasi domanda sull'accredito o sui tempi, rispondi pure a questa email.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background:#f8fafc;padding:18px 28px;border-top:1px solid #e5e7eb;font-size:12px;color:#64748b;text-align:center;">
                        © {{ date('Y') }} {{ config('app.name') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
