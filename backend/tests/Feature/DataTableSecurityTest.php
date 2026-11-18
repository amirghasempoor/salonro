<?php

use App\Models\Hall;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function nearbyHalls(array $filters)
{
    return test()->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode($filters),
        'sorting' => json_encode([]),
    ]));
}

function textFilter(string $id, string $value = 'zzzz', string $fn = 'contains'): array
{
    return ['id' => $id, 'value' => $value, 'fn' => $fn, 'datatype' => 'text'];
}

beforeEach(function () {
    Hall::factory()->count(2)->create(['lat' => '35.70', 'lng' => '51.40']);
});

test('a normal filter still works', function () {
    Hall::factory()->create(['lat' => '35.70', 'lng' => '51.40', 'name' => 'Rose Garden']);

    $response = nearbyHalls([textFilter('name', 'rose garden', 'equals')]);

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.name', 'Rose Garden');
});

test('a contains filter still works', function () {
    Hall::factory()->create(['lat' => '35.70', 'lng' => '51.40', 'name' => 'Rose Garden']);

    nearbyHalls([textFilter('name', 'garden')])->assertOk()->assertJsonCount(1, 'data');
});

test('an injected column name is rejected, not executed', function (string $id) {
    nearbyHalls([textFilter($id)])->assertStatus(422)->assertJsonPath('type', 'invalid_parameter');
})->with([
    'or 1=1 in contains' => ['name) like ? or 1=1 or lower(name'],
    'subselect' => ['(select password from experts limit 1)'],
    'comment' => ['name -- '],
    'quote' => ['name"'],
    'space' => ['name x'],
    'two dots' => ['a.b.c'],
]);

test('the same injection is rejected on equals filters', function () {
    nearbyHalls([textFilter('name) = ? or 1=1 or lower(name', 'zzzz', 'equals')])->assertStatus(422);
});

test('the injection cannot read other customers reservations', function () {
    $mine = User::factory()->create();
    Reservation::factory()->create();
    Sanctum::actingAs($mine, ['*'], 'user');

    $this->getJson(route('user.reservation.index', [
        'start' => 0, 'size' => 10,
        'filters' => json_encode([textFilter('hall_name) like ? or 1=1 or lower(hall_name')]),
        'sorting' => json_encode([]),
    ]))->assertStatus(422);
});

test('sensitive columns cannot be used as a filter oracle', function (string $id) {
    nearbyHalls([textFilter($id, '$2y')])->assertStatus(422);
})->with([
    'owner password' => ['owner.password'],
    'own password' => ['password'],
    'remember token' => ['owner.remember_token'],
]);

test('a relation filter on a real relation still works', function () {
    $hall = Hall::factory()->create(['lat' => '35.70', 'lng' => '51.40']);

    nearbyHalls([textFilter('owner.first_name', (string) $hall->owner->first_name, 'equals')])
        ->assertOk();
});

test('a method name that is not a relation is not called', function () {
    // "delete" is a real method on the model but not a relation: it must be treated
    // as a plain column and never invoked through whereHas().
    $before = Hall::query()->count();

    nearbyHalls([textFilter('delete.id', '1', 'equals')]);

    expect(Hall::query()->count())->toBe($before);
});
