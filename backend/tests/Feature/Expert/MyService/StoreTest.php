<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    $this->service = Service::factory()->create();
    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'is_active' => true,
    ]);
});

test('expert should be authenticated to store a service', function () {
    $this->postJson(route('expert.myService.store', [$this->hall->id]))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->expert, ['*'], 'user');
    $this->postJson(route('expert.myService.store', [$this->hall->id]))->assertUnauthorized();
});

test('services is required', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.myService.store', [$this->hall->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('services');
});

test('each service requires service_id, price and duration', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.myService.store', [$this->hall->id]), [
        'services' => [[]],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'services.0.service_id', 'services.0.price', 'services.0.duration',
    ]);
});

test('an expert not assigned to the hall cannot define services there', function () {
    $stranger = Expert::factory()->create(['is_verified' => true]);
    Sanctum::actingAs($stranger, ['*'], 'expert');

    $response = $this->postJson(route('expert.myService.store', [$this->hall->id]), [
        'services' => [['service_id' => $this->service->id, 'price' => 1000, 'duration' => 30]],
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'type' => 'logical_exception',
        'message' => __('messages.expert_not_in_hall'),
    ]);
});

test('an expert cannot define a price for a service the hall does not offer', function () {
    $otherService = Service::factory()->create();
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->postJson(route('expert.myService.store', [$this->hall->id]), [
        'services' => [['service_id' => $otherService->id, 'price' => 1000, 'duration' => 30]],
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'type' => 'logical_exception',
        'message' => __('messages.hall_does_not_offer_service'),
    ]);

    $this->assertDatabaseMissing('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $otherService->id,
    ]);
});

test('an expert can define price and duration for several services in one request', function () {
    $secondService = Service::factory()->create();
    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $secondService->id,
        'is_active' => true,
    ]);

    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->postJson(route('expert.myService.store', [$this->hall->id]), [
        'services' => [
            ['service_id' => $this->service->id, 'price' => 150000, 'duration' => 45],
            ['service_id' => $secondService->id, 'price' => 200000, 'duration' => 60],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $this->service->id,
        'price' => 150000,
        'duration' => 45,
    ]);

    $this->assertDatabaseHas('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $secondService->id,
        'price' => 200000,
        'duration' => 60,
    ]);
});

test('a bad service in a batch rolls back the whole request', function () {
    $otherService = Service::factory()->create();
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->postJson(route('expert.myService.store', [$this->hall->id]), [
        'services' => [
            ['service_id' => $this->service->id, 'price' => 150000, 'duration' => 45],
            ['service_id' => $otherService->id, 'price' => 1000, 'duration' => 30],
        ],
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseMissing('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $this->service->id,
    ]);
});
