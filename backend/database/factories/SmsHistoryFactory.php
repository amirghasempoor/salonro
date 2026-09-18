<?php

namespace Database\Factories;

use App\Models\SmsHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsHistory>
 */
class SmsHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => '09'.fake()->unique()->numerify('#########'),
            'status' => 1,
            'message' => fake()->sentence(),
            'pack_id' => fake()->uuid(),
            'message_ids' => json_encode([fake()->uuid()]),
            'cost' => '1000',
            'delivery_state' => null,
        ];
    }
}
