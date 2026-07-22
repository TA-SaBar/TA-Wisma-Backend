<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacilityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_read_facilities()
    {
        Facility::factory()->count(3)->create();

        $user = User::factory()->create(['role' => 'guest']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/facilities');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_koordinator_wisma_can_create_facility()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'koordinator_wisma']);
        $token = $admin->createToken('auth-token')->plainTextToken;

        $file = UploadedFile::fake()->image('room.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/facilities', [
            'name' => 'Kamar Deluxe',
            'type' => 'Buah',
                        'area' => 'Area Atas',
            'capacity' => 2,
            'price' => 500000,
            'unit' => 'night',
            'photo' => $file,
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Kamar Deluxe',
                 ]);

        $this->assertDatabaseHas('facilities', [
            'name' => 'Kamar Deluxe',
        ]);
    }

    public function test_non_admin_cannot_create_facility()
    {
        $guest = User::factory()->create(['role' => 'guest']);
        $token = $guest->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/facilities', [
            'name' => 'Kamar Deluxe',
            'type' => 'Buah',
                        'area' => 'Area Atas',
            'capacity' => 2,
            'price' => 500000,
            'unit' => 'night',
        ]);

        $response->assertStatus(403);
    }

    public function test_koordinator_wisma_can_update_facility()
    {
        $admin = User::factory()->create(['role' => 'koordinator_wisma']);
        $token = $admin->createToken('auth-token')->plainTextToken;

        $facility = Facility::factory()->create([
            'name' => 'Old Name',
            'price' => 100000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/facilities/' . $facility->id, [
            'name' => 'New Name',
            'price' => 200000,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'New Name',
                     'price' => '200000.00',
                 ]);

        $this->assertDatabaseHas('facilities', [
            'id' => $facility->id,
            'name' => 'New Name',
            'price' => 200000,
        ]);
    }

    public function test_koordinator_wisma_can_delete_facility()
    {
        $admin = User::factory()->create(['role' => 'koordinator_wisma']);
        $token = $admin->createToken('auth-token')->plainTextToken;

        $facility = Facility::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/facilities/' . $facility->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('facilities', [
            'id' => $facility->id,
        ]);
    }
}
