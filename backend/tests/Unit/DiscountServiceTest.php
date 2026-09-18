<?php

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Hall;
use App\Models\User;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(DiscountService::class);
});

test('reductionFor computes a percentage of the base total', function () {
    $discount = Discount::factory()->percentage(20)->make(['hall_id' => 1]);

    expect($this->service->reductionFor($discount, 200000))->toBe(40000);
});

test('reductionFor caps a fixed discount at the base total', function () {
    $discount = Discount::factory()->fixed(300000)->make(['hall_id' => 1]);

    expect($this->service->reductionFor($discount, 200000))->toBe(200000);
});

test('reductionFor yields nothing against a zero base total', function () {
    $discount = Discount::factory()->percentage(50)->make(['hall_id' => 1]);

    expect($this->service->reductionFor($discount, 0))->toBe(0);
});

test('resolveBest picks the larger of an overlapping manual and holiday discount', function () {
    $hall = Hall::factory()->create();
    $user = User::factory()->create();

    Discount::factory()->holiday()->percentage(10)->create(['hall_id' => $hall->id]); // 20000
    Discount::factory()->manual($user)->fixed(50000)->create(['hall_id' => $hall->id]); // 50000

    $best = $this->service->resolveBest($hall->id, $user->id, now(), 200000);

    expect($best)->not->toBeNull()
        ->and($best['amount'])->toBe(50000)
        ->and($best['discount']->type)->toBe(DiscountType::Manual);
});

test('resolveBest excludes a holiday outside its date window', function () {
    $hall = Hall::factory()->create();

    Discount::factory()->holiday()->percentage(10)->create([
        'hall_id' => $hall->id,
        'starts_at' => now()->subDays(10)->toDateString(),
        'ends_at' => now()->subDays(5)->toDateString(),
    ]);

    expect($this->service->resolveBest($hall->id, null, now(), 200000))->toBeNull();
});

test('resolveBest includes a holiday on its boundary day', function () {
    $hall = Hall::factory()->create();

    Discount::factory()->holiday()->fixed(30000)->create([
        'hall_id' => $hall->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(3)->toDateString(),
    ]);

    $best = $this->service->resolveBest($hall->id, null, now(), 200000);

    expect($best)->not->toBeNull()->and($best['amount'])->toBe(30000);
});

test('resolveBest ignores a manual discount with no remaining uses', function () {
    $hall = Hall::factory()->create();
    $user = User::factory()->create();

    Discount::factory()->manual($user)->fixed(40000)->create([
        'hall_id' => $hall->id,
        'usage_limit' => 2,
        'used_count' => 2,
    ]);

    expect($this->service->resolveBest($hall->id, $user->id, now(), 200000))->toBeNull();
});

test('resolveBest does not apply another customer\'s manual discount', function () {
    $hall = Hall::factory()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Discount::factory()->manual($owner)->fixed(40000)->create(['hall_id' => $hall->id]);

    expect($this->service->resolveBest($hall->id, $other->id, now(), 200000))->toBeNull();
});

test('resolveBest returns null when nothing matches', function () {
    $hall = Hall::factory()->create();

    expect($this->service->resolveBest($hall->id, null, now(), 200000))->toBeNull();
});

test('resolveBest returns null for a non-positive base total', function () {
    $hall = Hall::factory()->create();

    Discount::factory()->holiday()->percentage(10)->create(['hall_id' => $hall->id]);

    expect($this->service->resolveBest($hall->id, null, now(), 0))->toBeNull();
});
