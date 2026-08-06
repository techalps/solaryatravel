<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ __('emails.balance_reminder.title') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#7c3aed 0%,#5b21b6 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">{{ __('emails.balance_reminder.title') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">{!! __('emails.common.greeting', ['name' => '<strong>'.e($booking->customer_first_name).'</strong>']) !!}</p>
                        <p style="margin:0 0 18px 0;line-height:1.6;">
                            {!! __('emails.balance_reminder.intro', ['tour' => '<strong>'.e($booking->tour->name ?? '').'</strong>', 'number' => e($booking->booking_number)]) !!}
                        </p>

                        <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:16px;margin-bottom:18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr><td style="padding:3px 0;font-size:14px;color:#475569;">{{ __('emails.common.total') }}</td><td style="padding:3px 0;font-size:14px;text-align:right;">€ {{ number_format((float) $booking->total_amount, 2, ',', '.') }}</td></tr>
                                <tr><td style="padding:3px 0;font-size:14px;color:#475569;">{{ __('emails.balance_reminder.deposit_paid') }}</td><td style="padding:3px 0;font-size:14px;text-align:right;">− € {{ number_format((float) $booking->amount_paid, 2, ',', '.') }}</td></tr>
                                <tr><td style="padding:6px 0 0;font-size:15px;font-weight:700;color:#0f172a;">{{ __('emails.balance_reminder.balance_due') }}</td><td style="padding:6px 0 0;font-size:18px;font-weight:800;text-align:right;color:#5b21b6;">€ {{ number_format((float) $booking->balance_amount, 2, ',', '.') }}</td></tr>
                            </table>
                            @if($booking->balance_due_at)
                                <p style="margin:12px 0 0;font-size:13px;color:#92400e;">{{ __('emails.balance_reminder.deadline', ['date' => \Carbon\Carbon::parse($booking->balance_due_at)->format('d/m/Y H:i')]) }}</p>
                            @endif
                        </div>

                        <div style="text-align:center;">
                            <a href="{{ public_site_route('booking.balance', $booking->uuid) }}"
                               style="display:inline-block;background:#7c3aed;color:#ffffff;padding:14px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;">
                                {{ __('emails.balance_reminder.cta') }}
                            </a>
                        </div>
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
