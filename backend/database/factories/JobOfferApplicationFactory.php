<?php

namespace Database\Factories;

use App\Models\Expert;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOfferApplication>
 */
class JobOfferApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_offer_id' => JobOffer::factory(),
            'expert_id' => Expert::factory(),
            'status' => JobOfferApplication::STATUS_PENDING,
        ];
    }
}
