<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User berhasil memperbarui data diri (nama, email, no. HP).
     */
    public function test_user_can_update_profile_data()
    {
        $user  = User::factory()->create(['role' => 'guest']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->putJson('/api/profile', [
                'name'  => 'Nama Baru',
                'phone' => '081234567890',
            ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'success' => true,
                     'message' => 'Profil berhasil diperbarui.',
                 ]);

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'Nama Baru',
            'phone' => '081234567890',
        ]);
    }

    /**
     * User berhasil mengubah password.
     */
    public function test_user_can_update_password()
    {
        $user  = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
            'role'     => 'guest',
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->putJson('/api/profile', [
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true]);

        // Verifikasi bahwa password di DB sudah berubah
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    /**
     * Update password gagal jika konfirmasi tidak cocok.
     */
    public function test_password_update_fails_when_confirmation_mismatch()
    {
        $user  = User::factory()->create(['role' => 'guest']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->putJson('/api/profile', [
                'password'              => 'newpassword123',
                'password_confirmation' => 'BERBEDA999',
            ]);

        $response->assertStatus(422);
    }

    /**
     * User tidak bisa update email ke email yang sudah dipakai user lain.
     */
    public function test_user_cannot_use_duplicate_email()
    {
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);
        $user      = User::factory()->create(['email' => 'myemail@example.com', 'role' => 'guest']);
        $token     = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->putJson('/api/profile', [
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Pengguna yang tidak terautentikasi tidak bisa mengakses update profil.
     */
    public function test_unauthenticated_user_cannot_update_profile()
    {
        $response = $this->putJson('/api/profile', [
            'name' => 'Tidak Boleh',
        ]);

        $response->assertStatus(401);
    }
}
