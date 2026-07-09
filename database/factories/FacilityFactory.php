<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Fasilitas ' . fake()->unique()->words(2, true),
            'type' => fake()->randomElement(['Buah', 'Bunga', 'Rapat']),
            'gedung' => fake()->randomElement(['Wing A', 'Wing B', 'Gedung Utama']),
            'lantai' => 'Lantai ' . fake()->numberBetween(1, 15),
            'capacity' => fake()->numberBetween(2, 100),
            'price' => fake()->randomElement([500000, 1000000, 1500000]),
            'unit' => fake()->randomElement(['night', '4_jam', 'day']),
            'status' => fake()->randomElement(['READY', 'OCCUPIED', 'CLEANING', 'MAINTENANCE']),
        ];
    }
}
