<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BoardingController as AdminBoardingController;
use App\Http\Controllers\Admin\DepartureAssignmentController as AdminDepartureAssignmentController;
use App\Http\Controllers\Admin\CatamaranController as AdminCatamaranController;
use App\Http\Controllers\Admin\TourController as AdminTourController;
use App\Http\Controllers\Admin\AddonController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DeployController;

/*
|--------------------------------------------------------------------------
| Public Routes (bilingue IT/EN)
|--------------------------------------------------------------------------
|
| Le pagine pubbliche indicizzabili sono registrate UNA VOLTA PER LINGUA,
| sempre con gli stessi URI e gli stessi controller:
|
|   italiano (default) → nessun prefisso, nomi "nudi"  (home, tours.show…)
|   inglese            → prefisso /en,   nomi "en.*"   (en.home, en.tours.show…)
|
| I nomi vanno namespacati perché in Laravel un nome di route è unico: due
| registrazioni omonime si sovrascrivono nella name lookup. Le Blade però NON
| devono conoscere questo dettaglio: continuano a chiamare route('tours.show')
| e l'helper locale_route() (app/Helpers/locale.php) mappa il nome sulla
| variante della lingua attiva. Nessuna Blade costruisce URL a mano.
|
| Gli slug dei tour restano identici nelle due lingue.
|
| Restano a registrazione singola (solo italiano) i flussi non indicizzabili
| e fuori perimetro: pagamenti, prenotazione per UUID, widget, check-in, API,
| area utente, auth e admin.
|
*/

$localizedPublicRoutes = function (): void {
    Route::get('/', HomeController::class)->name('home');

    // Tours (entry-point pubblico per prenotazione)
    Route::get('/tour', [TourController::class, 'index'])->name('tours.index');
    Route::get('/tour/{slug}', [TourController::class, 'show'])->name('tours.show');

    // Booking flow (form pubblico)
    Route::get('/prenota', [BookingController::class, 'start'])->name('booking.start');
    Route::post('/prenota', [BookingController::class, 'store'])->name('booking.store');

    // Static
    Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/termini-condizioni', [PageController::class, 'terms'])->name('terms');
    Route::get('/cookie-policy', [PageController::class, 'cookies'])->name('cookies');
};

// Italiano: URL storiche, nessun prefisso, nomi di route invariati.
Route::group([], $localizedPublicRoutes);

// Ogni altra lingua: prefisso di URI e di nome uguali al codice lingua.
// NB: si registrano le route di TUTTE le lingue del catalogo, non solo di quelle
// attive: le route vengono cachate al deploy (route:cache), mentre le lingue
// attive cambiano a runtime dalle Impostazioni. Registrando tutto, attivare una
// lingua è una spunta e non richiede un rilascio. Le lingue non attive non sono
// raggiungibili perché SetLocale e lo switcher guardano Locales::active().
foreach (array_keys((array) config('locales.names', [])) as $locale) {
    if ($locale === (string) config('locales.default', 'it')) {
        continue;
    }

    Route::prefix($locale)->name($locale.'.')->group($localizedPublicRoutes);
}

// Sicurezza: /it/... (prefisso esplicito per la lingua di default) non esiste
// come URL canonica → 301 alla versione senza prefisso, per non generare
// contenuto duplicato se qualcuno linkasse quella forma.
Route::get('/it/{path?}', function (?string $path = null) {
    return redirect(($path ? '/'.$path : '/'), 301);
})->where('path', '.*')->name('locale.legacy-it');

// Switcher di lingua: salva la preferenza e torna alla STESSA pagina
// nell'altra lingua (non alla home).
Route::get('/lingua/{locale}', LocaleController::class)
    ->whereIn('locale', array_keys((array) config('locales.names', [])))
    ->name('locale.switch');

// Sitemap XML bilingue (annotazioni xhtml:link reciproche).
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/api/departures/{departure}/availability', [TourController::class, 'checkDeparture'])->name('api.departure.availability');

