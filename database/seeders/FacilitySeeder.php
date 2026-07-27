<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Seed the facilities table with data matching the frontend mock data.
     */
    public function run(): void
    {
        $bungalowBuah = [
            'Kedondong', 'Kesemek', 'Jamblang', 'Jeruk', 'Jambu', 'Delima', 'Duku', 'Durian',
            'Apel', 'Anggur', 'Leci', 'Alpukat', 'Belimbing', 'Buni', 'Cempedal', 'Ceremai',
            'Kelengkeng', 'Kecapi', 'Kepel', 'Kelapa', 'Salak', 'Langsat', 'Mundhu', 'Mangga',
            'Manggis', 'Markisa', 'Mengkudu', 'Melon', 'Nana', 'Maja', 'Nangka', 'Pepaya',
        ];

        $bungalowBunga = [
            'Widelia', 'Gladiol', 'Krisan', 'Tanjung', 'Teratai', 'Lotus', 'Seroja', 'Anthurium',
            'Aster', 'Kemuning', 'Lili', 'Alamanda', 'Dahlia', 'Gardenia', 'Nusa Indah', 'Kana',
            'Asoka', 'Raflesia', 'Lavender', 'Kenanga', 'Anyelir', 'Kamboja', 'Rosalia', 'Bugenvile',
        ];

        // Bungalow Buah (Area Bawah)
        foreach ($bungalowBuah as $name) {
            $status = 'READY';
            if ($name === 'Durian')  $status = 'MAINTENANCE';
            if ($name === 'Alpukat') $status = 'CLEANING';

            Facility::updateOrCreate(
                ['name' => 'Bungalow ' . $name],
                [
                    'name'        => 'Bungalow ' . $name,
                    'type'        => 'Buah',
                    'area'        => 'Area Bawah',
                    'capacity'    => 2,
                    'price'       => 387000,
                    'unit'        => 'night',
                    'bed'         => 'Queen Size',
                    'status'      => $status,
                    'photo'       => '/images/bungalow_buah.jpg',
                    'description' => 'Bungalow Standard tipe Buah yang nyaman dengan fasilitas tempat tidur Queen Size, AC, TV, kamar mandi dalam, dan perlengkapan mandi lengkap.',
                ]
            );
        }

        // Bungalow Bunga (Area Atas)
        foreach ($bungalowBunga as $name) {
            $status = 'READY';
            if ($name === 'Dahlia')  $status = 'CLEANING';
            if ($name === 'Kenanga') $status = 'MAINTENANCE';

            Facility::updateOrCreate(
                ['name' => 'Bungalow ' . $name],
                [
                    'name'        => 'Bungalow ' . $name,
                    'type'        => 'Bunga',
                    'area'        => 'Area Atas',
                    'capacity'    => 2,
                    'price'       => 549000,
                    'unit'        => 'night',
                    'bed'         => 'Twin Bed',
                    'status'      => $status,
                    'photo'       => '/images/bungalow_bunga.jpg',
                    'description' => 'Bungalow Standard tipe Bunga yang tenang dan bersih di area atas, dilengkapi dengan Twin Bed, AC, TV, Wi-Fi, dan pemandangan luar wisma.',
                ]
            );
        }

        // Daftar Ruang Rapat / Sidang / Serbaguna
        $ruangRapat = [
            ['name' => 'Ruang Sidang Utama 1 (RSDU 1)', 'area'=>'Area Atas','desc' => 'Ruang Sidang Utama (RSDU) Wisma DPR RI dengan kapasitas besar.', 'cap' => 100, 'price' => 250000],
            ['name' => 'Ruang Sidang Utama 2 (RSDU 2)', 'area'=>'Area Bawah','desc' => 'Ruang Sidang Utama (RSDU) Wisma DPR RI dengan kapasitas besar.', 'cap' => 100, 'price' => 250000],
            ['name' => 'Ruang Serbaguna 1 (RSG 1)', 'area'=>'Area Atas','desc' => 'Ruang Serbaguna (RSG) multifungsi untuk berbagai kegiatan/acara.', 'cap' => 150, 'price' => 250000],
            ['name' => 'Ruang Serbaguna 2 (RSG 2)', 'area'=>'Area Bawah','desc' => 'Ruang Serbaguna (RSG) multifungsi untuk berbagai kegiatan/acara.', 'cap' => 150, 'price' => 250000],
            ['name' => 'Ruang Panja 1', 'area'=>'Area Atas','desc' => 'Ruang rapat/sidang Panja Wisma DPR RI.', 'cap' => 30, 'price' => 250000],
            ['name' => 'Ruang Panja 2', 'area'=>'Area Atas','desc' => 'Ruang rapat/sidang Panja Wisma DPR RI.', 'cap' => 30, 'price' => 250000],
            ['name' => 'Ruang Panja 3', 'area'=>'Area Bawah','desc' => 'Ruang rapat/sidang Panja Wisma DPR RI.', 'cap' => 30, 'price' => 250000],
            ['name' => 'Ruang Panja 4', 'area'=>'Area Bawah','desc' => 'Ruang rapat/sidang Panja Wisma DPR RI.', 'cap' => 30, 'price' => 250000],
        ];

        foreach ($ruangRapat as $rapat) {
            Facility::updateOrCreate(
                ['name' => $rapat['name']],
                [
                    'name'        => $rapat['name'],
                    'type'        => 'Rapat',
                    'area'        => $rapat['area'],
                    'capacity'    => $rapat['cap'],
                    'price'       => $rapat['price'],
                    'unit'        => 'day',
                    'bed'         => 'Meja Rapat',
                    'status'      => 'READY',
                    'photo'       => '/images/ruang_rapat.jpeg', // Foto yang sama
                    'description' => $rapat['desc'] . ' Nyaman, dilengkapi kursi ergonomis, sound system, proyektor, AC, dan Wi-Fi.',
                ]
            );
        }
    }
}
