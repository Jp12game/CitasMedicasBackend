<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::patch('dashboard/patient-profile', [DashboardController::class, 'updatePatientProfile'])
        ->name('dashboard.patient-profile.update');
    Route::post('dashboard/appointments', [DashboardController::class, 'storeAppointment'])
        ->name('dashboard.appointments.store');
    Route::post('dashboard/payments/{payment}/finalize', [DashboardController::class, 'finalizePayment'])
        ->name('dashboard.payments.finalize');
    Route::post('dashboard/payments/{payment}/simulate', [DashboardController::class, 'simulatePayment'])
        ->name('dashboard.payments.simulate');
});

require __DIR__.'/settings.php';
