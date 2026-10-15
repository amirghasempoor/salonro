<?php

namespace Database\Factories;

use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpertService>
 */
class ExpertServiceFactory extends Factory
{
    protected $model = ExpertService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serviceable_type' => ExpertHall::class,
            'serviceable_id' => ExpertHall::factory(),
            'service_id' => Service::factory(),
            'price' => 150000,
            'duration' => 60,
        ];
    }
}
