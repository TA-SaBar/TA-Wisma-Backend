<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_facilities_by_status()
    {
        Facility::factory()->create(['status' => 'READY', 'name' => 'Kamar A']);
        Facility::factory()->create(['status' => 'MAINTENANCE', 'name' => 'Kamar B']);

        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/facilities?status=READY');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['name' => 'Kamar A'])
                 ->assertJsonMissing(['name' => 'Kamar B']);
    }

    public function test_can_filter_facilities_by_type()
    {
        Facility::factory()->create(['type' => 'kamar', 'name' => 'Kamar A']);
        Facility::factory()->create(['type' => 'ruang_rapat', 'name' => 'Ruang Rapat A']);

        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/facilities?type=kamar');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['name' => 'Kamar A'])
                 ->assertJsonMissing(['name' => 'Ruang Rapat A']);
    }

    public function test_can_filter_facilities_by_combination_of_status_and_type()
    {
        // BUG SIMULASI ITERASI 1:
        // Test ini didesain untuk gagal pada Iterasi 1 karena bug pada FacilityController.
        // Controller me-return data lebih awal pada blok filter status, sehingga
        // filter type diabaikan. Akibatnya, response akan mengembalikan 2 data (Kamar A & Ruang Rapat A)
        // padahal seharusnya hanya 1 data (Kamar A) karena kita memfilter type=kamar.
        
        Facility::factory()->create(['status' => 'READY', 'type' => 'kamar', 'name' => 'Kamar A']);
        Facility::factory()->create(['status' => 'READY', 'type' => 'ruang_rapat', 'name' => 'Ruang Rapat A']);
        Facility::factory()->create(['status' => 'MAINTENANCE', 'type' => 'kamar', 'name' => 'Kamar B']);

        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/facilities?status=READY&type=kamar');

        // Seharusnya hanya mengembalikan 1 fasilitas (Kamar A)
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['name' => 'Kamar A'])
                 ->assertJsonMissing(['name' => 'Ruang Rapat A'])
                 ->assertJsonMissing(['name' => 'Kamar B']);
    }
}
