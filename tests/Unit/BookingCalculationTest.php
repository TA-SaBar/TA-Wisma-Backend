<?php

namespace Tests\Unit;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_booking_code()
    {
        $code1 = Booking::generateBookingCode();
        $code2 = Booking::generateBookingCode();

        $this->assertNotEmpty($code1);
        $this->assertStringStartsWith('WDPR-' . date('Y') . '-', $code1);
        $this->assertNotEquals($code1, $code2);
    }
}
