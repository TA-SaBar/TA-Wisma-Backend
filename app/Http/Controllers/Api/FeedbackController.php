<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Booking;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Tampilkan semua feedback/rating (hanya Customer Service & Koordinator).
     * Termasuk agregasi rata-rata rating keseluruhan.
     *
     * GET /api/feedbacks
     */
    public function index(Request $request): JsonResponse
    {
        $feedbacks = Feedback::with(['booking.facility', 'user'])->latest()->get();

        // Agregasi rata-rata keseluruhan
        $aggregation = [
            'total'               => $feedbacks->count(),
            'avg_cleanliness'     => $feedbacks->avg('rating_cleanliness'),
            'avg_facilities'      => $feedbacks->avg('rating_facilities'),
            'avg_service'         => $feedbacks->avg('rating_service'),
            'avg_overall'         => $feedbacks->avg('average_rating'),
        ];

        return response()->json([
            'success'     => true,
            'data'        => $feedbacks,
            'aggregation' => $aggregation,
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
        $user = $request->user();
        $data = $request->validated();

        // Hanya pemilik booking yang bisa memberi feedback
        if ($booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk memberikan ulasan pada pemesanan ini.',
            ], 403);
        }

        // Hanya booking yang sudah selesai yang bisa diberi feedback
        if ($booking->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Ulasan hanya dapat diberikan setelah masa inap selesai (status: Selesai).',
            ], 422);
        }

        // Cek apakah feedback sudah pernah dikirim untuk booking ini
        if (Feedback::where('booking_id', $booking->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah pernah memberikan ulasan untuk pemesanan ini.',
            ], 422);
        }

        // Hitung rata-rata dari 3 dimensi rating
        $avgRating = round(
            ($data['rating_cleanliness'] + $data['rating_facilities'] + $data['rating_service']) / 3,
            2
        );

        $feedback = Feedback::create([
            'booking_id'         => $booking->id,
            'user_id'            => $user->id,
            'rating_cleanliness' => $data['rating_cleanliness'],
            'rating_facilities'  => $data['rating_facilities'],
            'rating_service'     => $data['rating_service'],
            'average_rating'     => $avgRating,
            'comment'            => $data['comment'] ?? null,
        ]);


        // Kirim Notifikasi ke Customer Service
        $csUsers = \App\Models\User::where('role', 'customer_service')->get();
        foreach ($csUsers as $cs) {
            \App\Models\Notification::create([
                'user_id'      => $cs->id,
                'type'         => 'rating',
                'title'        => 'Ulasan & Rating Baru',
                'message'      => "Tamu {$user->name} memberikan ulasan baru dengan skor rata-rata {$avgRating} Bintang.",
                'related_id'   => $feedback->id,
                'related_type' => 'new_rating',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Ulasan Anda berhasil dikirim.',
            'data'    => $feedback,
        ], 201);
    }

    /**
     * Cetak PDF Laporan Ulasan
     *
     * GET /api/feedbacks/export-pdf
     */
    public function exportPdf(Request $request)
    {
        $feedbacks = Feedback::with(['booking.facility', 'user'])->latest()->get();

        $aggregation = [
            'total'               => $feedbacks->count(),
            'avg_cleanliness'     => $feedbacks->avg('rating_cleanliness'),
            'avg_facilities'      => $feedbacks->avg('rating_facilities'),
            'avg_service'         => $feedbacks->avg('rating_service'),
            'avg_overall'         => $feedbacks->avg('average_rating'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.feedbacks', compact('feedbacks', 'aggregation', 'request'))->setPaper('a4', 'landscape');
        
        return $pdf->download('laporan-ulasan-' . date('Ymd') . '.pdf');
    }
}
