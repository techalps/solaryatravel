<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckIn;
use App\Enums\BookingStatus;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CheckInController extends Controller
{
    /**
     * Show the QR scanner page (for staff).
     */
    public function scanner(): View
    {
        return view('check-in.scanner');
    }

    /**
     * Process QR code scan.
     */
    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        // QR code format: BOOKING:XXXX-XXXX-XXXX:VERIFICATION_CODE
        $parts = explode(':', $request->qr_code);

        if (count($parts) !== 3 || $parts[0] !== 'BOOKING') {
            return response()->json([
                'success' => false,
                'message' => 'QR code non valido',
            ], 400);
        }

        $bookingNumber = $parts[1];
        $verificationCode = $parts[2];

        $booking = Booking::where('booking_number', $bookingNumber)
            ->with(['tour', 'departure'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Prenotazione non trovata',
            ], 404);
        }

        // Verify the code
        if ($booking->verification_code !== $verificationCode) {
            return response()->json([
                'success' => false,
                'message' => 'Codice di verifica non valido',
            ], 400);
        }

        // Check booking status
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return response()->json([
                'success' => false,
                'message' => 'La prenotazione non è confermata',
                'booking_status' => $booking->status->value,
            ], 400);
        }

        // Check if already checked in
        if ($booking->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in già effettuato',
                'checked_in_at' => $booking->checkIns->last()->checked_in_at->format('d/m/Y H:i'),
            ], 400);
        }

        // Check if booking date matches today
        if (!$booking->booking_date->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Questa prenotazione non è per oggi',
                'booking_date' => $booking->booking_date->format('d/m/Y'),
            ], 400);
        }

        // Create check-in record
        $checkIn = CheckIn::create([
            'booking_id' => $booking->id,
            'checked_in_by' => auth()->id(),
            'checked_in_at' => now(),
            'method' => 'qr_code',
            'device_info' => $request->header('User-Agent'),
            'location' => $request->ip(),
            'notes' => null,
        ]);

        // Update booking status
        $booking->update(['status' => BookingStatus::CHECKED_IN]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in completato con successo!',
            'booking' => [
                'number' => $booking->booking_number,
                'customer_name' => $booking->customer_first_name . ' ' . $booking->customer_last_name,
                'tour' => $booking->tour?->name,
                'time_slot' => optional($booking->departure)->start_time,
                'seats' => $booking->seats,
            ],
            'check_in' => [
                'id' => $checkIn->id,
                'time' => $checkIn->checked_in_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Show booking details for verification.
     */
    public function verify(string $bookingNumber): View|RedirectResponse
    {
        $booking = Booking::where('booking_number', $bookingNumber)
            ->with(['tour', 'departure', 'addons', 'checkIns'])
            ->firstOrFail();

        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }

        return view('check-in.verify', compact('booking'));
    }

    /**
     * Manual check-in (for staff).
     */
    public function manual(Request $request, string $bookingNumber): RedirectResponse
    {
        $booking = Booking::where('booking_number', $bookingNumber)->firstOrFail();

        if ($booking->status !== BookingStatus::CONFIRMED) {
            return redirect()
                ->back()
                ->with('error', 'La prenotazione non può essere effettuato il check-in.');
        }

        if ($booking->isCheckedIn()) {
            return redirect()
                ->back()
                ->with('warning', 'Check-in già effettuato.');
        }

        CheckIn::create([
            'booking_id' => $booking->id,
            'checked_in_by' => auth()->id(),
            'checked_in_at' => now(),
            'method' => 'manual',
            'notes' => $request->input('notes'),
        ]);

        $booking->update(['status' => BookingStatus::CHECKED_IN]);

        return redirect()
            ->back()
            ->with('success', 'Check-in completato!');
    }

    /**
     * Generate QR code for a booking.
     */
    public function generateQrCode(string $bookingNumber)
    {
        $booking = Booking::where('booking_number', $bookingNumber)->firstOrFail();

        // Generate verification code if not exists
        if (!$booking->verification_code) {
            $booking->update([
                'verification_code' => strtoupper(bin2hex(random_bytes(8))),
            ]);
            $booking->refresh();
        }

        $qrContent = sprintf(
            'BOOKING:%s:%s',
            $booking->booking_number,
            $booking->verification_code
        );

        $qrCode = app(QrCodeService::class)->png($qrContent, 300, 10);

        return response($qrCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="qr-' . $booking->booking_number . '.png"');
    }
}
