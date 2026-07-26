<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Wisma DPR RI Backend
|--------------------------------------------------------------------------
|
| Iterasi 1: Auth + Profil + Facility CRUD
| Iterasi 2: Booking + Midtrans + Check-in/out + Dashboard
| Iterasi 3: Keluhan + Rating/Feedback + Laporan Keuangan + Data Induk
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

    // --- Profil (FR-02) — semua role ---
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // --- Notifikasi (FR-03) ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // --- Dashboard Stats (FR-04) ---
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/guests', [ReportController::class, 'masterGuests']);

    // --- Facilities Read (semua role) ---
    Route::get('/facilities', [FacilityController::class, 'index']);
    Route::get('/facilities/{facility}', [FacilityController::class, 'show']);
    Route::get('/facilities/{facility}/booked-dates', [FacilityController::class, 'bookedDates']);

    // --- Facilities CUD (hanya koordinator wisma) ---
    Route::middleware('role:koordinator_wisma')->group(function () {
        Route::post('/facilities', [FacilityController::class, 'store']);
        Route::put('/facilities/{facility}', [FacilityController::class, 'update']);
        Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy']);
    });

    // --- Bookings (FR-06, FR-07) ---
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::middleware('role:guest')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
    });
    Route::post('/bookings/{booking}/check-status', [BookingController::class, 'checkStatus']);

    // --- Feedback per Booking (FR-08) — hanya guest pemilik booking ---
    Route::post('/bookings/{booking}/feedback', [FeedbackController::class, 'store']);

    // --- Check-in & Check-out (FR-09) — hanya resepsionis ---
    Route::middleware('role:receptionist')->group(function () {
        Route::put('/bookings/{booking}/checkin', [BookingController::class, 'checkIn']);
        Route::put('/bookings/{booking}/checkout', [BookingController::class, 'checkOut']);
    });

    // --- Unduh Tiket PDF (FR-07.02) ---
    Route::get('/bookings/{booking}/ticket', [BookingController::class, 'exportTicketPdf']);

    // --- Keluhan / Complaints (FR-08 Guest, FR-13 CS) ---
    Route::get('/complaints', [ComplaintController::class, 'index']);
    Route::put('/complaints/{complaint}/confirm', [ComplaintController::class, 'confirm']);
    Route::post('/complaints', [ComplaintController::class, 'store']);

    // --- Proses & Selesaikan Keluhan (FR-13) — hanya customer_service & koordinator ---
    Route::middleware('role:customer_service,koordinator_wisma')->group(function () {
        Route::put('/complaints/{complaint}/process', [ComplaintController::class, 'process']);
        Route::put('/complaints/{complaint}/resolve', [ComplaintController::class, 'resolve']);
        
        // FR-13.06: Buat tiket keluhan manual (tamu melapor offline)
        Route::post('/complaints/manual', [ComplaintController::class, 'storeManual']);
    });

    // --- Feedback Aggregation (FR-13) — hanya customer_service & koordinator ---
    Route::middleware('role:customer_service,koordinator_wisma')->group(function () {
        Route::get('/feedbacks', [FeedbackController::class, 'index']);
    });

    // --- Cetak Laporan PDF (Khusus Customer Service) ---
    Route::middleware('role:customer_service')->group(function () {
        Route::get('/complaints/export-pdf', [ComplaintController::class, 'exportPdf']);
        Route::get('/feedbacks/export-pdf', [FeedbackController::class, 'exportPdf']);
    });

    // --- Laporan Keuangan & Data Induk (FR-11, FR-12) — hanya koordinator wisma ---
    Route::middleware('role:koordinator_wisma')->group(function () {
        Route::get('/reports/financial', [ReportController::class, 'financial']);
        Route::get('/reports/financial/export-pdf', [ReportController::class, 'exportFinancialPdf']);
        Route::get('/reports/master-guests', [ReportController::class, 'masterGuests']);
        Route::get('/reports/booking-logs', [ReportController::class, 'bookingLogs']);
    });
});
