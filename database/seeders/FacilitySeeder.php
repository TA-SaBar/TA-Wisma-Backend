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
                    'gedung'      => 'Wisma',
                    'lantai'      => 'Area Bawah',
                    'capacity'    => 2,
                    'price'       => 387000,
                    'unit'        => 'night',
                    'luas'        => '24 m²',
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
                    'gedung'      => 'Wisma',
                    'lantai'      => 'Area Atas',
                    'capacity'    => 2,
                    'price'       => 549000,
                    'unit'        => 'night',
                    'luas'        => '28 m²',
                    'bed'         => 'Twin Bed',
                    'status'      => $status,
                    'photo'       => '/images/bungalow_bunga.jpg',
                    'description' => 'Bungalow Standard tipe Bunga yang tenang dan bersih di lantai atas, dilengkapi dengan Twin Bed, AC, TV, Wi-Fi, dan pemandangan luar wisma.',
                ]
            );
        }

        // Ruang Rapat
        Facility::updateOrCreate(
            ['name' => 'Ruang Panja (Rapat)'],
            [
                'name'        => 'Ruang Panja (Rapat)',
                'type'        => 'Rapat',
                'gedung'      => 'Wisma',
                'lantai'      => 'Area Bawah',
                'capacity'    => 30,
                'price'       => 250000,
                'unit'        => 'day',
                'luas'        => '60 m²',
                'bed'         => 'Meja Rapat Oval',
                'status'      => 'READY',
                'photo'       => '/images/ruang_rapat.jpeg',
                'description' => 'Ruang rapat/sidang Panja Wisma DPR RI yang nyaman, dilengkapi dengan meja oval rapat, kursi ergonomis, sound system, proyektor, AC, dan Wi-Fi cepat.',
            ]
        );
    }
}
