<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [PaymentController::class, 'webhook']);

// Auth (public)
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Appointments
        Route::get('/appointments/history', [AppointmentController::class, 'history']);
        Route::patch('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm']);
        Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);
        Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::apiResource('appointments', AppointmentController::class);

        // Availability
        Route::get('/availability', [AvailabilityController::class, 'index']);

        // Payments
        Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
        Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
    });
});
