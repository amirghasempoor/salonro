<?php

use App\Models\City;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Province;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

test('manager should be authenticated to store a hall', function () {
    $this->postJson(route('expert.hall.store'))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.hall.store'))->assertUnauthorized();
});

test('name is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('name');
});

test('name should be string', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'name' => __('validation.string', ['attribute' => 'name']),
    ]);
});

test('lat is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('lat');
});

test('lat should be string', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'lat' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'lat' => __('validation.string', ['attribute' => 'lat']),
    ]);
});

test('lng is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('lng');
});

test('lng should be string', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'lng' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'lng' => __('validation.string', ['attribute' => 'lng']),
    ]);
});

test('address is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('address');
});

test('address should be string', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'address' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'address' => __('validation.string', ['attribute' => 'address']),
    ]);
});

test('postal code is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('postal_code');
});

test('postal code should be string', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'postal_code' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'postal_code' => __('validation.string', ['attribute' => 'postal code']),
    ]);
});

test('telephone is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('telephone');
});

test('telephone should be string', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'telephone' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'telephone' => __('validation.string', ['attribute' => 'telephone']),
    ]);
});

test('province id is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('province_id');
});

test('province id should be existed', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'province_id' => fake()->numberBetween(1, 10),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'province_id' => __('validation.exists', ['attribute' => 'province id']),
    ]);
});

test('city id is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('city_id');
});

test('city id should be existed', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'), [
        'city_id' => fake()->numberBetween(1, 10),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'city_id' => __('validation.exists', ['attribute' => 'city id']),
    ]);
});

test('services is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('services');
});

test('manager can store a hall', function () {
    $expert = Expert::factory()->create();

    Sanctum::actingAs($expert, ['*'], 'expert');

    $province = Province::factory()->create();

    $city = City::factory()->create();

    $service1 = Service::factory()->create();
    $service2 = Service::factory()->create();

    $data = [
        'name' => fake()->name,
        'lat' => (string)fake()->latitude,
        'lng' => (string)fake()->longitude,
        'address' => fake()->address,
        'postal_code' => fake()->numerify(),
        'telephone' => fake()->phoneNumber,
        'province_id' => $province->id,
        'city_id' => $city->id,
        'services' => [
            [
                'service_id' => $service1->id,
                'duration' => fake()->numberBetween(1, 100),
                'price' => fake()->numberBetween(1, 100),
            ],
            [
                'service_id' => $service2->id,
                'duration' => fake()->numberBetween(1, 100),
                'price' => fake()->numberBetween(1, 100),
            ]
        ],
    ];

    $response = $this->postJson(route('expert.hall.store'), $data);

    $response->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'hall_id'
        ],
    ]);

    $this->assertDatabaseHas('halls', [
        'name' => $data['name'],
        'owner_id' => $expert->id,
        'owner_name' => $expert->last_name,
        'lat' => $data['lat'],
        'lng' => $data['lng'],
        'address' => $data['address'],
        'postal_code' => $data['postal_code'],
        'telephone' => $data['telephone'],
        'province_id' => $province->id,
        'city_id' => $city->id,
    ]);

    $this->assertDatabaseHas('hall_service', [
        'service_id' => $service1->id,
        'duration' => $data['services'][0]['duration'],
        'price' => $data['services'][0]['price'],
    ]);

    $this->assertDatabaseHas('hall_service', [
        'service_id' => $service2->id,
        'duration' => $data['services'][1]['duration'],
        'price' => $data['services'][1]['price'],
    ]);

    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $expert->id,
        'hall_id' => Hall::query()->first()->id,
    ]);
});
