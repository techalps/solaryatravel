<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Benvenuto su {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#0066cc 0%,#10b981 100%);padding:36px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:26px;font-weight:700;margin-top:6px;">Benvenuto a bordo!</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">Ciao <strong>{{ $user->first_name ?? $user->name }}</strong>,</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            grazie per esserti registrato su <strong>{{ config('app.name') }}</strong>. Da oggi puoi prenotare le nostre escursioni in catamarano,
                            consultare lo storico dei tuoi tour e gestire i tuoi biglietti.
                        </p>

                        @if(!empty($verifyUrl))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:18px;margin:18px 0;">
                                <tr>
                                    <td style="font-size:14px;color:#78350f;line-height:1.6;">
                                        <strong style="display:block;font-size:15px;margin-bottom:6px;color:#0f172a;">📧 Verifica il tuo indirizzo email</strong>
                                        Per attivare completamente l'account e ricevere conferme di prenotazione, clicca il pulsante qui sotto entro 60 minuti.
                                    </td>
                                </tr>
                            </table>

                            <div style="margin:18px 0 26px 0;text-align:center;">
                                <a href="{{ $verifyUrl }}" style="display:inline-block;background:#059669;color:#fff;padding:14px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;">
                                    Verifica indirizzo email
                                </a>
                                <p style="margin:10px 0 0 0;font-size:11px;color:#94a3b8;line-height:1.5;word-break:break-all;">
                                    Se il pulsante non funziona, copia questo link nel browser:<br>
                                    {{ $verifyUrl }}
                                </p>
                            </div>
                        @endif

                        <div style="margin:24px 0;text-align:center;">
                            {{-- Le email restano in italiano (fuori dal perimetro
                                 bilingue): il link va forzato alla versione IT,
                                 altrimenti erediterebbe la lingua della richiesta
                                 in cui la mail viene generata. --}}
                            <a href="{{ locale_route('tours.index', [], config('locales.default', 'it')) }}" style="display:inline-block;background:#0066cc;color:#fff;padding:14px 28px;border-radius:10px;text-decoration:none;font-weight:600;font-size:15px;">
                                Esplora i tour
                            </a>
                        </div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;padding:16px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="font-size:13px;color:#475569;line-height:1.6;">
                                    <strong style="display:block;margin-bottom:6px;color:#0f172a;">Cosa puoi fare ora:</strong>
                                    • Sfogliare il catalogo delle nostre escursioni<br>
                                    • Prenotare il tour che preferisci con pochi click<br>
                                    • Salvare le tue prenotazioni nel tuo account<br>
                                    • Ricevere biglietti e promemoria via email
                                </td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;font-size:13px;color:#94a3b8;line-height:1.6;">
                            Buona navigazione,<br>
                            il team {{ config('app.name') }}
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
