<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use App\Models\Complaint;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get dashboard statistics based on user role.
     *
     * @param User $user
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getStatsByRole(User $user, ?string $startDate, ?string $endDate): array
    {
        $role  = $user->role;
        $today = Carbon::today();

        return match ($role) {
            'guest'            => $this->guestStats($user),
            'receptionist'     => $this->receptionistStats($today),
            'customer_service' => $this->customerServiceStats(),
            default            => $this->koordinatorStats($startDate, $endDate),
        };
    }

    /**
     * Stats untuk role Guest.
     *
     * @param User $user
     * @return array
     */
    private function guestStats(User $user): array
    {
        $bookings = Booking::with('facility')->where('user_id', $user->id)->get();
        $activeBookings = $bookings->whereIn('status', ['lunas', 'check_in'])->values();

        return [
            'total_booking'       => $bookings->count(),
            'booking_aktif'       => $activeBookings->count(),
            'booking_selesai'     => $bookings->where('status', 'selesai')->count(),
            'total_aduan'         => Complaint::where('user_id', $user->id)->count(),
            'active_reservations' => $activeBookings,
        ];
    }

    /**
     * Stats untuk role Receptionist.
     *
     * @param Carbon $today
     * @return array
     */
    private function receptionistStats(Carbon $today): array
    {
        return [
            'antrean_checkin'   => Booking::where('status', 'lunas')->count(),
            'tamu_menginap'     => Booking::where('status', 'check_in')->count(),
            'checkin_hari_ini'  => Booking::where('status', 'check_in')
                                           ->whereDate('checked_in_at', $today)
                                           ->count(),
            'checkout_hari_ini' => Booking::where('status', 'selesai')
                                           ->whereDate('checked_out_at', $today)
                                           ->count(),
        ];
    }

    /**
     * Stats untuk role Customer Service.
     *
     * @return array
     */
    private function customerServiceStats(): array
    {
        return [
            'total_keluhan'      => Complaint::count(),
            'keluhan_pending'    => Complaint::where('status', 'pending')->count(),
            'keluhan_proses'     => Complaint::where('status', 'processed')->count(),
            'keluhan_konfirmasi' => Complaint::where('status', 'resolved')->where('is_guest_confirmed', false)->count(),
            'keluhan_selesai'    => Complaint::where('status', 'resolved')->where('is_guest_confirmed', true)->count(),
        ];
    }

    /**
     * Stats untuk role Koordinator Wisma (admin).
     *
     * @param string|null $startDateStr
     * @param string|null $endDateStr
     * @return array
     */
    private function koordinatorStats(?string $startDateStr, ?string $endDateStr): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate   = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::now()->endOfMonth();

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
                'end'   => $endDate->toDateString()
            ],
            'total_transaksi' => $totalTransaksi,
            'tamu_terdaftar'  => $tamuTerdaftar,
            'pendapatan'      => (float) $pendapatan,
            'fasilitas' => [
                'tersedia'    => Facility::where('status', 'READY')->count(),
                'terpakai'    => Facility::where('status', 'OCCUPIED')->count(),
                'pembersihan' => Facility::where('status', 'CLEANING')->count(),
                'perbaikan'   => Facility::where('status', 'MAINTENANCE')->count(),
            ]
        ];
    }
}
