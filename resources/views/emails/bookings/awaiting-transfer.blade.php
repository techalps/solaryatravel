<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ __('emails.awaiting_transfer.title') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#0ea5e9 0%,#0066cc 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">{{ __('emails.awaiting_transfer.title') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">{!! __('emails.common.greeting', ['name' => '<strong>'.e($booking->customer_first_name).'</strong>']) !!}</p>
                        <p style="margin:0 0 18px 0;line-height:1.6;">
                            {!! __('emails.awaiting_transfer.intro', [
                                'number' => e($booking->booking_number),
                                'tour' => '<strong>'.e($booking->tour->name ?? '').'</strong>',
                            ]) !!}
                        </p>

                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:18px;margin-bottom:18px;">
                            <p style="margin:0 0 8px 0;font-size:13px;color:#075985;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">{{ __('emails.awaiting_transfer.amount') }}</p>
                            <p style="margin:0 0 14px 0;font-size:26px;font-weight:800;color:#0066cc;">€ {{ number_format($amountDue, 2, ',', '.') }}</p>
                            @if($bankDetails)
                                <p style="margin:0 0 6px 0;font-size:13px;color:#075985;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">{{ __('emails.awaiting_transfer.bank_details') }}</p>
                                <pre style="margin:0;font-family:inherit;font-size:14px;color:#0f172a;white-space:pre-wrap;line-height:1.6;">{{ $bankDetails }}</pre>
                            @endif
                            <p style="margin:14px 0 0 0;font-size:14px;color:#0f172a;">
                                {{ __('emails.awaiting_transfer.reference', ['number' => $booking->booking_number]) }}
                            </p>
                        </div>

                        <p style="margin:0 0 18px 0;line-height:1.6;color:#475569;font-size:14px;">
                            {{ __('emails.awaiting_transfer.after') }}
                            {{ __('emails.awaiting_transfer.hint') }}
                        </p>

                        <div style="background:#fff8e6;border-left:4px solid #f59e0b;padding:14px;border-radius:6px;">
                            <p style="margin:0;font-size:13px;color:#78350f;line-height:1.6;">
                                {{ \App\Support\Settings::minParticipantsNotice() }}
                            </p>
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
