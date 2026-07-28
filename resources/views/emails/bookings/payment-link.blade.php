<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Pagamento prenotazione</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#0066cc 0%,#0099ff 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">Conferma e paga la tua prenotazione</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">Ciao <strong>{{ $booking->customer_first_name }}</strong>,</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            grazie per aver prenotato con noi! Per finalizzare la tua escursione su
                            <strong>{{ $booking->tour->name ?? '' }}</strong>, completa il pagamento online
                            in modo sicuro tramite Stripe.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;padding:16px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="padding:6px 0;font-size:13px;color:#64748b;width:140px;">Prenotazione</td>
                                <td style="padding:6px 0;font-size:14px;"><strong>#{{ $booking->booking_number }}</strong></td>
                            </tr>
                            @if($booking->departure)
                            <tr>
                                <td style="padding:6px 0;font-size:13px;color:#64748b;">Data</td>
                                <td style="padding:6px 0;font-size:14px;">{{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding:6px 0;font-size:13px;color:#64748b;">Partecipanti</td>
                                <td style="padding:6px 0;font-size:14px;">{{ $booking->seats }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;font-size:13px;color:#64748b;">Totale da pagare</td>
                                <td style="padding:6px 0;font-size:18px;color:#0066cc;"><strong>€ {{ number_format($booking->total_amount, 2, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding:8px 0 16px 0;">
                                    <a href="{{ $payUrl }}" style="display:inline-block;background:#0066cc;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:50px;font-weight:600;font-size:16px;">
                                        Paga ora con carta
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 8px 0;font-size:13px;color:#64748b;line-height:1.6;">
                            Se il pulsante non funziona, copia e incolla questo link nel browser:
                        </p>
                        <p style="margin:0 0 16px 0;font-size:12px;color:#0066cc;word-break:break-all;">
                            <a href="{{ $payUrl }}" style="color:#0066cc;">{{ $payUrl }}</a>
                        </p>

                        <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">

                        <p style="margin:0 0 16px 0;font-size:13px;color:#64748b;line-height:1.6;">
                            Una volta completato il pagamento, riceverai una seconda email con i tuoi biglietti
                            e i QR code da mostrare al momento dell'imbarco (uno per ogni passeggero).
                        </p>

                        <div style="background:#fff8e6;border-left:4px solid #f59e0b;padding:14px;border-radius:6px;">
                            <p style="margin:0;font-size:13px;color:#78350f;line-height:1.6;">
                                {{ \App\Support\Settings::minParticipantsNotice() }}
                            </p>
                        </div>

                        @if($booking->payment_deadline)
                            <p style="margin:14px 0 0 0;font-size:13px;color:#dc2626;">
                                <strong>Importante:</strong> Il link di pagamento scade il
                                {{ $booking->payment_deadline->format('d/m/Y H:i') }}.
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="background:#f8fafc;padding:18px 28px;border-top:1px solid #e5e7eb;font-size:12px;color:#64748b;text-align:center;">
                        Hai domande? Rispondi a questa email o contatta lo staff.<br>
                        © {{ date('Y') }} {{ config('app.name') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
