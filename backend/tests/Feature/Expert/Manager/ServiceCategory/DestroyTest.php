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

test('manager should be authenticated to delete a service category', function () {
    $this->deleteJson(route('expert.service_categories.destroy', $this->service->id))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->manager, ['*'], 'user');
    $this->deleteJson(route('expert.service_categories.destroy', $this->service->id))->assertUnauthorized();
});

test('manager can delete a service category', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->deleteJson(route('expert.service_categories.destroy', $this->service->id))->assertOk();

    $this->assertDatabaseMissing('services', ['id' => $this->service->id]);
});
