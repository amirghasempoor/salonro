<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->expertHall = ExpertHall::factory()->create();
});

test('manager should be authenticated to store a staff', function () {
    $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('first name is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('phone number is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]), [
        'phone_number' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('services is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('services');
});

test('services should be array', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]), [
        'services' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'services' => __('validation.array', ['attribute' => 'services']),
    ]);
});

test('services should be existed', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]), [
        'services' => [999999],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'services.0' => __('validation.exists', ['attribute' => 'services.0']),
    ]);
});

test('manager can store a staff', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');

    $service = Service::factory()->create();

    $data = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'phone_number' => '09'.fake()->numerify('#########'),
        'services' => [$service->id],
    ];

    $response = $this->postJson(route('expert.staff.store', [$this->expertHall->hall_id]), $data);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('experts', [
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone_number' => $data['phone_number'],
    ]);

    $staff = Expert::query()->where('phone_number', $data['phone_number'])->first();

    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $staff->id,
        'hall_id' => $this->expertHall->hall_id,
    ]);

    $expertHall = ExpertHall::query()
        ->where('expert_id', $staff->id)
        ->where('hall_id', $this->expertHall->hall_id)
        ->first();

    $this->assertDatabaseHas('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $expertHall->id,
        'service_id' => $service->id,
    ]);

    expect($staff->hasRole('expert'))->toBeTrue();
});
