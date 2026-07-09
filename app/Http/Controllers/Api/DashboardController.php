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
            default => $this->koordinatorStats($today),
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
        $bookings = Booking::where('user_id', $user->id)->get();

        return [
            'total_booking'    => $bookings->count(),
            'booking_aktif'    => $bookings->whereIn('status', ['pending', 'lunas', 'check_in'])->count(),
            'booking_selesai'  => $bookings->where('status', 'selesai')->count(),
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
     * Stats untuk role Koordinator Wisma (admin).
     */
    private function koordinatorStats(Carbon $today): array
    {
        $thisMonthStart = $today->copy()->startOfMonth();
        $thisMonthEnd   = $today->copy()->endOfMonth();

        $pendapatan = Booking::whereIn('status', ['lunas', 'check_in', 'selesai'])
                             ->whereBetween('paid_at', [$thisMonthStart, $thisMonthEnd])
                             ->sum('total_price');

        return [
            'total_booking_bulan_ini' => Booking::whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count(),
            'pendapatan_bulan_ini'    => (float) $pendapatan,
            'fasilitas_tersedia'      => Facility::where('status', 'READY')->count(),
            'fasilitas_terpakai'      => Facility::where('status', 'OCCUPIED')->count(),
            'tamu_menginap'           => Booking::where('status', 'check_in')->count(),
        ];
    }
}
