<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_feedback_for_completed_booking()
    {
        $guest    = User::factory()->create(['role' => 'guest']);
        $token    = $guest->createToken('auth-token')->plainTextToken;
        $facility = Facility::factory()->create();
        $booking  = Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => 'selesai',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/bookings/{$booking->id}/feedback", [
                'rating_cleanliness' => 5,
                'rating_facilities'  => 4,
                'rating_service'     => 5,
                'comment'            => 'Fasilitas sangat bersih dan nyaman.',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.average_rating', '4.67');

        $this->assertDatabaseHas('feedbacks', [
            'booking_id' => $booking->id,
            'user_id'    => $guest->id,
        ]);
    }

    public function test_average_rating_calculated_correctly()
    {
        $guest    = User::factory()->create(['role' => 'guest']);
        $token    = $guest->createToken('auth-token')->plainTextToken;
        $facility = Facility::factory()->create();
        $booking  = Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => 'selesai',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/bookings/{$booking->id}/feedback", [
                'rating_cleanliness' => 4,
                'rating_facilities'  => 3,
                'rating_service'     => 5,
            ]);

        $feedback = Feedback::first();
        // (4 + 3 + 5) / 3 = 4.00
        $this->assertEquals(4.00, (float) $feedback->average_rating);
    }

    public function test_guest_cannot_submit_feedback_for_active_booking()
    {
        $guest    = User::factory()->create(['role' => 'guest']);
        $token    = $guest->createToken('auth-token')->plainTextToken;
        $facility = Facility::factory()->create();
        $booking  = Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => 'check_in', // Belum selesai
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/bookings/{$booking->id}/feedback", [
                'rating_cleanliness' => 5,
                'rating_facilities'  => 5,
                'rating_service'     => 5,
            ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_submit_duplicate_feedback()
    {
        $guest    = User::factory()->create(['role' => 'guest']);
        $token    = $guest->createToken('auth-token')->plainTextToken;
        $facility = Facility::factory()->create();
        $booking  = Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => 'selesai',
        ]);

        $payload = [
            'rating_cleanliness' => 5,
            'rating_facilities'  => 5,
            'rating_service'     => 5,
        ];

        // Kirim pertama kali — berhasil
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/bookings/{$booking->id}/feedback", $payload)
            ->assertStatus(201);

        // Kirim kedua kali — harus gagal
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/bookings/{$booking->id}/feedback", $payload)
            ->assertStatus(422);
    }

    public function test_guest_cannot_give_feedback_for_other_users_booking()
    {
        $guest1   = User::factory()->create(['role' => 'guest']);
        $guest2   = User::factory()->create(['role' => 'guest']);
        $token2   = $guest2->createToken('auth-token')->plainTextToken;
        $facility = Facility::factory()->create();
        $booking  = Booking::factory()->create([
            'user_id'     => $guest1->id, // Milik guest1
            'facility_id' => $facility->id,
            'status'      => 'selesai',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token2])
            ->postJson("/api/bookings/{$booking->id}/feedback", [
                'rating_cleanliness' => 3,
                'rating_facilities'  => 3,
                'rating_service'     => 3,
            ]);

        $response->assertStatus(403);
    }
}
