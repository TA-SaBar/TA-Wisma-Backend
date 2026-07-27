<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Facility;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Notification as MidtransNotification;
use Midtrans\Transaction;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        MidtransConfig::$serverKey    = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = config('services.midtrans.is_production');
        MidtransConfig::$isSanitized  = config('services.midtrans.is_sanitized');
        MidtransConfig::$is3ds        = config('services.midtrans.is_3ds');
    }

    /**
     * Generate Midtrans Snap Token for a booking.
     *
     * @param Booking $booking
     * @param Facility $facility
     * @param array $guestData (guest_name, guest_email, guest_phone, user_email, user_phone)
     * @return string|null
     */
    public function generateSnapToken(Booking $booking, Facility $facility, array $guestData): ?string
    {
        try {
            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id'     => $booking->midtrans_order_id,
                    'gross_amount' => (int) round($booking->total_price),
                ],
                'item_details' => [
                    [
                        'id'       => $facility->id,
                        'price'    => (int) round($facility->price),
                        'quantity' => $booking->nights,
                        'name'     => $facility->name . ' (' . $booking->nights . ' malam/hari)',
                    ],
                    [
                        'id'       => 'TAX-11',
                        'price'    => (int) round($booking->tax),
                        'quantity' => 1,
                        'name'     => 'Pajak PPN (11%)',
                    ],
                ],
                'customer_details' => [
                    'first_name' => $guestData['guest_name'],
                    'email'      => $guestData['guest_email'] ?? $guestData['user_email'],
                    'phone'      => $guestData['guest_phone'] ?? $guestData['user_phone'],
                ],
                'custom_expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit'       => 'minute',
                    'duration'   => 60,
                ],
            ]);

            return $snapToken;
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get transaction status from Midtrans manually via API.
     *
     * @param string $orderId
     * @return mixed
     */
    public function getTransactionStatus(string $orderId)
    {
        return Transaction::status($orderId);
    }

    /**
     * Decode the webhook notification object.
     *
     * @return MidtransNotification
     */
    public function getNotificationPayload(): MidtransNotification
    {
        return new MidtransNotification();
    }
}
