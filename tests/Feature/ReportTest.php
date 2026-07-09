<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function koordinatorWithToken(): array
    {
        $user  = User::factory()->create(['role' => 'koordinator_wisma']);
        $token = $user->createToken('auth-token')->plainTextToken;
        return [$user, $token];
    }

    public function test_koordinator_can_access_financial_report()
    {
        [$koordinator, $token] = $this->koordinatorWithToken();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/reports/financial?start_date=2026-01-01&end_date=2026-12-31');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'data' => [
                         'periode',
                         'ringkasan' => [
                             'total_pendapatan',
                             'total_transaksi',
                             'total_malam',
                         ],
                         'distribusi_fasilitas',
                         'transaksi',
                     ],
                 ]);
    }

    public function test_financial_report_calculates_revenue_correctly()
    {
        [$koordinator, $token] = $this->koordinatorWithToken();
        $guest    = User::factory()->create(['role' => 'guest']);
        $facility = Facility::factory()->create(['price' => 400000]);

        // Buat 2 booking lunas dalam periode
        Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => 'selesai',
            'nights'      => 2,
            'subtotal'    => 800000,
            'tax'         => 88000,
            'total_price' => 888000,
            'paid_at'     => Carbon::parse('2026-07-01'),
        ]);
        Booking::factory()->create([
            'user_id'     => $guest->id,
            'facility_id' => $facility->id,
            'status'      => 'selesai',
            'nights'      => 3,
            'subtotal'    => 1200000,
            'tax'         => 132000,
            'total_price' => 1332000,
            'paid_at'     => Carbon::parse('2026-07-05'),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/reports/financial?start_date=2026-07-01&end_date=2026-07-31');

        $response->assertStatus(200)
                 ->assertJsonPath('data.ringkasan.total_transaksi', 2)
                 ->assertJsonPath('data.ringkasan.total_malam', 5)
                 ->assertJsonPath('data.ringkasan.total_pendapatan', 888000 + 1332000);
    }

    public function test_financial_report_requires_date_range()
    {
        [$koordinator, $token] = $this->koordinatorWithToken();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/reports/financial');

        $response->assertStatus(422);
    }

    public function test_financial_report_rejects_invalid_date_range()
    {
        [$koordinator, $token] = $this->koordinatorWithToken();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/reports/financial?start_date=2026-12-31&end_date=2026-01-01');

        $response->assertStatus(422);
    }

    public function test_koordinator_can_access_master_guests()
    {
        [$koordinator, $token] = $this->koordinatorWithToken();
        User::factory()->count(3)->create(['role' => 'guest']);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/reports/master-guests');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('total', 3);
    }

    public function test_guest_cannot_access_financial_report()
    {
        $guest = User::factory()->create(['role' => 'guest']);
        $token = $guest->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/reports/financial?start_date=2026-01-01&end_date=2026-12-31');

        $response->assertStatus(403);
    }
}
