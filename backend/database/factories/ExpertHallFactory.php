<?php

namespace Database\Factories;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpertHall>
 */
class ExpertHallFactory extends Factory
{
    protected $model = ExpertHall::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expert_id' => Expert::factory(),
            'hall_id' => Hall::factory(),
            'is_active' => true,
            'joined_at' => now(),
        ];
    }
}
