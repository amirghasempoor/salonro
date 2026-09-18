<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hall>
 */
class HallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'owner_id' => Expert::factory(),
            'owner_name' => fake()->lastName(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'address' => fake()->address(),
            'postal_code' => fake()->postcode(),
            'telephone' => fake()->phoneNumber(),
            'province_id' => Province::factory(),
            'province_name' => function (array $attributes) {
                return Province::query()->find($attributes['province_id'])->name;
            },
            'city_id' => City::factory(),
            'city_name' => function (array $attributes) {
                return City::query()->find($attributes['city_id'])->name;
            },
            'is_active' => true,
            'description' => null,
        ];
    }
}
