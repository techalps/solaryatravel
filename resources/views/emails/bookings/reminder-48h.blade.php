<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ __('emails.reminder_48h.title') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#10b981 0%,#0066cc 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ __('emails.reminder_48h.eyebrow') }}</div>
                        <div style="font-size:22px;font-weight:700;margin-top:4px;">{{ __('emails.reminder_48h.title') }}</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">{!! __('emails.common.greeting', ['name' => '<strong>'.e($booking->customer_first_name).'</strong>']) !!}</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            @if($booking->departure)
                                {{-- isoFormat con il locale ATTIVO: in un'email francese
                                     il giorno deve leggersi "mercredi", non "mercoledì". --}}
                                {!! __('emails.reminder_48h.intro_with_time', [
                                    'tour' => '<strong>'.e($booking->tour->name ?? '').'</strong>',
                                    'date' => '<strong>'.e(\Carbon\Carbon::parse($booking->departure->departure_date)->locale(app()->getLocale())->isoFormat('dddd D MMMM')).'</strong>',
                                    'time' => '<strong>'.e(\Carbon\Carbon::parse($booking->departure->start_time)->format('H:i')).'</strong>',
                                ]) !!}
                            @else
                                {!! __('emails.reminder_48h.intro_without_time', [
                                    'tour' => '<strong>'.e($booking->tour->name ?? '').'</strong>',
                                    'date' => '<strong>'.e(\Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y')).'</strong>',
                                ]) !!}
                            @endif
                        </p>

                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            {{ __('emails.reminder_48h.instructions') }}
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;padding:14px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;width:140px;">{{ __('emails.common.booking') }}</td>
                                <td style="padding:4px 0;font-size:14px;font-family:ui-monospace,Menlo,monospace;">#{{ $booking->booking_number }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">{{ __('emails.common.passengers') }}</td>
                                <td style="padding:4px 0;font-size:14px;">{{ $booking->seatRecords->count() }}</td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;font-size:13px;color:#94a3b8;line-height:1.6;">
                            {{ __('emails.reminder_48h.closing') }}
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
