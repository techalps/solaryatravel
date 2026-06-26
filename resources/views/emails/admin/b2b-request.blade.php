@php
    $label = $type === 'cancellation' ? 'annullamento' : 'modifica';
    $agencyName = $agency?->agency_name ?: $agency?->name ?: 'Agenzia';
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Richiesta {{ $label }} agenzia</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#d97706 0%,#b45309 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }} · Portale Agenzie</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">Richiesta di {{ $label }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 16px 0;font-size:15px;color:#475569;">
                            L'agenzia <strong>{{ $agencyName }}</strong> ha richiesto
                            l'<strong>{{ $label }}</strong> della prenotazione qui sotto.
                            La richiesta <strong>non è stata applicata</strong>: valutala e conferma o rifiuta
                            dal pannello admin (applicando l'eventuale penale).
                        </p>

                        @if($reason)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px 0;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;">
                                <tr><td style="padding:12px 14px;font-size:14px;color:#92400e;">
                                    <strong>Motivo indicato:</strong><br>{{ $reason }}
                                </td></tr>
                            </table>
                        @endif

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
