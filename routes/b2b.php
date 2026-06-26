<?php

use App\Http\Controllers\B2B\AuthController;
use App\Http\Controllers\B2B\DashboardController;
use App\Http\Controllers\B2B\ImpersonateController;
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

    // Nuova prenotazione — Fase 6.
    Route::get('/prenotazioni/nuova', function () {
        return view('b2b.placeholder', ['titolo' => 'Nuova prenotazione']);
    })->name('b2b.bookings.create');

    // Le mie prenotazioni — Fase 9.
    Route::get('/prenotazioni', function () {
        return view('b2b.placeholder', ['titolo' => 'Le mie prenotazioni']);
    })->name('b2b.bookings.index');
    Route::get('/prenotazioni/{booking:uuid}', function () {
        return view('b2b.placeholder', ['titolo' => 'Dettaglio prenotazione']);
    })->name('b2b.bookings.show');

    // Link & QR referral — Fase 7.
    Route::get('/referral', function () {
        return view('b2b.placeholder', ['titolo' => 'Link & QR']);
    })->name('b2b.referral');
});
