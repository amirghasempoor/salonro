<?php

use App\Models\City;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Province;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $owner = Expert::factory()->create();
    $hall = Hall::factory()->create(['owner_id' => $owner->id]);
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $owner->id,
        'hall_id' => $hall->id,
    ]);
});

test('manager should be authenticated to update a hall', function () {
    $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.hall.update', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('name is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('name');
});

test('name should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'name' => __('validation.string', ['attribute' => 'name']),
    ]);
});

test('lat is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('lat');
});

test('lat should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'lat' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'lat' => __('validation.string', ['attribute' => 'lat']),
    ]);
});

test('lng is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('lng');
});

test('lng should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'lng' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'lng' => __('validation.string', ['attribute' => 'lng']),
    ]);
});

test('address is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('address');
});

test('address should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'address' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'address' => __('validation.string', ['attribute' => 'address']),
    ]);
});

test('postal code is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('postal_code');
});

test('postal code should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'postal_code' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'postal_code' => __('validation.string', ['attribute' => 'postal code']),
    ]);
});

test('telephone is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('telephone');
});

test('telephone should be string', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'telephone' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'telephone' => __('validation.string', ['attribute' => 'telephone']),
    ]);
});

test('province id is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('province_id');
});

test('province id should be existed', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'province_id' => Province::query()->max('id') + 1,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'province_id' => __('validation.exists', ['attribute' => 'province id']),
    ]);
});

test('city id is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('city_id');
});

test('city id should be existed', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), [
        'city_id' => City::query()->max('id') + 1,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'city_id' => __('validation.exists', ['attribute' => 'city id']),
    ]);
});

test('services is required', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('services');
});

test('manager can update a hall', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);

    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');

    $province = Province::factory()->create();

    $city = City::factory()->create();

    $service1 = Service::factory()->create();
    $service2 = Service::factory()->create();

    $data = [
        'name' => fake()->name,
        'lat' => (string) fake()->latitude,
        'lng' => (string) fake()->longitude,
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
            ],
        ],
    ];

    $response = $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]), $data);

    $response->assertOk();

    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('halls', [
        'name' => $data['name'],
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

test('manager cannot update a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    // Authorization runs before validation, so even an empty payload is 403, not 422.
    $this->postJson(route('expert.hall.update', [$this->expertHall->hall_id]))
        ->assertForbidden();
});
