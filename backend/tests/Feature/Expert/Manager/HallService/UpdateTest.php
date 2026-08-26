<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\HallService;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
    $this->hallService = HallService::factory()->create([
        'hall_id' => $this->expertHall->hall_id,
    ]);
});

test('manager should be authenticated to update a hall service', function () {
    $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertUnauthorized();
});

test('service id is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('service_id');
});

test('service id should be existed', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]), [
        'service_id' => 999999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'service_id' => __('validation.exists', ['attribute' => 'service id']),
    ]);
});

test('duration is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('duration');
});

test('duration should be integer', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]), [
        'duration' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'duration' => __('validation.integer', ['attribute' => 'duration']),
    ]);
});

test('price is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('price');
});

test('price should be integer', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]), [
        'price' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'price' => __('validation.integer', ['attribute' => 'price']),
    ]);
});

test('is active is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('is_active');
});

test('is active should be boolean', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]), [
        'is_active' => 'not-a-boolean',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'is_active' => __('validation.boolean', ['attribute' => 'is active']),
    ]);
});

test('manager can update a hall service', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');

    $service = Service::factory()->create();

    $data = [
        'service_id' => $service->id,
        'description' => fake()->sentence(),
        'duration' => fake()->numberBetween(1, 100),
        'price' => fake()->numberBetween(1, 100),
        'is_active' => false,
    ];

    $response = $this->postJson(
        route('expert.services.update', [$this->expertHall->hall_id, $this->hallService->id]),
        $data
    );

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('hall_service', [
        'id' => $this->hallService->id,
        'service_id' => $service->id,
        'description' => $data['description'],
        'duration' => $data['duration'],
        'price' => $data['price'],
        'is_active' => 0,
    ]);
});
