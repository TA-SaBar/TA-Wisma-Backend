<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintFlowTest extends TestCase
{
    use RefreshDatabase;

    private function guestWithToken(): array
    {
        $user  = User::factory()->create(['role' => 'guest']);
        $token = $user->createToken('auth-token')->plainTextToken;
        return [$user, $token];
    }

    private function csWithToken(): array
    {
        $user  = User::factory()->create(['role' => 'customer_service']);
        $token = $user->createToken('auth-token')->plainTextToken;
        return [$user, $token];
    }

    public function test_guest_can_create_complaint()
    {
        [$guest, $token] = $this->guestWithToken();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/complaints', [
                'title'       => 'AC tidak berfungsi',
                'category'    => 'facility',
                'location'    => 'Kamar 204 Gedung A',
                'description' => 'AC di kamar 204 tidak mengeluarkan udara dingin sejak kemarin.',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('complaints', [
            'user_id'  => $guest->id,
            'title'    => 'AC tidak berfungsi',
            'category' => 'facility',
            'status'   => 'pending',
        ]);
    }

    public function test_guest_can_only_see_own_complaints()
    {
        [$guest1, $token1] = $this->guestWithToken();
        [$guest2, $token2] = $this->guestWithToken();

        // Buat 2 keluhan untuk guest1, 1 keluhan untuk guest2
        Complaint::factory()->count(2)->create(['user_id' => $guest1->id]);
        Complaint::factory()->create(['user_id' => $guest2->id]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token1])
            ->getJson('/api/complaints');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data'); // Hanya milik sendiri
    }

    public function test_customer_service_can_see_all_complaints()
    {
        [$guest1, $__] = $this->guestWithToken();
        [$guest2, $__] = $this->guestWithToken();
        [$cs, $csToken] = $this->csWithToken();

        Complaint::factory()->create(['user_id' => $guest1->id]);
        Complaint::factory()->create(['user_id' => $guest2->id]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $csToken])
            ->getJson('/api/complaints');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data'); // Semua keluhan
    }

    public function test_customer_service_can_process_complaint()
    {
        [$guest, $__]  = $this->guestWithToken();
        [$cs, $csToken] = $this->csWithToken();

        $complaint = Complaint::factory()->create([
            'user_id' => $guest->id,
            'status'  => 'pending',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $csToken])
            ->putJson("/api/complaints/{$complaint->id}/process");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'processed');
    }

    public function test_customer_service_can_resolve_complaint()
    {
        [$guest, $__]  = $this->guestWithToken();
        [$cs, $csToken] = $this->csWithToken();

        $complaint = Complaint::factory()->create([
            'user_id' => $guest->id,
            'status'  => 'processed',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $csToken])
            ->putJson("/api/complaints/{$complaint->id}/resolve");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('complaints', [
            'id'          => $complaint->id,
            'status'      => 'resolved',
            'resolved_by' => $cs->name,
        ]);
    }

    public function test_cannot_resolve_complaint_that_is_still_pending()
    {
        [$guest, $__]  = $this->guestWithToken();
        [$cs, $csToken] = $this->csWithToken();

        $complaint = Complaint::factory()->create([
            'user_id' => $guest->id,
            'status'  => 'pending',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $csToken])
            ->putJson("/api/complaints/{$complaint->id}/resolve");

        $response->assertStatus(422);
    }

    public function test_guest_cannot_process_complaint()
    {
        [$guest, $token] = $this->guestWithToken();

        $complaint = Complaint::factory()->create([
            'user_id' => $guest->id,
            'status'  => 'pending',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->putJson("/api/complaints/{$complaint->id}/process");

        $response->assertStatus(403);
    }

    public function test_complaint_requires_valid_category()
    {
        [$guest, $token] = $this->guestWithToken();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/complaints', [
                'title'    => 'Test keluhan',
                'category' => 'invalid_category', // Tidak valid
                'location' => 'Gedung A',
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['category']);
    }
}
