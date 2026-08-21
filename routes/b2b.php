<?php

use App\Http\Controllers\B2B\AuthController;
use App\Http\Controllers\B2B\BookingController;
use App\Http\Controllers\B2B\DashboardController;
use App\Http\Controllers\B2B\ImpersonateController;
use App\Http\Controllers\B2B\ReferralController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Canale B2B Agenzie
|--------------------------------------------------------------------------
|
| Route servite SOLO sull'host config('b2b.domain') (secondo document root /b2b).
| Caricate da bootstrap/app.php dentro Route::domain(...)->middleware('b2b_web').
| Il gating ruolo↔area è sul middleware 'b2b' (solo utenti con ruolo b2b).
|
| Stesso codebase, stesso DB, stesso motore prenotazioni: nessuna duplicazione.
|
*/

// --- Autenticazione (pubblica, solo guest) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('b2b.login');
    Route::post('/login', [AuthController::class, 'login'])->name('b2b.login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('b2b.logout');

// --- Area riservata agenzie (ruolo b2b o admin in impersonificazione) ---
Route::middleware('b2b')->group(function () {
    // Impersonificazione agenzia (solo admin gestionali). La schermata di scelta
    // è raggiungibile anche senza agenzia attiva (vedi B2bMiddleware).
    Route::get('/scegli-agenzia', [ImpersonateController::class, 'select'])->name('b2b.impersonate.select');
    Route::post('/scegli-agenzia', [ImpersonateController::class, 'store'])->name('b2b.impersonate.store');
    Route::post('/cambia-agenzia', [ImpersonateController::class, 'stop'])->name('b2b.impersonate.stop');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('b2b.dashboard');

    // Nuova prenotazione (Flusso A): scelta tour → form identico al cliente.
    Route::get('/prenotazioni/nuova', [BookingController::class, 'create'])->name('b2b.bookings.create');
    Route::get('/prenotazioni/nuova/prenota', [BookingController::class, 'start'])->name('b2b.bookings.start');
    // Comuni per la select a cascata del documento (stesso endpoint del sito).
    Route::get('/api/geo/comuni/{sigla}', [\App\Http\Controllers\GeoController::class, 'comuni'])
        ->where('sigla', '[A-Za-z]{2}')->name('b2b.api.geo.comuni');

    // Le mie prenotazioni: lista + dettaglio (isolamento per agenzia).
    Route::get('/prenotazioni', [BookingController::class, 'index'])->name('b2b.bookings.index');
    Route::get('/prenotazioni/{booking:uuid}', [BookingController::class, 'show'])->name('b2b.bookings.show');
    // Azioni sul dettaglio: reinvio estremi pagamento al cliente, richieste ad admin.
    Route::post('/prenotazioni/{booking:uuid}/reinvia-pagamento', [BookingController::class, 'resendPayment'])->name('b2b.bookings.resend-payment');
    // Correzione dell'email del cliente e reinvio delle comunicazioni: le due
    // cose vanno insieme, un'email sbagliata rende il cliente irraggiungibile.
    Route::post('/prenotazioni/{booking:uuid}/email', [BookingController::class, 'updateEmail'])->name('b2b.bookings.update-email');
    Route::post('/prenotazioni/{booking:uuid}/reinvia-comunicazioni', [BookingController::class, 'resendCommunications'])->name('b2b.bookings.resend-communications');
    Route::post('/prenotazioni/{booking:uuid}/richiedi-annullamento', [BookingController::class, 'requestCancellation'])->name('b2b.bookings.request-cancellation');
    Route::post('/prenotazioni/{booking:uuid}/richiedi-modifica', [BookingController::class, 'requestModification'])->name('b2b.bookings.request-modification');

    // Widget incorporabile (iframe) — codice agenzia + snippet.
    Route::get('/widget', [ReferralController::class, 'widget'])->name('b2b.widget');
    Route::post('/widget/domini', [ReferralController::class, 'saveWidgetDomains'])->name('b2b.widget.domains');

    // Link & QR referral (Flusso B).
    Route::get('/referral', [ReferralController::class, 'index'])->name('b2b.referral');
    Route::get('/referral/qr', [ReferralController::class, 'qr'])->name('b2b.referral.qr');
    Route::get('/referral/volantino', [ReferralController::class, 'flyer'])->name('b2b.referral.flyer');
});
