<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\User;

class FeedbackService
{
    /**
     * Ambil semua feedback beserta data agregasi (rata-rata skor).
     *
     * @return array
     */
    public function getFeedbacks(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Feedback::with(['booking.facility', 'user'])->latest();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            ]);
        }

        $feedbacks = $query->get();

        $aggregation = [
            'total'               => $feedbacks->count(),
            'avg_cleanliness'     => $feedbacks->avg('rating_cleanliness'),
            'avg_facilities'      => $feedbacks->avg('rating_facilities'),
            'avg_service'         => $feedbacks->avg('rating_service'),
            'avg_overall'         => $feedbacks->avg('average_rating'),
        ];

        return [
            'feedbacks'   => $feedbacks,
            'aggregation' => $aggregation,
        ];
    }

    /**
     * Submit feedback untuk suatu booking.
     *
     * @param Booking $booking
     * @param User $user
     * @param array $data
     * @return Feedback|array  Mengembalikan Feedback jika sukses, array ['error' => true, 'message' => ..., 'status' => ...] jika gagal
     */
    public function submitFeedback(Booking $booking, User $user, array $data)
    {
        if ($booking->user_id !== $user->id) {
            return [
                'error'   => true,
                'message' => 'Anda tidak memiliki akses untuk memberikan ulasan pada pemesanan ini.',
                'status'  => 403,
            ];
        }

        if ($booking->status !== 'selesai') {
            return [
                'error'   => true,
                'message' => 'Ulasan hanya dapat diberikan setelah masa inap selesai (status: Selesai).',
                'status'  => 422,
            ];
        }

        if (Feedback::where('booking_id', $booking->id)->exists()) {
            return [
                'error'   => true,
                'message' => 'Anda sudah pernah memberikan ulasan untuk pemesanan ini.',
                'status'  => 422,
            ];
        }

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

        $csUsers = User::where('role', 'customer_service')->get();
        foreach ($csUsers as $cs) {
            Notification::create([
                'user_id'      => $cs->id,
                'type'         => 'rating',
                'title'        => 'Ulasan & Rating Baru',
                'message'      => "Tamu {$user->name} memberikan ulasan baru dengan skor rata-rata {$avgRating} Bintang.",
                'related_id'   => $feedback->id,
                'related_type' => 'new_rating',
            ]);
        }

        return $feedback;
    }
}
