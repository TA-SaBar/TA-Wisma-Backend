<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Wisma DPR RI Backend
|--------------------------------------------------------------------------
|
| Iterasi 1: Auth + Profil + Facility CRUD
| Iterasi 2: Booking + Midtrans + Check-in/out + Dashboard
|
*/

// ============================================================
// PUBLIC ROUTES (Tidak perlu autentikasi)
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// Midtrans Webhook — public, dipanggil oleh server Midtrans
Route::post('/midtrans/webhook', [BookingController::class, 'midtransWebhook']);

// ============================================================
// AUTHENTICATED ROUTES (Perlu token Sanctum)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Profil (FR-02) — semua role bisa update profil sendiri ---
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // --- Notifikasi (FR-03) ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // --- Dashboard Stats (FR-04) ---
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // --- Facilities (Read — semua role bisa akses) ---
    Route::get('/facilities', [FacilityController::class, 'index']);
    Route::get('/facilities/{facility}', [FacilityController::class, 'show']);

    // --- Facilities (CUD — hanya koordinator wisma) ---
    Route::middleware('role:koordinator_wisma')->group(function () {
        Route::post('/facilities', [FacilityController::class, 'store']);
        Route::put('/facilities/{facility}', [FacilityController::class, 'update']);
        Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy']);
    });

    // --- Bookings (FR-06, FR-07) ---
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);

    // --- Check-in & Check-out (FR-09) — hanya resepsionis ---
    Route::middleware('role:receptionist')->group(function () {
        Route::put('/bookings/{booking}/checkin', [BookingController::class, 'checkIn']);
        Route::put('/bookings/{booking}/checkout', [BookingController::class, 'checkOut']);
    });
});
