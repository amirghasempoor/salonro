<?php

namespace Database\Factories;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'expert_id' => Expert::factory(),
            'expert_name' => fake()->name(),
            'hall_id' => Hall::factory(),
            'hall_name' => fake()->company(),
            'state_id' => ReservationState::factory(),
            'state_name' => 'reserve',
            'start_time' => now()->addDay(),
            'finish_time' => now()->addDay()->addHour(),
            'total_price' => fake()->randomFloat(0, 50000, 500000),
            'cancelled_by_id' => null,
            'cancelled_by_type' => null,
            'cancelled_reason' => null,
        ];
    }
}
