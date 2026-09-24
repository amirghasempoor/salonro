<?php

use App\Models\Expert;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->manager = Expert::factory()->create();
    $this->manager->assignRole('manager');
    $this->service = Service::factory()->create();
});

test('manager should be authenticated to see a service category', function () {
    $this->getJson(route('expert.service_categories.show', $this->service->id))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->manager, ['*'], 'user');
    $this->getJson(route('expert.service_categories.show', $this->service->id))->assertUnauthorized();
});

test('manager can see a service category', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->getJson(route('expert.service_categories.show', $this->service->id))
        ->assertOk()
        ->assertJsonPath('data.id', $this->service->id)
        ->assertJsonPath('data.sub_cat_name', $this->service->sub_cat_name);
});
