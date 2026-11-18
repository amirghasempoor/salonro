<?php

use App\Models\Expert;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

test('the public halls list works without filters or sorting', function () {
    Hall::factory()->create();

    $this->getJson(route('user.home.hallsInArea', ['lat' => 35.59, 'lng' => 51.43]))->assertOk();
});

test('my halls works without filters or sorting', function () {
    $hall = Hall::factory()->create();
    Sanctum::actingAs(Expert::query()->find($hall->owner_id), ['*'], 'expert');

    $this->getJson(route('expert.profile.myHalls'))->assertOk();
});

test('malformed filters are treated as no filters', function () {
    $hall = Hall::factory()->create();
    Sanctum::actingAs(Expert::query()->find($hall->owner_id), ['*'], 'expert');

    $this->getJson(route('expert.profile.myHalls', ['filters' => 'not-json', 'sorting' => '"x"']))->assertOk();
});
