<?php

use App\Models\Expert;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->service = Service::factory()->create(['sub_cat_name' => 'Cut']);
    $this->payload = ['cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 101, 'sub_cat_name' => 'Cut'];
});

function expertWithRole(?string $role): Expert
{
    $expert = Expert::factory()->create();

    if ($role !== null) {
        $expert->assignRole($role);
    }

    return $expert;
}

test('only an admin may change the shared service catalog', function (?string $role) {
    Sanctum::actingAs(expertWithRole($role), ['*'], 'expert');

    $this->postJson(route('expert.service_categories.store'), $this->payload)->assertForbidden();
    $this->postJson(route('expert.service_categories.update', $this->service->id), $this->payload)->assertForbidden();
    $this->deleteJson(route('expert.service_categories.destroy', $this->service->id))->assertForbidden();

    $this->assertDatabaseHas('services', ['id' => $this->service->id, 'sub_cat_name' => 'Cut']);
    $this->assertDatabaseCount('services', 1);
})->with([
    'an expert with no role' => [null],
    'an expert' => ['expert'],
    'a manager' => ['manager'],
]);

test('every logged-in expert can still read the catalog', function (?string $role) {
    Sanctum::actingAs(expertWithRole($role), ['*'], 'expert');

    $this->getJson(route('expert.service_categories.list'))->assertOk();
    $this->getJson(route('expert.service_categories.show', $this->service->id))->assertOk();
    $this->getJson(route('expert.service_categories.index', [
        'start' => 0, 'size' => 10,
        'filters' => json_encode([]), 'sorting' => json_encode([]),
    ]))->assertOk();
})->with([
    'an expert with no role' => [null],
    'an expert' => ['expert'],
    'a manager' => ['manager'],
]);