// Comuni di una provincia (select a cascata luogo di emissione documento).
// Statico e cacheabile; disponibile anche su widget (stesso host).
Route::get('/api/geo/comuni/{sigla}', [\App\Http\Controllers\GeoController::class, 'comuni'])
    ->where('sigla', '[A-Za-z]{2}')->name('api.geo.comuni');

// Redirect compatibilità: vecchio /catamarani -> /tour
Route::redirect('/catamarani', '/tour');

// Widget di prenotazione incorporabile per le agenzie (iframe su siti terzi).
// Riusa il flusso pubblico + referral (?ref=TOKEN) con layout "nudo".
Route::get('/widget', [WidgetController::class, 'index'])->name('widget.index');

// Booking flow (le route /prenota sono registrate nel gruppo bilingue sopra)
Route::get('/prenotazione/{booking:uuid}/bonifico', [BookingController::class, 'bankTransfer'])->name('booking.bank-transfer');
Route::get('/prenotazione/{booking:uuid}/saldo', [BookingController::class, 'balance'])->name('booking.balance');
Route::post('/prenotazione/{booking:uuid}/saldo', [BookingController::class, 'payBalance'])->name('booking.balance.pay');

// Booking show / confirmation
Route::get('/prenotazione/{booking:uuid}', [BookingController::class, 'show'])->name('booking.show');
Route::get('/prenotazione/{booking:uuid}/conferma', [BookingController::class, 'confirmation'])->name('booking.confirmation');
Route::get('/prenotazione/{booking:uuid}/qr', [BookingController::class, 'qrCode'])->name('booking.qr');
Route::get('/prenotazione/{booking:uuid}/biglietti', [BookingController::class, 'tickets'])->name('booking.tickets');
Route::get('/biglietti/{seat:qr_code}/qr', [BookingController::class, 'seatQr'])->name('booking.seat.qr');

