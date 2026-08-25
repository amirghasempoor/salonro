<?php

namespace Database\Factories;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\JobOffer;
use App\Models\Profession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOffer>
 */
class JobOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'hall_name' => function (array $attributes) {
                return Hall::query()->find($attributes['hall_id'])->name;
            },
            'expert_id' => Expert::factory(),
            'profession_id' => Profession::factory(),
            'profession_name' => function (array $attributes) {
                return Profession::query()->find($attributes['profession_id'])->name;
            },
            'province_id' => function (array $attributes) {
                return Hall::query()->find($attributes['hall_id'])->province_id;
            },
            'province_name' => function (array $attributes) {
                return Hall::query()->find($attributes['hall_id'])->province_name;
            },
            'city_id' => function (array $attributes) {
                return Hall::query()->find($attributes['hall_id'])->city_id;
            },
            'city_name' => function (array $attributes) {
                return Hall::query()->find($attributes['hall_id'])->city_name;
            },
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
