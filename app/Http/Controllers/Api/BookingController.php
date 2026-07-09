<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Notification as MidtransNotification;

class BookingController extends Controller
{
    public function __construct()
    {
        MidtransConfig::$serverKey    = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = config('services.midtrans.is_production');
        MidtransConfig::$isSanitized  = config('services.midtrans.is_sanitized');
        MidtransConfig::$is3ds        = config('services.midtrans.is_3ds');
    }

    /**
     * Display a listing of bookings.
     * - Guest: hanya bookingnya sendiri
     * - Receptionist & Koordinator: semua booking
     *
     * GET /api/bookings
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::with(['facility'])->latest();

        if ($user->role === 'guest') {
            $query->where('user_id', $user->id);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search by booking code or guest name
        if ($request->has('search') && $request->search !== '') {
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

    /**
     * Create a new booking and generate Midtrans Snap Token.
     *
     * POST /api/bookings
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $user     = $request->user();
        $data     = $request->validated();
        $facility = Facility::findOrFail($data['facility_id']);

        // Kalkulasi durasi dan harga
        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights   = $checkIn->diffInDays($checkOut);

        if ($nights < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Durasi minimal 1 malam/hari.',
            ], 422);
        }

        $subtotal   = $nights * $facility->price;
        $tax        = $subtotal * 0.11;
        $totalPrice = $subtotal + $tax;

        // Generate booking code & midtrans order id
        $bookingCode     = Booking::generateBookingCode();
        $midtransOrderId = 'WDPR-' . time() . '-' . $user->id;

        // Buat record booking
        $booking = Booking::create([
            'booking_code'      => $bookingCode,
            'user_id'           => $user->id,
            'facility_id'       => $facility->id,
            'check_in'          => $data['check_in'],
            'check_out'         => $data['check_out'],
            'nights'            => $nights,
            'subtotal'          => $subtotal,
            'tax'               => $tax,
            'total_price'       => $totalPrice,
            'status'            => 'pending',
            'guest_name'        => $data['guest_name'],
            'guest_nip'         => $data['guest_nip'] ?? null,
            'guest_phone'       => $data['guest_phone'] ?? null,
            'guest_email'       => $data['guest_email'] ?? null,
            'midtrans_order_id' => $midtransOrderId,
        ]);

        // Generate Midtrans Snap Token
        try {
            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id'     => $midtransOrderId,
                    'gross_amount' => (int) round($totalPrice),
                ],
                'item_details' => [
                    [
                        'id'       => $facility->id,
                        'price'    => (int) round($facility->price),
                        'quantity' => $nights,
                        'name'     => $facility->name . ' (' . $nights . ' malam/hari)',
                    ],
                ],
                'customer_details' => [
                    'first_name' => $data['guest_name'],
                    'email'      => $data['guest_email'] ?? $user->email,
                    'phone'      => $data['guest_phone'] ?? $user->phone,
                ],
            ]);

            $booking->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            // Jika Midtrans gagal, booking tetap dibuat tapi tanpa snap_token
            $snapToken = null;
        }

        $booking->load('facility');

        return response()->json([
            'success'    => true,
            'message'    => 'Booking berhasil dibuat. Lanjutkan ke pembayaran.',
            'data'       => $booking,
            'snap_token' => $snapToken,
        ], 201);
    }

    /**
     * Handle Midtrans webhook callback.
     * PUBLIC — tidak perlu auth.
     *
     * POST /api/midtrans/webhook
     */
    public function midtransWebhook(Request $request): JsonResponse
    {
        try {
            $notification = new MidtransNotification();

            $orderId           = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus       = $notification->fraud_status;
            $paymentType       = $notification->payment_type;

            $booking = Booking::where('midtrans_order_id', $orderId)->first();

            if (!$booking) {
                return response()->json(['message' => 'Booking not found.'], 404);
            }

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                if ($fraudStatus === 'accept' || $transactionStatus === 'settlement') {
                    $booking->update([
                        'status'         => 'lunas',
                        'payment_method' => $paymentType,
                        'paid_at'        => now(),
                    ]);

                    // Kirim notifikasi ke user
                    Notification::create([
                        'user_id' => $booking->user_id,
                        'type'    => 'payment',
                        'title'   => 'Pembayaran Berhasil',
                        'message' => "Pembayaran untuk booking {$booking->booking_code} telah berhasil. Silakan check-in sesuai jadwal.",
                        'is_read' => false,
                    ]);
                }
            } elseif ($transactionStatus === 'cancel' || $transactionStatus === 'deny' || $transactionStatus === 'expire') {
                $booking->update(['status' => 'cancelled']);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process check-in for a booking.
     * Hanya Resepsionis.
     *
     * PUT /api/bookings/{booking}/checkin
     */
    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status !== 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Check-in hanya bisa dilakukan untuk booking yang sudah lunas.',
            ], 422);
        }

        $booking->update([
            'status'         => 'check_in',
            'checked_in_at'  => now(),
        ]);

        // Update status fasilitas menjadi OCCUPIED
        $booking->facility->update(['status' => 'OCCUPIED']);

        // Kirim notifikasi ke guest
        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'checkin',
            'title'   => 'Check-In Berhasil',
            'message' => "Anda resmi check-in untuk booking {$booking->booking_code}. Selamat menikmati fasilitas Wisma DPR RI.",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Check-in untuk {$booking->guest_name} berhasil diproses.",
            'data'    => $booking->fresh(['facility']),
        ]);
    }

    /**
     * Process check-out for a booking.
     * Hanya Resepsionis.
     *
     * PUT /api/bookings/{booking}/checkout
     */
    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status !== 'check_in') {
            return response()->json([
                'success' => false,
                'message' => 'Check-out hanya bisa dilakukan untuk tamu yang sedang menginap.',
            ], 422);
        }

        $booking->update([
            'status'          => 'selesai',
            'checked_out_at'  => now(),
        ]);

        // Update status fasilitas menjadi CLEANING
        $booking->facility->update(['status' => 'CLEANING']);

        // Kirim notifikasi ke guest
        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'checkout',
            'title'   => 'Check-Out Berhasil',
            'message' => "Terima kasih telah menginap di Wisma DPR RI. Booking {$booking->booking_code} telah selesai.",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Check-out untuk {$booking->guest_name} berhasil diproses.",
            'data'    => $booking->fresh(['facility']),
        ]);
    }
}
