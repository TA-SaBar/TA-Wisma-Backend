<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function getGuestWithToken(): array
    {
        $guest = User::factory()->create(['role' => 'guest']);
        $token = $guest->createToken('auth-token')->plainTextToken;
        return [$guest, $token];
    }

    private function getReceptionistWithToken(): array
    {
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $token        = $receptionist->createToken('auth-token')->plainTextToken;
        return [$receptionist, $token];
    }

    public function test_guest_can_create_booking()
    {
        [$guest, $token] = $this->getGuestWithToken();
        $facility = Facility::factory()->create(['status' => 'READY', 'price' => 387000, 'unit' => 'night']);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/bookings', [
                'facility_id' => $facility->id,
                'check_in'    => now()->addDay()->toDateString(),
                'check_out'   => now()->addDays(3)->toDateString(),
                'guest_name'  => $guest->name,
                'guest_nip'   => $guest->nip,
                'guest_phone' => $guest->phone,
                'guest_email' => $guest->email,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'pending')
                 ->assertJsonPath('data.nights', 2);
    }

    public function test_booking_calculates_price_correctly()
    {
        [$guest, $token] = $this->getGuestWithToken();
        $pricePerNight = 387000;
        $facility = Facility::factory()->create(['status' => 'READY', 'price' => $pricePerNight, 'unit' => 'night']);

        $checkIn  = now()->addDay()->toDateString();
        $checkOut = now()->addDays(4)->toDateString(); // 3 malam

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/bookings', [
                'facility_id' => $facility->id,
                'check_in'    => $checkIn,
                'check_out'   => $checkOut,
                'guest_name'  => $guest->name,
            ]);

        $booking = Booking::first();
        $expectedSubtotal   = 3 * $pricePerNight;
        $expectedTax        = $expectedSubtotal * 0.11;
        $expectedTotal      = $expectedSubtotal + $expectedTax;

        $this->assertEquals(3, $booking->nights);
        $this->assertEquals($expectedSubtotal, (float) $booking->subtotal);
        $this->assertEqualsWithDelta($expectedTax, (float) $booking->tax, 0.01);
        $this->assertEqualsWithDelta($expectedTotal, (float) $booking->total_price, 0.01);
    }

    public function test_guest_can_see_own_bookings()
    {
        [$guest, $token]   = $this->getGuestWithToken();
        [$otherGuest, $__] = $this->getGuestWithToken();
        $facility = Facility::factory()->create();

        // Buat booking milik guest dan booking milik orang lain
        Booking::factory()->create(['user_id' => $guest->id, 'facility_id' => $facility->id]);
        Booking::factory()->create(['user_id' => $otherGuest->id, 'facility_id' => $facility->id]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/bookings');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data'); // Hanya milik sendiri
    }

    public function test_receptionist_can_see_all_bookings()
    {
        [$guest1, $__] = $this->getGuestWithToken();
        [$guest2, $__] = $this->getGuestWithToken();
        [$receptionist, $rToken] = $this->getReceptionistWithToken();
        $facility = Facility::factory()->create();

        Booking::factory()->create(['user_id' => $guest1->id, 'facility_id' => $facility->id]);
        Booking::factory()->create(['user_id' => $guest2->id, 'facility_id' => $facility->id]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $rToken])
            ->getJson('/api/bookings');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data'); // Semua booking
    }

    public function test_guest_cannot_book_with_invalid_dates()
    {
        [$guest, $token] = $this->getGuestWithToken();
        $facility = Facility::factory()->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/bookings', [
                'facility_id' => $facility->id,
                'check_in'    => now()->addDays(5)->toDateString(),
                'check_out'   => now()->addDays(3)->toDateString(), // lebih awal dari check_in
                'guest_name'  => $guest->name,
            ]);

        $response->assertStatus(422);
    }
}
