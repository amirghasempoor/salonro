<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $catId = fake()->numberBetween(1, 10);

        return [
            'cat_id' => $catId,
            'cat_name' => fake()->unique()->word().' cat',
            'sub_cat_id' => $catId * 100 + fake()->numberBetween(1, 50),
            'sub_cat_name' => fake()->unique()->word(),
            'icon' => null,
        ];
    }
}
