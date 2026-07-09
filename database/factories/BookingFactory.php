<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn  = $this->faker->dateTimeBetween('+1 day', '+10 days');
        $checkOut = $this->faker->dateTimeBetween($checkIn, '+15 days');
        $nights   = max(1, (int) date_diff($checkIn, $checkOut)->days);
        $price    = 387000;
        $subtotal = $nights * $price;
        $tax      = $subtotal * 0.11;

        return [
            'booking_code'      => Booking::generateBookingCode(),
            'user_id'           => User::factory(),
            'facility_id'       => Facility::factory(),
            'check_in'          => $checkIn->format('Y-m-d'),
            'check_out'         => $checkOut->format('Y-m-d'),
            'nights'            => $nights,
            'subtotal'          => $subtotal,
            'tax'               => $tax,
            'total_price'       => $subtotal + $tax,
            'status'            => 'pending',
            'guest_name'        => $this->faker->name(),
            'guest_nip'         => $this->faker->numerify('##############'),
            'guest_phone'       => $this->faker->phoneNumber(),
            'guest_email'       => $this->faker->email(),
            'payment_method'    => null,
            'snap_token'        => null,
            'midtrans_order_id' => null,
            'paid_at'           => null,
            'checked_in_at'     => null,
            'checked_out_at'    => null,
        ];
    }
}
