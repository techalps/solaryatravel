{{-- Tabella dati prenotazione condivisa dalle email admin. Riceve $booking. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:8px 0 0;">
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;width:160px;">Prenotazione</td>
        <td style="padding:6px 0;font-size:14px;font-weight:600;">#{{ $booking->booking_number }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Cliente</td>
        <td style="padding:6px 0;font-size:14px;">{{ $booking->customer_first_name }} {{ $booking->customer_last_name }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Email</td>
        <td style="padding:6px 0;font-size:14px;"><a href="mailto:{{ $booking->customer_email }}" style="color:#2563eb;text-decoration:none;">{{ $booking->customer_email }}</a></td>
    </tr>
    @if($booking->customer_phone)
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Telefono</td>
        <td style="padding:6px 0;font-size:14px;"><a href="tel:{{ $booking->customer_phone }}" style="color:#2563eb;text-decoration:none;">{{ $booking->customer_phone }}</a></td>
    </tr>
    @endif
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Tour</td>
        <td style="padding:6px 0;font-size:14px;font-weight:600;">{{ $booking->tour->name ?? 'N/D' }}</td>
    </tr>
    @if($booking->departure)
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Partenza</td>
        <td style="padding:6px 0;font-size:14px;">{{ \Carbon\Carbon::parse($booking->departure->departure_date)->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($booking->departure->start_time)->format('H:i') }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Passeggeri</td>
        <td style="padding:6px 0;font-size:14px;">{{ $booking->seats }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#64748b;">Totale</td>
        <td style="padding:6px 0;font-size:14px;font-weight:700;">€ {{ number_format((float) $booking->total_amount, 2, ',', '.') }}</td>
    </tr>
</table>

<div style="margin-top:20px;">
    <a href="{{ route('admin.bookings.show', $booking) }}"
       style="display:inline-block;background:#0E1B33;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:11px 22px;border-radius:8px;">
        Apri nel gestionale →
    </a>
</div>
