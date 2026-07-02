<?php

use App\Http\Controllers\Api\AuthController;
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
|
*/

// ============================================================
// PUBLIC ROUTES (Tidak perlu autentikasi)
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// AUTHENTICATED ROUTES (Perlu token Sanctum)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Profil (FR-02) — semua role bisa update profil sendiri ---
    Route::put('/profile', [ProfileController::class, 'update']);

    // --- Notifikasi (FR-03) ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // --- Facilities (Read — semua role bisa akses) ---
    Route::get('/facilities', [FacilityController::class, 'index']);
    Route::get('/facilities/{facility}', [FacilityController::class, 'show']);

    // --- Facilities (CUD — hanya koordinator wisma) ---
    Route::middleware('role:koordinator_wisma')->group(function () {
        Route::post('/facilities', [FacilityController::class, 'store']);
        Route::put('/facilities/{facility}', [FacilityController::class, 'update']);
        Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy']);
    });
});
