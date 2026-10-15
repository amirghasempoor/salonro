<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;

test('the hall services endpoint is public and requires no authentication', function () {
    $hall = Hall::factory()->create();

    $this->getJson(route('user.home.hallServices', $hall))->assertOk();
});

test('it returns the services attached to the hall', function () {
    $hall = Hall::factory()->create();
    $service = Service::factory()->create([
        'cat_name' => 'خدمات مو',
        'sub_cat_name' => 'رنگ مو',
    ]);
    HallService::factory()->create([
        'hall_id' => $hall->id,
        'service_id' => $service->id,
    ]);

    $response = $this->getJson(route('user.home.hallServices', $hall));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonStructure([
        'data' => [
            ['id', 'cat_id', 'cat_name', 'sub_cat_id', 'sub_cat_name', 'icon', 'price_min', 'price_max'],
        ],
    ]);
    $response->assertJsonPath('data.0.id', $service->id);
    $response->assertJsonPath('data.0.cat_name', 'خدمات مو');
    $response->assertJsonMissingPath('data.0.pivot');
    $response->assertJsonMissingPath('data.0.price');
});

test('a service with no staff pricing it yet has a null price range', function () {
    $hall = Hall::factory()->create();
    $service = Service::factory()->create();
    HallService::factory()->create(['hall_id' => $hall->id, 'service_id' => $service->id]);

    $response = $this->getJson(route('user.home.hallServices', $hall));

    $response->assertOk();
    $response->assertJsonPath('data.0.price_min', null);
    $response->assertJsonPath('data.0.price_max', null);
});

test('a service is annotated with the min and max price staff at the hall charge for it', function () {
    $hall = Hall::factory()->create();
    $service = Service::factory()->create();
    HallService::factory()->create(['hall_id' => $hall->id, 'service_id' => $service->id]);

    $cheaperExpert = Expert::factory()->create();
    $cheaperExpertHall = ExpertHall::factory()->create(['expert_id' => $cheaperExpert->id, 'hall_id' => $hall->id]);
    $cheaperExpertHall->services()->attach($service->id, ['price' => 100000, 'duration' => 30]);

    $pricierExpert = Expert::factory()->create();
    $pricierExpertHall = ExpertHall::factory()->create(['expert_id' => $pricierExpert->id, 'hall_id' => $hall->id]);
    $pricierExpertHall->services()->attach($service->id, ['price' => 300000, 'duration' => 60]);

    $response = $this->getJson(route('user.home.hallServices', $hall));

    $response->assertOk();
    $response->assertJsonPath('data.0.price_min', 100000);
    $response->assertJsonPath('data.0.price_max', 300000);
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
