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

dataset('bad table input', [
    'text start' => [['start' => 'abc']],
    'negative start' => [['start' => -5]],
    'text size' => [['size' => 'many']],
    'zero size' => [['size' => 0]],
    'huge size' => [['size' => 100000000]],
    'filter missing fields' => [['filters' => '[{"id":"name"}]']],
    'filter not an object' => [['filters' => '["name"]']],
    'sorting missing desc' => [['sorting' => '[{"id":"name"}]']],
]);

it('answers bad paging, filter or sorting input with a 422, never a 500', function (array $query) {
    $hall = Hall::factory()->create();
    Sanctum::actingAs(Expert::query()->find($hall->owner_id), ['*'], 'expert');

    $this->getJson(route('expert.profile.myHalls', $query))
        ->assertStatus(422)->assertJsonPath('type', 'invalid_parameter');
})->with('bad table input');

test('rels that are not an array are ignored and valid paging still works', function () {
    $hall = Hall::factory()->create();
    Sanctum::actingAs(Expert::query()->find($hall->owner_id), ['*'], 'expert');

    $this->getJson(route('expert.profile.myHalls', ['rels' => 'oops', 'start' => 0, 'size' => 10]))->assertOk();
});
