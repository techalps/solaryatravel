<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ __('emails.tickets.title') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06);">
                <tr>
                    <td style="background:linear-gradient(135deg,#10b981 0%,#0066cc 100%);padding:32px 28px;color:#ffffff;">
                        <div style="font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">{{ config('app.name') }}</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px;">{{ __('emails.tickets.title') }}</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;">{!! __('emails.common.greeting', ['name' => '<strong>'.e($booking->customer_first_name).'</strong>']) !!}</p>
                        <p style="margin:0 0 16px 0;line-height:1.6;">
                            {{ __('emails.tickets.intro') }}
                            {{ __('emails.tickets.instructions') }}
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;padding:14px;margin:12px 0 20px 0;">
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;width:140px;">{{ __('emails.common.tour') }}</td>
                                <td style="padding:4px 0;font-size:14px;"><strong>{{ $booking->tour->name ?? '' }}</strong></td>
                            </tr>
                            @if($booking->departure)
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">{{ __('emails.common.date_departure') }}</td>
                                <td style="padding:4px 0;font-size:14px;">{{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">{{ __('emails.common.passengers') }}</td>
                                <td style="padding:4px 0;font-size:14px;">{{ $booking->seatRecords->count() }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;font-size:13px;color:#64748b;">{{ __('emails.common.booking') }}</td>
                                <td style="padding:4px 0;font-size:14px;font-family:ui-monospace,Menlo,monospace;">#{{ $booking->booking_number }}</td>
                            </tr>
                        </table>

                        <div style="background:#dbeafe;border-left:4px solid #0066cc;padding:14px;border-radius:6px;margin-top:6px;">
                            <p style="margin:0;font-size:14px;color:#0c4a6e;line-height:1.6;">
                                <strong>📎 In allegato:</strong> biglietti-{{ $booking->booking_number }}.pdf
                            </p>
                        </div>

                        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:14px;border-radius:6px;margin-top:18px;">
                            <p style="margin:0;font-size:13px;color:#78350f;line-height:1.6;">
                                <strong>{{ __('emails.tickets.tip_label') }}</strong> {{ __('emails.tickets.tip') }}
                            </p>
                        </div>

                        {{-- Assistenza WhatsApp: link wa.me col messaggio già precompilato.
                             Compare solo se il numero aziendale è configurato. --}}
                        @php
                            $waLink = \App\Support\WhatsApp::customerLink($booking);
                        @endphp
                        @if($waLink)
                            <div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:16px;border-radius:6px;margin-top:18px;text-align:center;">
                                <p style="margin:0 0 12px;font-size:13px;color:#14532d;line-height:1.6;">
                                    {{ __('whatsapp.help_text') }}
                                </p>
                                <a href="{{ $waLink }}"
                                   style="display:inline-block;background:#25D366;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:11px 24px;border-radius:999px;">
                                    {{ __('whatsapp.contact_us') }}
                                </a>
                            </div>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="background:#f8fafc;padding:18px 28px;border-top:1px solid #e5e7eb;font-size:12px;color:#64748b;text-align:center;">
                        {{ __('emails.reminder_48h.closing') }}<br>
                        © {{ date('Y') }} {{ config('app.name') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
