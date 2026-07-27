<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Laporan keuangan & okupansi dengan filter periode.
     * Hanya Koordinator Wisma.
     *
     * GET /api/reports/financial
     */
    public function financial(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $data = $this->reportService->getFinancialReport(
            $request->start_date,
            $request->end_date,
            $request->status,
            $request->search
        );

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Unduh Laporan PDF (Koordinator Wisma).
     *
     * GET /api/reports/financial/export-pdf
     */
    public function exportFinancialPdf(Request $request)
    {
        $bookings = $this->reportService->getBookingsForExport(
            $request->start_date,
            $request->end_date,
            $request->status,
            $request->search
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.financial', compact('bookings', 'request'))
            ->setPaper('a4', 'landscape');
        
        return $pdf->download('laporan-wisma-' . date('Ymd') . '.pdf');
    }

    /**
     * Database tamu (direktori akun tamu) & riwayat log transaksi reservasi.
     * Hanya Koordinator Wisma.
     *
     * GET /api/reports/master-guests
     */
    public function masterGuests(Request $request): JsonResponse
    {
        $guests = $this->reportService->getMasterGuests();

        return response()->json([
            'success' => true,
            'data'    => $guests,
            'total'   => $guests->count(),
        ]);
    }

    /**
     * GET /api/reports/booking-logs
     */
    public function bookingLogs(Request $request): JsonResponse
    {
        $bookings = $this->reportService->getBookingLogs(
            $request->start_date,
            $request->end_date,
            $request->status,
            $request->search
        );

        return response()->json([
            'success' => true,
            'data'    => $bookings,
            'total'   => $bookings->count(),
        ]);
    }
}
