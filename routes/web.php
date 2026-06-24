<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\PageController;
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
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

// Tours (entry-point pubblico per prenotazione)
Route::get('/tour', [TourController::class, 'index'])->name('tours.index');
Route::get('/tour/{slug}', [TourController::class, 'show'])->name('tours.show');
Route::get('/api/departures/{departure}/availability', [TourController::class, 'checkDeparture'])->name('api.departure.availability');

// Redirect compatibilità: vecchio /catamarani -> /tour
Route::redirect('/catamarani', '/tour');

// Booking flow
Route::get('/prenota', [BookingController::class, 'start'])->name('booking.start');
Route::post('/prenota', [BookingController::class, 'store'])->name('booking.store');
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

// Static
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/termini-condizioni', [PageController::class, 'terms'])->name('terms');
Route::get('/cookie-policy', [PageController::class, 'cookies'])->name('cookies');

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
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
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

    Route::resource('bookings', AdminBookingController::class);
    // Binding per id: il <select> tour invia l'id numerico, non lo slug (route key di default del Tour).
    Route::get('/bookings-api/tours/{tour:id}/departures', [AdminBookingController::class, 'departuresJson'])->name('bookings.departures.json');
    // Disponibilità catamarani per uso esclusivo su un periodo (date libere).
    Route::get('/bookings-api/tours/{tour:id}/catamaran-availability', [AdminBookingController::class, 'catamaranAvailability'])->name('bookings.catamaran-availability');
    Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/bookings/{booking}/confirm-transfer', [AdminBookingController::class, 'confirmTransfer'])->name('bookings.confirm-transfer');
    Route::post('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/refund', [AdminBookingController::class, 'refund'])->name('bookings.refund');
    Route::post('/bookings/{booking}/resend-confirmation', [AdminBookingController::class, 'resendConfirmation'])->name('bookings.resend');
    // Genera/invia il link di pagamento Stripe al cliente (uso admin).
    Route::post('/bookings/{booking}/send-payment-link', [AdminBookingController::class, 'sendPaymentLink'])->name('bookings.send-payment-link');
    // Invia al cliente la richiesta di saldo (link Stripe o istruzioni bonifico).
    Route::post('/bookings/{booking}/send-balance-request', [AdminBookingController::class, 'sendBalanceRequest'])->name('bookings.send-balance-request');
    Route::get('/bookings/{booking}/export', [AdminBookingController::class, 'export'])->name('bookings.export');
    Route::post('/bookings/{booking}/seats/{seat}/move', [AdminBookingController::class, 'moveSeat'])->name('bookings.seats.move');
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

    // Payments
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    // Settings
    Route::get('/impostazioni', [SettingsController::class, 'index'])->name('settings');
    Route::post('/impostazioni', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/impostazioni/test-mail', [SettingsController::class, 'sendTestMail'])->name('settings.mail-test');

    // Deploy & Migrations
    Route::get('/deploy', [DeployController::class, 'index'])->name('deploy.index');
    Route::post('/deploy/migrate', [DeployController::class, 'migrate'])->name('deploy.migrate');
    Route::post('/deploy/artisan', [DeployController::class, 'artisan'])->name('deploy.artisan');

    // Users
    Route::resource('users', AdminUserController::class)->except(['show']);

    // Guida operativa (pagine statiche per gli operatori)
    Route::get('/guida', [GuideController::class, 'index'])->name('guide.index');
    Route::get('/guida/{topic}', [GuideController::class, 'show'])->name('guide.show');
});

require __DIR__.'/auth.php';
