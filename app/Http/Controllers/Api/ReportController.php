<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Laporan keuangan & okupansi dengan filter periode.
     * Hanya Koordinator Wisma.
     *
     * GET /api/reports/financial
     */
    public function financial(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        // Query booking yang sudah lunas dalam periode
        $bookings = Booking::with(['facility', 'user'])
            ->whereIn('status', ['lunas', 'check_in', 'selesai'])
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->orderBy('paid_at')
            ->get();

        // Kalkulasi agregat keuangan
        $totalPendapatan = $bookings->sum('total_price');
        $totalSubtotal   = $bookings->sum('subtotal');
        $totalPajak      = $bookings->sum('tax');
        $totalTransaksi  = $bookings->count();
        $totalMalam      = $bookings->sum('nights');

        // Distribusi per fasilitas (okupansi)
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

        // Statistik fasilitas saat ini
        $fasilitasSaatIni = [
            'total'    => Facility::count(),
            'ready'    => Facility::where('status', 'READY')->count(),
            'occupied' => Facility::where('status', 'OCCUPIED')->count(),
            'cleaning' => Facility::where('status', 'CLEANING')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'periode' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date'   => $endDate->toDateString(),
                ],
                'ringkasan' => [
                    'total_pendapatan' => (float) $totalPendapatan,
                    'total_subtotal'   => (float) $totalSubtotal,
                    'total_pajak'      => (float) $totalPajak,
                    'total_transaksi'  => $totalTransaksi,
                    'total_malam'      => $totalMalam,
                    'rata_per_malam'   => $totalMalam > 0 ? round($totalPendapatan / $totalMalam, 2) : 0,
                ],
                'fasilitas_saat_ini'   => $fasilitasSaatIni,
                'distribusi_fasilitas' => $distribusiFasilitas,
                'transaksi'            => $bookings,
            ],
        ]);
    }

    /**
     * Database tamu (direktori akun tamu) & riwayat log transaksi reservasi.
     * Hanya Koordinator Wisma.
     *
     * GET /api/reports/master-guests
     * GET /api/reports/booking-logs
     */
    public function masterGuests(Request $request): JsonResponse
    {
        $guests = User::where('role', 'guest')
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

        return response()->json([
            'success' => true,
            'data'    => $guests,
            'total'   => $guests->count(),
        ]);
    }

    public function bookingLogs(Request $request): JsonResponse
    {
        $query = Booking::with(['facility', 'user'])->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', '%' . $search . '%')
                  ->orWhere('guest_name', 'like', '%' . $search . '%')
                  ->orWhere('guest_nip', 'like', '%' . $search . '%');
            });
        }

        $bookings = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $bookings,
            'total'   => $bookings->count(),
        ]);
    }
}
