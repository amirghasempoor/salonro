<?php

use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;

test('the hall services endpoint is public and requires no authentication', function () {
    $hall = Hall::factory()->create();

    $this->getJson(route('user.home.hallServices', $hall))->assertOk();
});

test('it returns the services attached to the hall with price and duration flattened', function () {
    $hall = Hall::factory()->create();
    $service = Service::factory()->create([
        'cat_name' => 'خدمات مو',
        'sub_cat_name' => 'رنگ مو',
    ]);
    HallService::factory()->create([
        'hall_id' => $hall->id,
        'service_id' => $service->id,
        'price' => 50000,
        'duration' => 60,
    ]);

    $response = $this->getJson(route('user.home.hallServices', $hall));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonStructure([
        'data' => [
            ['id', 'cat_id', 'cat_name', 'sub_cat_id', 'sub_cat_name', 'icon', 'price', 'duration'],
        ],
    ]);
    $response->assertJsonPath('data.0.id', $service->id);
    $response->assertJsonPath('data.0.cat_name', 'خدمات مو');
    $response->assertJsonMissingPath('data.0.pivot');

    $data = $response->json('data');
    expect((float) $data[0]['price'])->toBe(50000.0)
        ->and((float) $data[0]['duration'])->toBe(60.0);
});

test('it returns an empty list when the hall has no services', function () {
    $hall = Hall::factory()->create();

    $this->getJson(route('user.home.hallServices', $hall))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('it only returns services attached to the requested hall', function () {
    $hall = Hall::factory()->create();
    $otherHall = Hall::factory()->create();

    $service = Service::factory()->create();
    HallService::factory()->create(['hall_id' => $hall->id, 'service_id' => $service->id]);
    HallService::factory()->create(['hall_id' => $otherHall->id]);

    $this->getJson(route('user.home.hallServices', $hall))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $service->id);
});

test('it returns 404 for a hall that does not exist', function () {
    $this->getJson(route('user.home.hallServices', 999))->assertNotFound();
});
