<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Prenotazione annullata</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">Prenotazione annullata</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">Ciao <strong>{{ $booking->customer_first_name }}</strong>,</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            ti confermiamo che la tua prenotazione <strong>#{{ $booking->booking_number }}</strong> è stata annullata.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;padding:14px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;width:140px;">Tour</td>
                                <td style="padding:4px 0;font-size:14px;"><strong>{{ $booking->tour->name ?? '' }}</strong></td>
                            </tr>
                            @if($booking->departure)
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">Data e partenza</td>
                                <td style="padding:4px 0;font-size:14px;">{{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">Annullata il</td>
                                <td style="padding:4px 0;font-size:14px;">{{ optional($booking->cancelled_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</td>
                            </tr>
                        </table>

                        @if($reason)
                            <div style="background:#fef2f2;border-left:4px solid #ef4444;padding:14px;border-radius:6px;margin-top:6px;">
                                <p style="margin:0 0 6px 0;font-size:12px;color:#991b1b;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">Motivazione</p>
                                <p style="margin:0;font-size:14px;color:#7f1d1d;line-height:1.6;">{{ $reason }}</p>
                            </div>
                        @endif

                        @if(!empty($refundCalc) && ($refundCalc['paid'] ?? 0) > 0)
                            <div style="background:#f0f9ff;border:1px solid #bae6fd;padding:16px;border-radius:8px;margin-top:14px;">
                                <p style="margin:0 0 10px 0;font-size:12px;color:#075985;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">Dettaglio rimborso</p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr><td style="padding:3px 0;font-size:14px;color:#475569;">Importo versato</td><td style="padding:3px 0;font-size:14px;text-align:right;">€ {{ number_format((float) $refundCalc['paid'], 2, ',', '.') }}</td></tr>
                                    <tr><td style="padding:3px 0;font-size:14px;color:#475569;">Rimborso ({{ $refundCalc['percentage'] }}%)</td><td style="padding:3px 0;font-size:14px;text-align:right;font-weight:700;color:#059669;">€ {{ number_format((float) $refundCalc['amount'], 2, ',', '.') }}</td></tr>
                                    @if(($refundCalc['penalty'] ?? 0) > 0)
                                    <tr><td style="padding:3px 0;font-size:14px;color:#475569;">Penale trattenuta</td><td style="padding:3px 0;font-size:14px;text-align:right;color:#b91c1c;">€ {{ number_format((float) $refundCalc['penalty'], 2, ',', '.') }}</td></tr>
                                    @endif
                                </table>
                                @if(($refundCalc['amount'] ?? 0) > 0)
                                    <p style="margin:10px 0 0 0;font-size:13px;color:#64748b;">Il rimborso verrà accreditato sul metodo di pagamento originale entro 5–10 giorni lavorativi.</p>
                                @else
                                    <p style="margin:10px 0 0 0;font-size:13px;color:#64748b;">Secondo le condizioni di cancellazione, per questa data non è previsto alcun rimborso.</p>
                                @endif
                            </div>
                        @endif

                        <p style="margin:18px 0 0 0;line-height:1.6;">
                            Se hai dubbi o ritieni che ci sia stato un errore, rispondi a questa email o contattaci.
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
