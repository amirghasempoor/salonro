<?php

namespace Database\Factories;

use App\Enums\DiscountAmountType;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state (an active, in-window holiday discount).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'type' => DiscountType::Holiday,
            'user_id' => null,
            'title' => fake()->word(),
            'amount_type' => DiscountAmountType::Percentage,
            'amount' => 10,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDay()->toDateString(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function manual(User|int|null $user = null): static
    {
        return $this->state(fn () => [
            'type' => DiscountType::Manual,
            'user_id' => $user instanceof User ? $user->id : ($user ?? User::factory()),
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    public function holiday(): static
    {
        return $this->state(fn () => [
            'type' => DiscountType::Holiday,
            'user_id' => null,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDay()->toDateString(),
        ]);
    }

    public function percentage(int $percent): static
    {
        return $this->state(fn () => [
            'amount_type' => DiscountAmountType::Percentage,
            'amount' => $percent,
        ]);
    }

    public function fixed(int $toman): static
    {
        return $this->state(fn () => [
            'amount_type' => DiscountAmountType::Fixed,
            'amount' => $toman,
        ]);
    }
}
