<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * Display settings page.
     */
    public function index(): View
    {
        $settings = $this->getSettings();
        // Tour attivi per la sezione "orari limite di prenotazione" (per-tour).
        $activeTours = \App\Models\Tour::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'booking_cutoff_time']);

        return view('admin.settings.index', compact('settings', 'activeTours'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'site_email' => 'required|email|max:100',
            'admin_notification_email' => 'nullable|email|max:100',
            'site_phone' => 'nullable|string|max:30',
            'site_address' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:50',
            'currency' => 'required|string|size:3',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'booking_advance_days' => 'required|integer|min:0|max:365',
            'cancellation_hours' => 'required|integer|min:0|max:168',
            // Penali di storno (fasce in giorni + % rimborso)
            'cancel_penalty_days_1' => 'required|integer|min:0|max:365',
            'cancel_penalty_refund_1' => 'required|integer|min:0|max:100',
            'cancel_penalty_days_2' => 'required|integer|min:0|max:365',
            'cancel_penalty_refund_2' => 'required|integer|min:0|max:100',
            'cancel_penalty_refund_under' => 'required|integer|min:0|max:100',
            // Acconto
            'deposit_enabled' => 'boolean',
            'deposit_percentage' => 'required|integer|min:1|max:99',
            // Scadenza saldo in giorni prima della partenza (sostituisce le ore).
            'balance_due_days' => 'required|integer|min:1|max:90',
            // Anticipo minimo perché l'acconto sia proponibile (0 = sempre).
            'deposit_min_days' => 'required|integer|min:0|max:365',
            // Bonifico istantaneo
            'bank_transfer_enabled' => 'boolean',
            'bank_transfer_details' => 'nullable|string|max:1000',
            'bank_transfer_expiry_hours' => 'required|integer|min:1|max:168',
            // Minimo partecipanti per confermare la partenza
            'min_participants' => 'required|integer|min:1|max:50',
            'min_participants_deadline_label' => 'nullable|string|max:120',
            // Orario limite di prenotazione globale (HH:MM del giorno prima).
            'booking_cutoff_time' => 'required|date_format:H:i',
            'default_seats' => 'required|integer|min:1|max:50',
            'payment_deadline_minutes' => 'required|integer|min:5|max:1440',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
            'smtp_host' => 'nullable|string|max:100',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:100',
            'smtp_password' => 'nullable|string|max:100',
            'smtp_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_name' => 'nullable|string|max:100',
            'mail_from_address' => 'nullable|email|max:100',
            // SMTP dedicato alle notifiche admin (nuova prenotazione, cancellazione, rimborso)
            'admin_smtp_host' => 'nullable|string|max:100',
            'admin_smtp_port' => 'nullable|integer|min:1|max:65535',
            'admin_smtp_username' => 'nullable|string|max:100',
            'admin_smtp_password' => 'nullable|string|max:100',
            'admin_smtp_encryption' => 'nullable|string|in:tls,ssl',
            'admin_mail_from_name' => 'nullable|string|max:100',
            'admin_mail_from_address' => 'nullable|email|max:100',
            'enable_notifications' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        // Convert checkboxes
        $validated['enable_notifications'] = $request->boolean('enable_notifications');
        $validated['maintenance_mode'] = $request->boolean('maintenance_mode');
        $validated['deposit_enabled'] = $request->boolean('deposit_enabled');
        $validated['bank_transfer_enabled'] = $request->boolean('bank_transfer_enabled');

        $this->saveSettings($validated);

        // Clear cache
        Cache::forget('app_settings');

        return back()->with('success', 'Impostazioni aggiornate con successo.');
    }

    /**
     * Salva gli orari limite di prenotazione per i singoli tour.
     * - "applica a tutti": scrive lo stesso orario su tutti i tour attivi;
     * - altrimenti: per ogni tour, orario proprio oppure vuoto = usa il globale.
     */
    public function updateTourCutoffs(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Modalità "applica a tutti": un unico orario per ogni tour attivo.
        if ($request->filled('apply_all_time')) {
            $request->validate(['apply_all_time' => 'date_format:H:i']);
            \App\Models\Tour::where('is_active', true)
                ->update(['booking_cutoff_time' => $request->input('apply_all_time') . ':00']);

            return back()->with('success', 'Orario limite applicato a tutti i tour attivi.');
        }

        // Modalità per-tour: mappa tour_id => "HH:MM" (o vuoto = usa il globale).
        $data = $request->validate([
            'cutoff' => 'array',
            'cutoff.*' => 'nullable|date_format:H:i',
        ]);

        foreach ($data['cutoff'] ?? [] as $tourId => $time) {
            \App\Models\Tour::where('id', (int) $tourId)
                ->update(['booking_cutoff_time' => $time ? $time . ':00' : null]);
        }

        return back()->with('success', 'Orari limite dei tour aggiornati.');
    }

    /**
     * Invia una mail di test con la config SMTP corrente. Risponde JSON per chiamate AJAX.
     */
    public function sendTestMail(Request $request)
    {
        $request->validate(['to' => 'required|email']);

        try {
            Mail::raw(
                "Questa è una mail di prova inviata da " . config('app.name') . " il " . now()->format('d/m/Y H:i:s') . ".\n\n"
                . "Se la stai leggendo, la configurazione SMTP funziona correttamente.\n\n"
                . "Driver: " . config('mail.default') . "\n"
                . "Host: " . config('mail.mailers.smtp.host') . "\n"
                . "Porta: " . config('mail.mailers.smtp.port') . "\n"
                . "Mittente: " . config('mail.from.address'),
                function ($message) use ($request) {
                    $message->to($request->to)->subject('Test SMTP · ' . config('app.name'));
                }
            );
            return response()->json([
                'success' => true,
                'message' => 'Mail di prova inviata a ' . $request->to . '. Verifica la casella (anche spam).',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Le fasce orarie sono ora gestite per-tour (tour_departures).
    // Vedi admin.tours.departures.

    /**
     * Get settings from storage.
     */
    private function getSettings(): array
    {
        return Cache::remember('app_settings', 3600, function () {
            $path = storage_path('app/settings.json');
            
            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true) ?? $this->getDefaultSettings();
            }

            return $this->getDefaultSettings();
        });
    }

    /**
     * Save settings to storage.
     */
    private function saveSettings(array $settings): void
    {
        $path = storage_path('app/settings.json');
        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Get default settings.
     */
    private function getDefaultSettings(): array
    {
        return [
            'site_name' => 'Solarya Travel',
            'site_email' => 'info@solaryatravel.com',
            'admin_notification_email' => '',
            'site_phone' => '',
            'site_address' => '',
            'company_name' => 'Solarya Travel S.r.l.',
            'vat_number' => '',
            'currency' => 'EUR',
            'tax_rate' => 22,
            'booking_advance_days' => 30,
            'cancellation_hours' => 24,
            'default_seats' => 1,
            'payment_deadline_minutes' => 30,
            // Penali di storno
            'cancel_penalty_days_1' => 14,
            'cancel_penalty_refund_1' => 70,
            'cancel_penalty_days_2' => 7,
            'cancel_penalty_refund_2' => 50,
            'cancel_penalty_refund_under' => 0,
            // Acconto
            'deposit_enabled' => false,
            'deposit_percentage' => 50,
            'balance_due_days' => 3,
            'deposit_min_days' => 7,
            // Bonifico istantaneo
            'bank_transfer_enabled' => false,
            'bank_transfer_details' => '',
            'bank_transfer_expiry_hours' => 24,
            // Minimo partecipanti
            'min_participants' => 6,
            'min_participants_deadline_label' => '48 ore prima della partenza',
            // Orario limite di prenotazione globale (giorno prima)
            'booking_cutoff_time' => '22:00',
            'stripe_public_key' => config('services.stripe.key', ''),
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'smtp_host' => config('mail.mailers.smtp.host', ''),
            'smtp_port' => config('mail.mailers.smtp.port', 587),
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'mail_from_name' => config('mail.from.name', 'Solarya Travel'),
            'mail_from_address' => config('mail.from.address', 'noreply@solaryatravel.com'),
            'admin_smtp_host' => '',
            'admin_smtp_port' => 587,
            'admin_smtp_username' => '',
            'admin_smtp_password' => '',
            'admin_smtp_encryption' => 'tls',
            'admin_mail_from_name' => 'Solarya Travel · Sistema',
            'admin_mail_from_address' => '',
            'enable_notifications' => true,
            'maintenance_mode' => false,
        ];
    }
}
