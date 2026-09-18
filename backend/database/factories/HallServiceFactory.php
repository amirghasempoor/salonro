<?php

namespace Database\Factories;

use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HallService>
 */
class HallServiceFactory extends Factory
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
            'service_id' => Service::factory(),
            'description' => null,
            'duration' => 60,
            'price' => 150000,
            'is_active' => true,
        ];
    }
}
