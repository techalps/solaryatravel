<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Canale B2B Agenzie
|--------------------------------------------------------------------------
|
| Route servite SOLO sull'host config('b2b.host') (secondo document root /b2b).
| Caricate da bootstrap/app.php dentro Route::domain(...)->middleware('b2b_web').
| Il gating ruolo↔area è sul middleware 'b2b' (solo utenti con ruolo b2b).
|
| Stesso codebase, stesso DB, stesso motore prenotazioni: nessuna duplicazione.
|
*/

// Login B2B: pagina pubblica (guest), grafica admin. Vedi Fase 4.
Route::get('/login', function () {
    return 'B2B LOGIN placeholder';
})->name('b2b.login');

// Area riservata agenzie: solo utenti con ruolo b2b.
Route::middleware('b2b')->group(function () {
    Route::get('/', function () {
        return 'B2B OK — host: '.request()->getHttpHost().' — agenzia: '.auth()->user()->agency_name;
    })->name('b2b.dashboard');
});
