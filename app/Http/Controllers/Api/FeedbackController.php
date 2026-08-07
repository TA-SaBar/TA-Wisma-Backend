<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Booking;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * Tampilkan semua feedback/rating (hanya Customer Service & Koordinator).
     * Termasuk agregasi rata-rata rating keseluruhan.
     *
     * GET /api/feedbacks
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->feedbackService->getFeedbacks($request->start_date, $request->end_date);

        return response()->json([
            'success'     => true,
            'data'        => $result['feedbacks'],
            'aggregation' => $result['aggregation'],
        ]);
    }

    /**
     * Tamu mengirimkan feedback untuk booking yang sudah selesai.
     * Setiap booking hanya boleh mendapatkan satu feedback (unique).
     *
     * POST /api/bookings/{booking}/feedback
     */
    public function store(StoreFeedbackRequest $request, Booking $booking): JsonResponse
    {
        $result = $this->feedbackService->submitFeedback($booking, $request->user(), $request->validated());

        if (is_array($result) && isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Ulasan Anda berhasil dikirim.',
            'data'    => $result,
        ], 201);
    }

    /**
     * Cetak PDF Laporan Ulasan
     *
     * GET /api/feedbacks/export-pdf
     */
    public function exportPdf(Request $request)
    {
        $result = $this->feedbackService->getFeedbacks($request->start_date, $request->end_date);
        $feedbacks = $result['feedbacks'];
        $aggregation = $result['aggregation'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.feedbacks', compact('feedbacks', 'aggregation', 'request'))->setPaper('a4', 'landscape');
        
        return $pdf->download('laporan-ulasan-' . date('Ymd') . '.pdf');
    }
}
