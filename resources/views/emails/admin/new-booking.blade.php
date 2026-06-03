<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Nuova prenotazione</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }} · Notifica admin</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">Nuova prenotazione confermata</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 16px 0;font-size:15px;color:#475569;">
                            È stata confermata una nuova prenotazione con pagamento andato a buon fine.
                        </p>
                        @include('emails.admin._booking-details', ['booking' => $booking])
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
