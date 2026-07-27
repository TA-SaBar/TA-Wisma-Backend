<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class BookingService
{
    /**
     * Calculate pricing and nights for a booking.
     *
     * @param Facility $facility
     * @param string $checkIn
     * @param string $checkOut
     * @return array (nights, subtotal, tax, total_price)
     */
    public function calculatePricing(Facility $facility, string $checkIn, string $checkOut): array
    {
        $ci = Carbon::parse($checkIn);
        $co = Carbon::parse($checkOut);
        $nights = $ci->diffInDays($co);

        if ($facility->unit === 'day') {
            $nights += 1;
        } else {
            if ($nights === 0) $nights = 1;
        }

        $subtotal   = $nights * $facility->price;
        $tax        = $subtotal * 0.11;
        $totalPrice = $subtotal + $tax;

        return [
            'nights'      => $nights,
            'subtotal'    => $subtotal,
            'tax'         => $tax,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Create a new booking record in the database.
     *
     * @param User $user
     * @param Facility $facility
     * @param array $pricingData
     * @param array $guestData
     * @return Booking
     */
    public function createBooking(User $user, Facility $facility, array $pricingData, array $guestData): Booking
    {
        $bookingCode     = Booking::generateBookingCode();
        $midtransOrderId = 'WDPR-' . time() . '-' . $user->id;

        return Booking::create([
            'booking_code'      => $bookingCode,
            'user_id'           => $user->id,
            'facility_id'       => $facility->id,
            'check_in'          => $guestData['check_in'],
            'check_out'         => $guestData['check_out'],
            'nights'            => $pricingData['nights'],
            'subtotal'          => $pricingData['subtotal'],
            'tax'               => $pricingData['tax'],
            'total_price'       => $pricingData['total_price'],
            'status'            => 'pending',
            'guest_name'        => $guestData['guest_name'],
            'guest_nip'         => $guestData['guest_nip'] ?? null,
            'guest_phone'       => $guestData['guest_phone'] ?? null,
            'guest_email'       => $guestData['guest_email'] ?? null,
            'midtrans_order_id' => $midtransOrderId,
        ]);
    }

    /**
     * Mark booking as paid and dispatch notifications.
     *
     * @param Booking $booking
     * @param string|null $paymentType
     * @param string $source ('webhook' or 'manual_check')
     * @return void
     */
    public function processPaymentSuccess(Booking $booking, ?string $paymentType, string $source = 'webhook'): void
    {
        // Hindari pemrosesan ganda
        if ($booking->status === 'lunas' || $booking->status === 'check_in' || $booking->status === 'selesai') {
            return;
        }

        $booking->update([
            'status'         => 'lunas',
            'payment_method' => $paymentType,
            'paid_at'        => now(),
        ]);

        $message = $source === 'webhook'
            ? "Pembayaran untuk booking {$booking->booking_code} telah berhasil. Silakan check-in sesuai jadwal."
            : "Pembayaran untuk booking {$booking->booking_code} telah berhasil melalui pengecekan manual. Silakan check-in sesuai jadwal.";

        // Notifikasi ke tamu
        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'payment',
            'title'   => 'Pembayaran Berhasil',
            'message' => $message,
            'is_read' => false,
        ]);

        // Notifikasi ke resepsionis
        $receptionists = User::where('role', 'receptionist')->get();
        foreach ($receptionists as $rec) {
            // Karena check_in sudah berupa cast string 'Y-m-d' (dari perbaikan sebelumnya), parsing Carbon tetap aman.
            $formattedCheckIn = Carbon::parse($booking->check_in)->translatedFormat('l, d F Y');
            Notification::create([
                'user_id'      => $rec->id,
                'type'         => 'booking',
                'title'        => 'Pemesanan Baru (Lunas)',
                'message'      => "Pemesanan baru {$booking->booking_code} telah lunas. Tamu dijadwalkan check-in pada {$formattedCheckIn}",
                'related_id'   => $booking->id,
                'related_type' => 'new_booking',
                'is_read'      => false,
            ]);
        }
    }

    /**
     * Process Check-In
     *
     * @param Booking $booking
     * @return void
     */
    public function processCheckIn(Booking $booking): void
    {
        $booking->update([
            'status'        => 'check_in',
            'checked_in_at' => now(),
        ]);

        $booking->facility->update(['status' => 'OCCUPIED']);

        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'checkin',
            'title'   => 'Check-In Berhasil',
            'message' => "Anda resmi check-in untuk booking {$booking->booking_code}. Selamat menikmati fasilitas Wisma DPR RI.",
            'is_read' => false,
        ]);
    }

    /**
     * Process Check-Out
     *
     * @param Booking $booking
     * @return void
     */
    public function processCheckOut(Booking $booking): void
    {
        $booking->update([
            'status'         => 'selesai',
            'checked_out_at' => now(),
        ]);

        $booking->facility->update(['status' => 'CLEANING']);

        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'checkout',
            'title'   => 'Check-Out Berhasil',
            'message' => "Terima kasih telah menginap di Wisma DPR RI. Booking {$booking->booking_code} telah selesai.",
            'is_read' => false,
        ]);

        // Notifikasi ke koordinator wisma
        $koordinators = User::where('role', 'koordinator_wisma')->get();
        foreach ($koordinators as $koordinator) {
            Notification::create([
                'user_id' => $koordinator->id,
                'type'    => 'checkout',
                'title'   => 'Tamu Check-Out',
                'message' => "Tamu {$booking->guest_name} telah check-out dari unit {$booking->facility->name} (Booking: {$booking->booking_code}). Fasilitas masuk antrean Cleaning.",
                'is_read' => false,
            ]);
        }
    }
}
