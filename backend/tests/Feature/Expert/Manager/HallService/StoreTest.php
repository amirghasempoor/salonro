<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $owner = Expert::factory()->create();
    $hall = Hall::factory()->create(['owner_id' => $owner->id]);
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $owner->id,
        'hall_id' => $hall->id,
    ]);
});

test('manager should be authenticated to store a hall service', function () {
    $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('service id is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('service_id');
});

test('service id should be existed', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]), [
        'service_id' => 999999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'service_id' => __('validation.exists', ['attribute' => 'service id']),
    ]);
});

test('description should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]), [
        'description' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'description' => __('validation.string', ['attribute' => 'description']),
    ]);
});

test('duration is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('duration');
});

test('duration should be integer', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]), [
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
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('price');
});

test('price should be integer', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]), [
        'price' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'price' => __('validation.integer', ['attribute' => 'price']),
    ]);
});

test('manager can store a hall service', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');

    $service = Service::factory()->create();

    $data = [
        'service_id' => $service->id,
        'description' => fake()->sentence(),
        'duration' => fake()->numberBetween(1, 100),
        'price' => fake()->numberBetween(1, 100),
    ];

    $response = $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]), $data);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'hall_service_id',
        ],
    ]);

    $this->assertDatabaseHas('hall_service', [
        'hall_id' => $this->expertHall->hall_id,
        'service_id' => $service->id,
        'description' => $data['description'],
        'duration' => $data['duration'],
        'price' => $data['price'],
    ]);
});

test('manager cannot store a service on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.services.store', [$this->expertHall->hall_id]))
        ->assertForbidden();
});
