<?php

use App\Models\Expert;
use App\Models\JobOffer;
use App\Models\Profession;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->jobOffer = JobOffer::factory()->create();
    $this->manager = Expert::query()->find($this->jobOffer->hall->owner_id);
});

test('manager should be authenticated to update a job offer', function () {
    $this->postJson(route('expert.job_offers.update', [$this->jobOffer->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.job_offers.update', [$this->jobOffer->id]))->assertUnauthorized();
});

test('profession should be existed', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->postJson(route('expert.job_offers.update', [$this->jobOffer->id]), [
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
    $response = $this->postJson(route('expert.job_offers.update', [$this->jobOffer->id]), [
        'description' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'description' => __('validation.string', ['attribute' => 'description']),
    ]);
});

test('is active should be boolean', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->postJson(route('expert.job_offers.update', [$this->jobOffer->id]), [
        'is_active' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'is_active' => __('validation.boolean', ['attribute' => 'is active']),
    ]);
});

test('manager can update a job offer', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $profession = Profession::factory()->create();

    $data = [
        'profession_id' => $profession->id,
        'description' => fake()->sentence(),
        'is_active' => false,
    ];

    $response = $this->postJson(route('expert.job_offers.update', [$this->jobOffer->id]), $data);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('job_offers', [
        'id' => $this->jobOffer->id,
        'profession_id' => $profession->id,
        'description' => $data['description'],
        'is_active' => 0,
    ]);
});
