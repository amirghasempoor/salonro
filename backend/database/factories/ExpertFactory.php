<?php

namespace Database\Factories;

use App\Models\Expert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Expert>
 */
class ExpertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone_number' => '09'.fake()->unique()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'avatar' => null,
            'province_id' => null,
            'city_id' => null,
            'gender' => null,
            'birth_date' => null,
            'is_verified' => false,
            'is_active' => true,
            'bio' => null,
        ];
    }
}