// Payment
Route::prefix('pagamento')->name('payment.')->group(function () {
    Route::get('/{booking:uuid}', [PaymentController::class, 'show'])->name('show');
    Route::post('/{booking:uuid}/process', [PaymentController::class, 'process'])->name('process');
    Route::get('/{booking:uuid}/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/{booking:uuid}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
});
Route::post('/webhooks/stripe', [PaymentController::class, 'webhook'])->name('webhooks.stripe');

// Check-in QR
Route::get('/checkin/{qrCode}', [CheckInController::class, 'verify'])->name('checkin.verify');

// Static: le pagine legali sono registrate nel gruppo bilingue sopra.

/*
|--------------------------------------------------------------------------
| Auth User
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/le-mie-prenotazioni', [BookingController::class, 'myBookings'])->name('bookings.my');
    Route::post('/prenotazione/{booking:uuid}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');

    Route::get('/profilo', [PageController::class, 'profile'])->name('profile');
    Route::put('/profilo', [PageController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profilo/password', [PageController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
// 'skipper_area' gira DOPO 'admin': lo skipper entra nell'area (isAdmin() è
// vero) ma viene confinato alla sola sezione Imbarco. Inerte per gli altri ruoli.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin', 'skipper_area'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/programma', [DashboardController::class, 'schedule'])->name('schedule');

    // Imbarco passeggeri (QR scan per partenza)
    Route::get('/imbarco', [AdminBoardingController::class, 'index'])->name('boarding.index');
    Route::get('/imbarco/{departure}', [AdminBoardingController::class, 'show'])->name('boarding.show');
    Route::get('/imbarco/{departure}/state', [AdminBoardingController::class, 'state'])->name('boarding.state');
    Route::post('/imbarco/{departure}/scan', [AdminBoardingController::class, 'scan'])->name('boarding.scan');
    Route::post('/imbarco/{departure}/seats/{seat}/toggle', [AdminBoardingController::class, 'toggle'])->name('boarding.toggle');

    // Assegnazione catamarani
    Route::get('/assegnazione', [AdminDepartureAssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assegnazione/{departure}', [AdminDepartureAssignmentController::class, 'show'])->name('assignments.show');
    // Spostamento in blocco dei passeggeri selezionati su un altro catamarano.
    Route::post('/assegnazione/{departure}/sposta-selezionati', [AdminDepartureAssignmentController::class, 'moveSeatsBulk'])
        ->name('assignments.move-bulk');

    Route::resource('bookings', AdminBookingController::class);
    // Binding per id: il <select> tour invia l'id numerico, non lo slug (route key di default del Tour).
    Route::get('/bookings-api/tours/{tour:id}/departures', [AdminBookingController::class, 'departuresJson'])->name('bookings.departures.json');
    // Disponibilità catamarani per uso esclusivo su un periodo (date libere).
    Route::get('/bookings-api/tours/{tour:id}/catamaran-availability', [AdminBookingController::class, 'catamaranAvailability'])->name('bookings.catamaran-availability');
    // Centro notifiche: il feed è interrogato dal polling della campanella.
    Route::get('/notifiche', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifiche/feed', [\App\Http\Controllers\Admin\NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifiche/segna-tutte-lette', [\App\Http\Controllers\Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifiche/elimina-tutte', [\App\Http\Controllers\Admin\NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::get('/notifiche/{notification}/apri', [\App\Http\Controllers\Admin\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifiche/{notification}/letta', [\App\Http\Controllers\Admin\NotificationController::class, 'readAjax'])->name('notifications.read-ajax');
    Route::delete('/notifiche/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Completamento in blocco dall'elenco (deve stare prima di /bookings/{booking}).
    Route::post('/bookings/completa-in-blocco', [AdminBookingController::class, 'bulkComplete'])->name('bookings.bulk-complete');
    Route::post('/bookings/{booking}/completa', [AdminBookingController::class, 'complete'])->name('bookings.complete');
    Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/bookings/{booking}/confirm-transfer', [AdminBookingController::class, 'confirmTransfer'])->name('bookings.confirm-transfer');
    // Storno via bonifico eseguito fuori dal sistema: l'admin lo conferma qui.
    Route::post('/bookings/{booking}/confirm-refund', [AdminBookingController::class, 'confirmRefund'])->name('bookings.confirm-refund');
    Route::post('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/refund', [AdminBookingController::class, 'refund'])->name('bookings.refund');
    Route::post('/bookings/{booking}/resend-confirmation', [AdminBookingController::class, 'resendConfirmation'])->name('bookings.resend');
    // Genera/invia il link di pagamento Stripe al cliente (uso admin).
    Route::post('/bookings/{booking}/send-payment-link', [AdminBookingController::class, 'sendPaymentLink'])->name('bookings.send-payment-link');
    // Invia al cliente la richiesta di saldo (link Stripe o istruzioni bonifico).
    Route::post('/bookings/{booking}/send-balance-request', [AdminBookingController::class, 'sendBalanceRequest'])->name('bookings.send-balance-request');
    Route::get('/bookings/{booking}/export', [AdminBookingController::class, 'export'])->name('bookings.export');
    Route::post('/bookings/{booking}/seats/{seat}/move', [AdminBookingController::class, 'moveSeat'])->name('bookings.seats.move');
    // Modifica del documento d'identità di un passeggero (solo admin).
    Route::post('/bookings/{booking}/seats/{seat}/document', [AdminBookingController::class, 'updateSeatDocument'])->name('bookings.seats.document');
    // Sposta un'intera riserva (uso esclusivo) su un altro catamarano.
    Route::post('/bookings/{booking}/move-reservation', [AdminBookingController::class, 'moveReservation'])->name('bookings.move-reservation');
    // Disdetta di singoli partecipanti/extra con eventuale rimborso parziale.
    Route::post('/bookings/{booking}/remove-items', [AdminBookingController::class, 'removeItems'])->name('bookings.remove-items');
    // Anteprima differenza prezzo per un cambio data (JSON).
    Route::get('/bookings/{booking}/reschedule-preview', [AdminBookingController::class, 'reschedulePreview'])->name('bookings.reschedule-preview');
    // Cambio data con conguaglio secondo il metodo di pagamento.
    Route::post('/bookings/{booking}/reschedule', [AdminBookingController::class, 'reschedule'])->name('bookings.reschedule');

    // Tours
    Route::resource('tours', AdminTourController::class);
    Route::post('/tours/{tour}/toggle', [AdminTourController::class, 'toggle'])->name('tours.toggle');
    Route::delete('/tours/{tour}/images/{image}', [AdminTourController::class, 'deleteImage'])->name('tours.images.delete');
    Route::post('/tours/{tour}/images/{image}/primary', [AdminTourController::class, 'setPrimaryImage'])->name('tours.images.primary');

    // Catamarans (flotta)
    Route::resource('catamarans', AdminCatamaranController::class);
    Route::post('/catamarans/{catamaran}/toggle', [AdminCatamaranController::class, 'toggle'])->name('catamarans.toggle');
    Route::post('/catamarans/{catamaran}/images', [AdminCatamaranController::class, 'uploadImages'])->name('catamarans.images.upload');
    Route::delete('/catamarans/{catamaran}/images/{image}', [AdminCatamaranController::class, 'deleteImage'])->name('catamarans.images.delete');
    Route::post('/catamarans/{catamaran}/images/reorder', [AdminCatamaranController::class, 'reorderImages'])->name('catamarans.images.reorder');

    // Addons
    Route::resource('addons', AddonController::class);
    Route::post('/addons/{addon}/toggle', [AddonController::class, 'toggle'])->name('addons.toggle');
    Route::post('/addons/reorder', [AddonController::class, 'reorder'])->name('addons.reorder');

    // Discounts
    Route::resource('discounts', DiscountController::class);
    Route::post('/discounts/{discount}/toggle', [DiscountController::class, 'toggle'])->name('discounts.toggle');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/bookings', [ReportController::class, 'bookings'])->name('reports.bookings');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    // Export Excel completo: un file .xlsx con un foglio per ogni report.
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');

    // Commissioni agenzie B2B (rendicontazione + liquidazione)
    Route::get('/commissioni', [\App\Http\Controllers\Admin\CommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissioni/agenzia/{agency}', [\App\Http\Controllers\Admin\CommissionController::class, 'agency'])->name('commissions.agency');
    Route::post('/commissioni/{booking}/segna-pagata', [\App\Http\Controllers\Admin\CommissionController::class, 'markPaid'])->name('commissions.mark-paid');
    Route::post('/commissioni/segna-pagate', [\App\Http\Controllers\Admin\CommissionController::class, 'markPaidBulk'])->name('commissions.mark-paid-bulk');
    Route::post('/commissioni/{booking}/annulla-pagata', [\App\Http\Controllers\Admin\CommissionController::class, 'unmarkPaid'])->name('commissions.unmark-paid');
    // Risoluzione richieste agenzia (annullamento/modifica)
    Route::post('/bookings/{booking}/richiesta-b2b', [AdminBookingController::class, 'resolveB2bRequest'])->name('bookings.resolve-b2b-request');

    // Payments
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    // Settings
    Route::get('/impostazioni', [SettingsController::class, 'index'])->name('settings');
    Route::post('/impostazioni', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/impostazioni/test-mail', [SettingsController::class, 'sendTestMail'])->name('settings.mail-test');
    Route::post('/impostazioni/orari-prenotazione', [SettingsController::class, 'updateTourCutoffs'])->name('settings.tour-cutoffs');

    // Sezione "Sistema": riservata al ruolo tecnico system_admin (log, deploy, migrazioni).
    Route::middleware('system')->group(function () {
        Route::get('/deploy', [DeployController::class, 'index'])->name('deploy.index');
        Route::post('/deploy/migrate', [DeployController::class, 'migrate'])->name('deploy.migrate');
        Route::post('/deploy/artisan', [DeployController::class, 'artisan'])->name('deploy.artisan');

        // Log diagnostici (grafici + filtri sugli eventi prenotazioni/pagamenti/email).
        Route::get('/sistema/log', [\App\Http\Controllers\Admin\LogController::class, 'index'])->name('system.logs');
    });

    // Users
    Route::resource('users', AdminUserController::class)->except(['show']);

    // Guida operativa (pagine statiche per gli operatori)
    Route::get('/guida', [GuideController::class, 'index'])->name('guide.index');
    Route::get('/guida/{topic}', [GuideController::class, 'show'])->name('guide.show');
});

require __DIR__.'/auth.php';
