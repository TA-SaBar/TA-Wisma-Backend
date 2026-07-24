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

        // Auto-expire pending bookings older than 60 minutes
        $now = now();
        foreach ($bookings as $b) {
            if ($b->status === 'pending' && $b->created_at->diffInMinutes($now) >= 60) {
                $b->update(['status' => 'cancelled']);
                // Status is updated on the object dynamically for this response too
            }
        }

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

        // Proteksi Backend: Cegah booking HANYA jika fasilitas sedang MAINTENANCE
        if ($facility->status === 'MAINTENANCE') {
            return response()->json([
                'success' => false,
                'message' => 'Fasilitas saat ini sedang dalam masa perbaikan (MAINTENANCE) dan tidak dapat dipesan.',
            ], 422);
        }

        // Kalkulasi durasi dan harga
        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights   = $checkIn->diffInDays($checkOut);

        if ($facility->unit === 'day') {
            $nights += 1;
        } else {
            if ($nights === 0) $nights = 1;
        }

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
                    [
                        'id'       => 'TAX-11',
                        'price'    => (int) round($tax),
                        'quantity' => 1,
                        'name'     => 'Pajak PPN (11%)',
                    ],
                ],
                'customer_details' => [
                    'first_name' => $data['guest_name'],
                    'email'      => $data['guest_email'] ?? $user->email,
                    'phone'      => $data['guest_phone'] ?? $user->phone,
                ],
                'custom_expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit'       => 'minute',
                    'duration'   => 60,
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
                    // Kirim notifikasi ke resepsionis
                    $receptionists = \App\Models\User::where('role', 'receptionist')->get();
                    foreach ($receptionists as $rec) {
                        $formattedCheckIn = \Carbon\Carbon::parse($booking->check_in)->translatedFormat('l, d F Y');
                        \App\Models\Notification::create([
                            'user_id' => $rec->id,
                            'type'    => 'booking',
                            'title'   => 'Pemesanan Baru (Lunas)',
                            'message' => "Pemesanan baru {$booking->booking_code} telah lunas. Tamu dijadwalkan check-in pada {$formattedCheckIn}",
                            'related_id' => $booking->id,
                            'related_type' => 'new_booking',
                            'is_read' => false,
                        ]);
                    }

                }
            } elseif ($transactionStatus === 'cancel' || $transactionStatus === 'deny' || $transactionStatus === 'expire') {
                $booking->update(['status' => 'cancelled']);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Midtrans Webhook Error: ' . $e->getMessage());
            
            // Jika ini dari tombol "Test Connection" di dashboard Midtrans, 
            // payload dummy yang dikirimkan seringkali gagal validasi signature,
            // atau mencoba mencari transaksi palsu yang berujung pada error 404.
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
     *
     * POST /api/bookings/{booking}/check-status
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
            $status = \Midtrans\Transaction::status($booking->midtrans_order_id);
            $transactionStatus = $status->transaction_status;
            $fraudStatus = $status->fraud_status ?? null;

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                if ($fraudStatus === 'accept' || $transactionStatus === 'settlement') {
                    $booking->update([
                        'status'         => 'lunas',
                        'payment_method' => $status->payment_type ?? null,
                        'paid_at'        => now(),
                    ]);

                    Notification::create([
                        'user_id' => $booking->user_id,
                        'type'    => 'payment',
                        'title'   => 'Pembayaran Berhasil',
                        'message' => "Pembayaran untuk booking {$booking->booking_code} telah berhasil melalui pengecekan manual. Silakan check-in sesuai jadwal.",
                        'is_read' => false,
                    ]);
                    // Kirim notifikasi ke resepsionis
                    $receptionists = \App\Models\User::where('role', 'receptionist')->get();
                    foreach ($receptionists as $rec) {
                        \App\Models\Notification::create([
                            'user_id' => $rec->id,
                            'type'    => 'booking',
                            'title'   => 'Pemesanan Baru (Lunas)',
                            'message' => "Pemesanan baru {$booking->booking_code} telah lunas. Tamu dijadwalkan check-in pada {$booking->check_in}.",
                            'related_id' => $booking->id,
                            'related_type' => 'new_booking',
                            'is_read' => false,
                        ]);
                    }

                }
            } elseif ($transactionStatus === 'cancel' || $transactionStatus === 'deny' || $transactionStatus === 'expire') {
                $booking->update(['status' => 'cancelled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status terbaru berhasil ditarik dari Midtrans.',
                'data' => $booking->fresh()
            ]);
        } catch (\Exception $e) {
            $errMessage = strtolower($e->getMessage());
            
            // Jika transaksi belum dibuat/dibayar di Midtrans, API akan melempar 404
            if (str_contains($errMessage, '404') || str_contains($errMessage, 'transaction doesn\'t exist')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran belum dimulai atau belum tercatat di sistem Midtrans. Silakan selesaikan pembayaran terlebih dahulu.',
                    'data' => $booking
                ], 200); // 200 OK agar frontend menampilkannya sebagai info, bukan error fatal
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status: ' . $e->getMessage()
            ], 500);
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

        // Kirim notifikasi ke koordinator wisma
        $koordinators = \App\Models\User::where('role', 'koordinator_wisma')->get();
        foreach ($koordinators as $koordinator) {
            Notification::create([
                'user_id' => $koordinator->id,
                'type'    => 'checkout',
                'title'   => 'Tamu Check-Out',
                'message' => "Tamu {$booking->guest_name} telah check-out dari unit {$booking->facility->name} (Booking: {$booking->booking_code}). Fasilitas masuk antrean Cleaning.",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Check-out untuk {$booking->guest_name} berhasil diproses.",
            'data'    => $booking->fresh(['facility']),
        ]);
    }

    /**
     * Unduh tiket PDF (Guest).
     *
     * GET /api/bookings/{booking}/ticket
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
