<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database with default users for all 4 roles.
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Sahal Fajri',
                'email'    => 'sahalfajri@dpr.go.id',
                'nip'      => '199001152016031001',
                'phone'    => '+62 812-3456-7890',
                'role'     => 'guest',
                'instansi' => 'Sekretariat Jenderal DPR RI',
                'kunjungan' => 0,
                'password' => Hash::make('password'),
            ],
            [
                'name'     => 'Mochammad Abrar Ardika',
                'email'    => 'abrarardika@dpr.go.id',
                'nip'      => '199204082017031002',
                'phone'    => '+62 812-3456-7890',
                'role'     => 'guest',
                'instansi' => 'Sekretariat Jenderal DPR RI',
                'kunjungan' => 0,
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@dpr.go.id',
                'nip' => '198904122015031002',
                'phone' => '+62 812-3456-7890',
                'role' => 'guest',
                'instansi' => 'Sekretariat Jenderal DPR RI',
                'kunjungan' => 12,
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Amira Resepsionis',
                'email' => 'receptionist@wisma.dpr.go.id',
                'nip' => '199203142015032001',
                'phone' => '+62 813-2345-6789',
                'role' => 'receptionist',
                'instansi' => 'Front Desk Wisma',
                'kunjungan' => 0,
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Amira CS',
                'email' => 'cs@wisma.dpr.go.id',
                'nip' => '199308122018022003',
                'phone' => '+62 814-3456-7891',
                'role' => 'customer_service',
                'instansi' => 'Layanan Customer Service Wisma',
                'kunjungan' => 0,
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Koordinator Wisma',
                'email' => 'koordinator@wisma.dpr.go.id',
                'nip' => '198704122010011001',
                'phone' => '+62 812-3456-7890',
                'role' => 'koordinator_wisma',
                'instansi' => 'Wisma DPR RI',
                'kunjungan' => 0,
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
