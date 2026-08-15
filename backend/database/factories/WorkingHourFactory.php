<?php

namespace Database\Factories;

use App\Models\ExpertHall;
use App\Models\WorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingHour>
 */
class WorkingHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hourable_id' => ExpertHall::factory(),
            'hourable_type' => ExpertHall::class,
            'day' => fake()->unique()->randomElement(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'from' => '09:00:00',
            'to' => '18:00:00',
        ];
    }
}
