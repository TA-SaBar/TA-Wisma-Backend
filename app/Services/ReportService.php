<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get financial and occupancy report data.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $status
     * @param string|null $search
     * @return array
     */
    public function getFinancialReport(?string $startDate, ?string $endDate, ?string $status, ?string $search): array
    {
        $query = Booking::with(['facility', 'user'])
            ->whereIn('status', ['lunas', 'check_in', 'selesai'])
            ->orderBy('paid_at');

        if ($status && $status !== 'semua') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', '%' . $search . '%')
                  ->orWhere('guest_nip', 'like', '%' . $search . '%')
                  ->orWhere('booking_code', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', '%' . $search . '%')
                         ->orWhere('nip', 'like', '%' . $search . '%');
                  });
            });
        }

        $sDate = null;
        $eDate = null;

        if ($startDate && $endDate) {
            $sDate = Carbon::parse($startDate)->startOfDay();
            $eDate = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('paid_at', [$sDate, $eDate]);
        }

        $bookings = $query->get();

        $totalPendapatan = $bookings->sum('total_price');
        $totalMalam      = $bookings->sum('nights');

        $distribusiFasilitas = $bookings->groupBy('facility_id')->map(function ($group) {
            $facility = $group->first()->facility;
            return [
                'facility_id'   => $facility ? $facility->id : null,
                'facility_name' => $facility ? $facility->name : 'N/A',
                'gedung'        => $facility ? $facility->gedung : 'N/A',
                'total_booking' => $group->count(),
                'total_malam'   => $group->sum('nights'),
                'total_revenue' => $group->sum('total_price'),
            ];
        })->values();

        $fasilitasSaatIni = [
            'total'    => Facility::count(),
            'ready'    => Facility::where('status', 'READY')->count(),
            'occupied' => Facility::where('status', 'OCCUPIED')->count(),
            'cleaning' => Facility::where('status', 'CLEANING')->count(),
        ];

        return [
            'periode' => [
                'start_date' => $sDate ? $sDate->toDateString() : 'Semua',
                'end_date'   => $eDate ? $eDate->toDateString() : 'Waktu',
            ],
            'ringkasan' => [
                'total_pendapatan' => (float) $totalPendapatan,
                'total_subtotal'   => (float) $bookings->sum('subtotal'),
                'total_pajak'      => (float) $bookings->sum('tax'),
                'total_transaksi'  => $bookings->count(),
                'total_malam'      => $totalMalam,
                'rata_per_malam'   => $totalMalam > 0 ? round($totalPendapatan / $totalMalam, 2) : 0,
            ],
            'fasilitas_saat_ini'   => $fasilitasSaatIni,
            'distribusi_fasilitas' => $distribusiFasilitas,
            'transaksi'            => $bookings,
        ];
    }

    /**
     * Get booking data for PDF export.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $status
     * @param string|null $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBookingsForExport(?string $startDate, ?string $endDate, ?string $status, ?string $search)
    {
        $query = Booking::with(['facility', 'user'])->latest();

        if ($status && $status !== 'semua') {
            $query->where('status', $status);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('check_in', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', '%' . $search . '%')
                  ->orWhere('guest_nip', 'like', '%' . $search . '%')
                  ->orWhere('booking_code', 'like', '%' . $search . '%');
            });
        }

        return $query->get();
    }

    /**
     * Get master guest list with booking aggregates.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMasterGuests()
    {
        return User::where('role', 'guest')
            ->withCount('bookings')
            ->orderBy('name')
            ->get()
            ->map(function ($g) {
                return [
                    'id'             => $g->id,
                    'name'           => $g->name,
                    'nip'            => $g->nip,
                    'email'          => $g->email,
                    'phone'          => $g->phone,
                    'instansi'       => $g->instansi,
                    'total_booking'  => $g->bookings_count,
                    'last_visit_at'  => $g->last_visit_at,
                ];
            });
    }

    /**
     * Get booking logs for history tab.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $status
     * @param string|null $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBookingLogs(?string $startDate, ?string $endDate, ?string $status, ?string $search)
    {
        $query = Booking::with(['facility', 'user'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', '%' . $search . '%')
                  ->orWhere('guest_name', 'like', '%' . $search . '%')
                  ->orWhere('guest_nip', 'like', '%' . $search . '%');
            });
        }

        return $query->get();
    }
}
