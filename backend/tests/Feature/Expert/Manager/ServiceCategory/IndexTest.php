<?php

use App\Models\Expert;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->manager = Expert::factory()->create();
    $this->manager->assignRole('manager');
});

test('manager should be authenticated to list service categories', function () {
    $this->getJson(route('expert.service_categories.index'))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->manager, ['*'], 'user');
    $this->getJson(route('expert.service_categories.index'))->assertUnauthorized();
});

test('manager can list services', function () {
    Service::factory()->count(3)->create();
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $response = $this->getJson(route('expert.service_categories.index', [
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

test('list groups services under their category', function () {
    Service::factory()->create(['cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 101, 'sub_cat_name' => 'Cut']);
    Service::factory()->create(['cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 102, 'sub_cat_name' => 'Color']);
    Service::factory()->create(['cat_id' => 2, 'cat_name' => 'Nails', 'sub_cat_id' => 201, 'sub_cat_name' => 'Manicure']);
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $response = $this->getJson(route('expert.service_categories.list'));

    $response->assertOk();
    $response->assertJsonCount(2);
    $response->assertJsonPath('0.cat_id', 1);
    $response->assertJsonPath('0.title', 'Hair');
    $response->assertJsonCount(2, '0.templates');
    $response->assertJsonPath('0.templates.0.name', 'Cut');
    $response->assertJsonPath('1.title', 'Nails');
});
