<?php

use App\Models\City;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Province;
use App\Models\Service;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');

    $this->province = Province::factory()->create();
    $this->city = City::factory()->create(['province_id' => $this->province->id]);
    $this->service = Service::factory()->create();
});

test('expert can create a hall', function () {
    $this->postJson('/expert/halls', [
        'name' => 'Salon Central',
        'lat' => '35.6892',
        'lng' => '51.3890',
        'address' => 'Tehran, Valiasr St',
        'postal_code' => '1234567890',
        'telephone' => '02112345678',
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
        'description' => 'A nice salon',
        'services' => [
            ['service_id' => $this->service->id, 'duration' => 60, 'price' => 150000],
        ],
    ])->assertOk()
        ->assertJsonStructure(['data' => ['hall_id']]);

    $this->assertDatabaseHas('halls', ['name' => 'Salon Central', 'owner_id' => $this->expert->id]);
    $this->assertDatabaseHas('expert_hall', ['expert_id' => $this->expert->id]);
    $this->assertDatabaseHas('hall_service', ['service_id' => $this->service->id]);
});

test('expert can list own halls', function () {
    $hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $hall->experts()->attach($this->expert->id);

    $this->getJson('/expert/halls?filters=[]&sorting=[]')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('expert can view a hall', function () {
    $hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $hall->services()->attach($this->service->id, ['price' => 100000, 'duration' => 60]);

    $this->getJson("/expert/halls/{$hall->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $hall->id);
});

test('expert can update a hall', function () {
    $hall = Hall::factory()->create(['owner_id' => $this->expert->id]);

    $this->postJson("/expert/halls/{$hall->id}", [
        'name' => 'Renamed Salon',
        'lat' => '35.7',
        'lng' => '51.4',
        'address' => 'New address',
        'postal_code' => '0987654321',
        'telephone' => '02100000000',
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
    ])->assertOk();

    $this->assertDatabaseHas('halls', ['id' => $hall->id, 'name' => 'Renamed Salon']);
});

test('expert can delete a hall', function () {
    $hall = Hall::factory()->create(['owner_id' => $this->expert->id]);

    $this->deleteJson("/expert/halls/{$hall->id}")->assertOk();

    $this->assertDatabaseMissing('halls', ['id' => $hall->id]);
});

test('expert can list services attached to a hall', function () {
    $hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $hall->services()->attach($this->service->id, ['price' => 100000, 'duration' => 60]);

    $this->getJson("/expert/halls/services/{$hall->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
