<?php

namespace Database\Factories;

use App\Models\Expert;
use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imageable_id' => Expert::factory(),
            'imageable_type' => Expert::class,
            'url' => '/storage/experts/portfolios/'.fake()->uuid().'.jpg',
            'title' => null,
        ];
    }
}
