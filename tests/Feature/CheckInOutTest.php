<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInOutTest extends TestCase
{
    use RefreshDatabase;

    private function createBookingWithStatus(string $status, User $guest, Facility $facility): Booking
    {
        return Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => $status,
        ]);
    }

    public function test_receptionist_can_checkin_paid_booking()
    {
        $guest        = User::factory()->create(['role' => 'guest']);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $rToken       = $receptionist->createToken('auth-token')->plainTextToken;
        $facility     = Facility::factory()->create(['status' => 'READY']);
        $booking      = $this->createBookingWithStatus('lunas', $guest, $facility);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $rToken])
            ->putJson("/api/bookings/{$booking->id}/checkin");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'check_in');

        $this->assertDatabaseHas('facilities', [
            'id'     => $facility->id,
            'status' => 'OCCUPIED',
        ]);
    }

    public function test_receptionist_cannot_checkin_pending_booking()
    {
        $guest        = User::factory()->create(['role' => 'guest']);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $rToken       = $receptionist->createToken('auth-token')->plainTextToken;
        $facility     = Facility::factory()->create();
        $booking      = $this->createBookingWithStatus('pending', $guest, $facility);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $rToken])
            ->putJson("/api/bookings/{$booking->id}/checkin");

        $response->assertStatus(422);
    }

    public function test_receptionist_can_checkout_checkedin_booking()
    {
        $guest        = User::factory()->create(['role' => 'guest']);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $rToken       = $receptionist->createToken('auth-token')->plainTextToken;
        $facility     = Facility::factory()->create(['status' => 'OCCUPIED']);
        $booking      = $this->createBookingWithStatus('check_in', $guest, $facility);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $rToken])
            ->putJson("/api/bookings/{$booking->id}/checkout");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'selesai');

        $this->assertDatabaseHas('facilities', [
            'id'     => $facility->id,
            'status' => 'CLEANING',
        ]);
    }

    public function test_guest_cannot_checkin()
    {
        $guest    = User::factory()->create(['role' => 'guest']);
        $gToken   = $guest->createToken('auth-token')->plainTextToken;
        $facility = Facility::factory()->create();
        $booking  = $this->createBookingWithStatus('lunas', $guest, $facility);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $gToken])
            ->putJson("/api/bookings/{$booking->id}/checkin");

        $response->assertStatus(403);
    }
}
