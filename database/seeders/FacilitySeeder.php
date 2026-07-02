<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Seed the facilities table with the same initial data as the frontend.
     */
    public function run(): void
    {
        $facilities = [
            [
                'name' => 'VIP Suite Nusantara',
                'type' => 'kamar',
                'gedung' => 'Wing A',
                'lantai' => 'Lantai 12',
                'capacity' => 2,
                'price' => 2500000,
                'unit' => 'night',
                'luas' => '45 m²',
                'bed' => 'King Size',
                'status' => 'READY',
                'photo' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=600&q=80',
                'description' => 'Fasilitas utama untuk tamu kenegaraan tingkat tinggi dengan desain mewah, ruang lounge pribadi, kamar mandi marmer berpemanas, dan pemandangan panorama kota Jakarta.',
            ],
            [
                'name' => 'Ruang Rapat Nusantara III',
                'type' => 'ruang_rapat',
                'gedung' => 'Gedung Utama',
                'lantai' => 'Lantai 2',
                'capacity' => 25,
                'price' => 1200000,
                'unit' => '4_jam',
                'luas' => '80 m²',
                'bed' => 'Conference Table',
                'status' => 'CLEANING',
                'photo' => 'https://images.unsplash.com/photo-1517502884422-41eaaced0168?auto=format&fit=crop&w=600&q=80',
                'description' => 'Ruang rapat medium dengan sistem audio-visual terintegrasi, layar proyeksi otomatis, mikrofon konferensi nirkabel, dan layanan asisten rapat siap sedia.',
            ],
            [
                'name' => 'Auditorium Sasana Bhakti',
                'type' => 'ruang_rapat',
                'gedung' => 'Gedung Utama',
                'lantai' => 'Ground Floor',
                'capacity' => 500,
                'price' => 10000000,
                'unit' => 'day',
                'luas' => '600 m²',
                'bed' => 'Theater Seating',
                'status' => 'MAINTENANCE',
                'photo' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?auto=format&fit=crop&w=600&q=80',
                'description' => 'Balai serbaguna berkapasitas besar untuk acara formal, pelantikan, seminar internasional, atau pameran seni. Dilengkapi akustik profesional dan pencahayaan panggung lengkap.',
            ],
            [
                'name' => 'Executive Suite - Wing A',
                'type' => 'kamar',
                'gedung' => 'Wing A',
                'lantai' => 'Lantai 5',
                'capacity' => 2,
                'price' => 1250000,
                'unit' => 'night',
                'luas' => '40 m²',
                'bed' => 'King Size',
                'status' => 'READY',
                'photo' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=600&q=80',
                'description' => 'Suite Eksekutif dirancang khusus untuk memenuhi standar kenyamanan pejabat negara dan tamu penting. Memiliki ruang kerja luas terpisah, smart home system, dan lounge bar mini.',
            ],
            [
                'name' => 'Superior Room - Wing B',
                'type' => 'kamar',
                'gedung' => 'Wing B',
                'lantai' => 'Lantai 3',
                'capacity' => 2,
                'price' => 1050000,
                'unit' => 'night',
                'luas' => '32 m²',
                'bed' => 'Queen Size',
                'status' => 'READY',
                'photo' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=600&q=80',
                'description' => 'Kamar superior bernuansa modern minimalis dengan fasilitas lengkap, kasur berkualitas tinggi, meja kerja ergonomis, dan akses Wi-Fi berkecepatan tinggi.',
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::updateOrCreate(
                ['name' => $facility['name']],
                $facility
            );
        }
    }
}
