<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complaint>
 */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'complaint_code' => Complaint::generateComplaintCode(),
            'user_id'        => User::factory(),
            'title'          => $this->faker->sentence(4),
            'category'       => $this->faker->randomElement(['facility', 'laundry', 'internet', 'food']),
            'location'       => 'Kamar ' . $this->faker->numberBetween(101, 310) . ' Gedung A',
            'description'    => $this->faker->paragraph(),
            'status'         => 'pending',
            'resolved_by'    => null,
            'resolved_at'    => null,
        ];
    }
}
