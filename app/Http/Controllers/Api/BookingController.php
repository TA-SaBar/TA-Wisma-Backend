<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Facility;
use App\Services\BookingService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected MidtransService $midtransService;

    public function __construct(BookingService $bookingService, MidtransService $midtransService)
    {
        $this->bookingService = $bookingService;
        $this->midtransService = $midtransService;
    }

    /**
     * Display a listing of bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Booking::with(['facility'])->latest();

        if ($user->role === 'guest') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

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
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $user     = $request->user();
        $data     = $request->validated();
        $facility = Facility::findOrFail($data['facility_id']);

        if ($facility->status === 'MAINTENANCE') {
            return response()->json([
                'success' => false,
                'message' => 'Fasilitas saat ini sedang dalam masa perbaikan (MAINTENANCE) dan tidak dapat dipesan.',
            ], 422);
        }

        // Kalkulasi
        $pricing = $this->bookingService->calculatePricing($facility, $data['check_in'], $data['check_out']);

        if ($pricing['nights'] < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Durasi minimal 1 malam/hari.',
            ], 422);
        }

        // Buat record booking
        $guestData = [
            'guest_name'  => $data['guest_name'],
            'guest_nip'   => $data['guest_nip'] ?? null,
            'guest_phone' => $data['guest_phone'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'check_in'    => $data['check_in'],
            'check_out'   => $data['check_out'],
        ];

        $booking = $this->bookingService->createBooking($user, $facility, $pricing, $guestData);

        // Midtrans Token
        $snapToken = $this->midtransService->generateSnapToken($booking, $facility, [
            ...$guestData,
            'user_email' => $user->email,
            'user_phone' => $user->phone,
        ]);

        if ($snapToken) {
            $booking->update(['snap_token' => $snapToken]);
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
     */
    public function midtransWebhook(Request $request): JsonResponse
    {
        try {
            $notification = $this->midtransService->getNotificationPayload();

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
                    $this->bookingService->processPaymentSuccess($booking, $paymentType, 'webhook');
                }
            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $booking->update(['status' => 'cancelled']);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Midtrans Webhook Error: ' . $e->getMessage());
            
            $errMessage = strtolower($e->getMessage());
            if (
                str_contains($errMessage, 'signature key') || 
                str_contains($errMessage, 'failed to parse') || 
                str_contains($errMessage, 'transaction doesn\'t exist') ||
                str_contains($errMessage, '404')
            ) {
                return response()->json(['message' => 'Test connection received (dummy data ignored).'], 200);
            }

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check payment status manually via Midtrans API
     */
    public function checkStatus(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status === 'pending' && $booking->created_at->diffInMinutes(now()) >= 60) {
            $booking->update(['status' => 'cancelled']);
            return response()->json([
                'success' => true,
                'message' => 'Batas waktu pembayaran telah habis. Booking ini dibatalkan.',
                'data' => $booking
            ]);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'Status booking sudah ' . $booking->status,
                'data' => $booking
            ]);
        }

        try {
            $status = $this->midtransService->getTransactionStatus($booking->midtrans_order_id);
            $transactionStatus = $status->transaction_status;
            $fraudStatus = $status->fraud_status ?? null;

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                if ($fraudStatus === 'accept' || $transactionStatus === 'settlement') {
                    $this->bookingService->processPaymentSuccess($booking, $status->payment_type ?? null, 'manual_check');
                }
            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $booking->update(['status' => 'cancelled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status terbaru berhasil ditarik dari Midtrans.',
                'data' => $booking->fresh()
            ]);
        } catch (\Exception $e) {
            $errMessage = strtolower($e->getMessage());
            
            if (str_contains($errMessage, '404') || str_contains($errMessage, 'transaction doesn\'t exist')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran belum dimulai atau belum tercatat di sistem Midtrans. Silakan selesaikan pembayaran terlebih dahulu.',
                    'data' => $booking
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process check-in for a booking.
     */
    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status !== 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Check-in hanya bisa dilakukan untuk booking yang sudah lunas.',
            ], 422);
        }

        $this->bookingService->processCheckIn($booking);

        return response()->json([
            'success' => true,
            'message' => "Check-in untuk {$booking->guest_name} berhasil diproses.",
            'data'    => $booking->fresh(['facility']),
        ]);
    }

    /**
     * Process check-out for a booking.
     */
    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status !== 'check_in') {
            return response()->json([
                'success' => false,
                'message' => 'Check-out hanya bisa dilakukan untuk tamu yang sedang menginap.',
            ], 422);
        }

        $this->bookingService->processCheckOut($booking);

        return response()->json([
            'success' => true,
            'message' => "Check-out untuk {$booking->guest_name} berhasil diproses.",
            'data'    => $booking->fresh(['facility']),
        ]);
    }

    /**
     * Unduh tiket PDF (Guest).
     */
    public function exportTicketPdf(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id && !in_array($user->role, ['receptionist', 'koordinator_wisma'])) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        if (!in_array($booking->status, ['lunas', 'check_in', 'selesai'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket hanya dapat dicetak setelah pembayaran Lunas.',
            ], 422);
        }

        $booking->load(['facility', 'user']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ticket', compact('booking'));
        
        return $pdf->download('E-Ticket-' . $booking->booking_code . '.pdf');
    }
}
