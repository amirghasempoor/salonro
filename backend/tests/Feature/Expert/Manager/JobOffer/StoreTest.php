<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Profession;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->hall = Hall::factory()->create();
    $this->manager = Expert::query()->find($this->hall->owner_id);
});

test('manager should be authenticated to store a job offer', function () {
    $this->postJson(route('expert.job_offers.store', [$this->hall->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.job_offers.store', [$this->hall->id]))->assertUnauthorized();
});

test('profession is required', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->postJson(route('expert.job_offers.store', [$this->hall->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('profession_id');
});

test('profession should be integer', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->postJson(route('expert.job_offers.store', [$this->hall->id]), [
        'profession_id' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'profession_id' => __('validation.integer', ['attribute' => 'profession id']),
    ]);
});

test('profession should be existed', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->postJson(route('expert.job_offers.store', [$this->hall->id]), [
        'profession_id' => 999999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'profession_id' => __('validation.exists', ['attribute' => 'profession id']),
    ]);
});

test('description should be string', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->postJson(route('expert.job_offers.store', [$this->hall->id]), [
        'description' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'description' => __('validation.string', ['attribute' => 'description']),
    ]);
});

test('manager can store a job offer', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $profession = Profession::factory()->create();

    $data = [
        'profession_id' => $profession->id,
        'description' => fake()->sentence(),
    ];

    $response = $this->postJson(route('expert.job_offers.store', [$this->hall->id]), $data);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('job_offers', [
        'hall_id' => $this->hall->id,
        'expert_id' => $this->manager->id,
        'profession_id' => $profession->id,
        'description' => $data['description'],
    ]);
});
