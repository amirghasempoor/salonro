<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
});

test('manager should be authenticated to store a staff', function () {
    $this->postJson(route('expert.staff.store', [$this->hall->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.staff.store', [$this->hall->id]))->assertUnauthorized();
});

test('phone number is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->hall->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->hall->id]), [
        'phone_number' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('services is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->hall->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('services');
});

test('services should be array', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->hall->id]), [
        'services' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'services' => __('validation.array', ['attribute' => 'services']),
    ]);
});

test('services should be existed', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.store', [$this->hall->id]), [
        'services' => [999999],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'services.0' => __('validation.exists', ['attribute' => 'services.0']),
    ]);
});

test('manager can store a staff', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $service = Service::factory()->create();

    $data = [
        'phone_number' => '09'.fake()->numerify('#########'),
        'services' => [$service->id],
    ];

    $response = $this->postJson(route('expert.staff.store', [$this->hall->id]), $data);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('experts', [
        'phone_number' => $data['phone_number'],
    ]);

    $staff = Expert::query()->where('phone_number', $data['phone_number'])->first();

    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $staff->id,
        'hall_id' => $this->hall->id,
    ]);

    $expertHall = ExpertHall::query()
        ->where('expert_id', $staff->id)
        ->where('hall_id', $this->hall->id)
        ->first();

    $this->assertDatabaseHas('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $expertHall->id,
        'service_id' => $service->id,
    ]);

    expect($staff->hasRole('expert'))->toBeTrue();
});

test('manager cannot store a staff on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.staff.store', [$this->hall->id]))->assertForbidden();
});
