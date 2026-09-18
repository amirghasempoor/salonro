<?php

use App\Models\Expert;
use App\Models\JobOffer;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
});

test('expert should be authenticated to browse job offers', function () {
    $this->getJson(route('expert.jobs.index'))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.jobs.index'))->assertUnauthorized();
});

test('expert can browse only active job offers', function () {
    $expert = Expert::factory()->create();
    JobOffer::factory()->count(3)->create(['is_active' => true]);
    JobOffer::factory()->count(2)->create(['is_active' => false]);

    $expert->assignRole('expert');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.jobs.index', [
        'start' => 0,
        'size' => 5,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});
