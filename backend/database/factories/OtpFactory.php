<?php

namespace Database\Factories;

use App\Models\Otp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Otp>
 */
class OtpFactory extends Factory
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
            'verification_code' => fake()->numberBetween(1000, 9999),
            'used' => false,
            'expired_at' => now()->addMinutes(2),
        ];
    }
}
