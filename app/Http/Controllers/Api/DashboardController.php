<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics based on user role.
     *
     * GET /api/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user  = $request->user();
        $role  = $user->role;
        $today = Carbon::today();

        $stats = match ($role) {
            'guest' => $this->guestStats($user),
            'receptionist' => $this->receptionistStats($today),
            'customer_service' => $this->customerServiceStats(),
            default => $this->koordinatorStats($request),
        };

        return response()->json([
            'success' => true,
            'data'    => $stats,
            'role'    => $role,
        ]);
    }

    /**
     * Stats untuk role Guest.
     */
    private function guestStats($user): array
    {
        $bookings = Booking::with('facility')->where('user_id', $user->id)->get();
        $activeBookings = $bookings->whereIn('status', ['lunas', 'check_in'])->values();

        return [
            'total_booking'    => $bookings->count(),
            'booking_aktif'    => $activeBookings->count(),
            'booking_selesai'  => $bookings->where('status', 'selesai')->count(),
            'total_aduan'      => \App\Models\Complaint::where('user_id', $user->id)->count(),
            'active_reservations' => $activeBookings,
        ];
    }

    /**
     * Stats untuk role Receptionist.
     */
    private function receptionistStats(Carbon $today): array
    {
        return [
            'antrean_checkin'  => Booking::where('status', 'lunas')->count(),
            'tamu_menginap'    => Booking::where('status', 'check_in')->count(),
            'checkin_hari_ini' => Booking::where('status', 'check_in')
                                          ->whereDate('checked_in_at', $today)
                                          ->count(),
            'checkout_hari_ini'=> Booking::where('status', 'selesai')
                                          ->whereDate('checked_out_at', $today)
                                          ->count(),
        ];
    }

    /**
     * Stats untuk role Customer Service.
     */
    private function customerServiceStats(): array
    {
        return [
            'total_keluhan'      => \App\Models\Complaint::count(),
            'keluhan_pending'    => \App\Models\Complaint::where('status', 'pending')->count(),
            'keluhan_proses'     => \App\Models\Complaint::where('status', 'processed')->count(),
            'keluhan_konfirmasi' => \App\Models\Complaint::where('status', 'resolved')->where('is_guest_confirmed', false)->count(),
            'keluhan_selesai'    => \App\Models\Complaint::where('status', 'resolved')->where('is_guest_confirmed', true)->count(),
        ];
    }

    /**
     * Stats untuk role Koordinator Wisma (admin).
     */
    private function koordinatorStats(Request $request): array
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        $activeStatuses = ['lunas', 'check_in', 'selesai'];

        $totalTransaksi = Booking::whereBetween('check_in', [$startDate, $endDate])->count();
        
        $tamuTerdaftar = Booking::whereBetween('check_in', [$startDate, $endDate])
            ->whereIn('status', $activeStatuses)
            ->count();

        $pendapatan = Booking::whereBetween('check_in', [$startDate, $endDate])
            ->whereIn('status', $activeStatuses)
            ->sum('total_price');

        return [
            'periode' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'total_transaksi' => $totalTransaksi,
            'tamu_terdaftar'  => $tamuTerdaftar,
            'pendapatan'      => (float) $pendapatan,
            'fasilitas' => [
                'tersedia' => Facility::where('status', 'READY')->count(),
                'terpakai' => Facility::where('status', 'OCCUPIED')->count(),
                'pembersihan' => Facility::where('status', 'CLEANING')->count(),
                'perbaikan' => Facility::where('status', 'MAINTENANCE')->count(),
            ]
        ];
    }
}
