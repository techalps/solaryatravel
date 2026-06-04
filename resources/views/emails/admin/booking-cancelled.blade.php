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
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }} · Notifica admin</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">Prenotazione annullata</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 16px 0;font-size:15px;color:#475569;">
                            Una prenotazione è stata annullata.
                            @if($reason)<br>Motivo: <strong>{{ $reason }}</strong>@endif
                        </p>
                        @include('emails.admin._booking-details', ['booking' => $booking])

                        @if(!empty($refundCalc) && ($refundCalc['paid'] ?? 0) > 0)
                            <div style="margin-top:18px;padding:14px 16px;background:#f8fafc;border-radius:8px;">
                                <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#0E1B33;">Rimborso secondo policy</p>
                                <p style="margin:0;font-size:14px;color:#475569;">
                                    Versato € {{ number_format((float) $refundCalc['paid'], 2, ',', '.') }} ·
                                    Rimborso {{ $refundCalc['percentage'] }}% = <strong>€ {{ number_format((float) $refundCalc['amount'], 2, ',', '.') }}</strong>
                                    @if(($refundCalc['penalty'] ?? 0) > 0) · Penale € {{ number_format((float) $refundCalc['penalty'], 2, ',', '.') }}@endif
                                </p>
                                @if(!empty($refundResult) && ($refundResult['manual'] ?? false) && ($refundResult['amount'] ?? 0) > 0)
                                    <p style="margin:10px 0 0;padding:10px 12px;background:#fffbeb;border-left:4px solid #f59e0b;font-size:13px;color:#92400e;">
                                        ⚠️ Pagamento via bonifico: il rimborso di € {{ number_format((float) $refundResult['amount'], 2, ',', '.') }} va effettuato <strong>manualmente</strong>.
                                    </p>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 28px;background:#f8fafc;border-top:1px solid #eef2f7;font-size:12px;color:#94a3b8;">
                        Email automatica · {{ config('app.name') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
