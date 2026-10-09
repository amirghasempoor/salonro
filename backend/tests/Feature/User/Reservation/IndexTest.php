<?php

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

test('the user should be authenticated to list their reservations', function () {
    $this->getJson(route('user.reservation.index'))->assertUnauthorized();
});

test('the user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->getJson(route('user.reservation.index'))->assertUnauthorized();
});

test('it only lists the authenticated user\'s own reservations', function () {
    $own = Reservation::factory()->create(['user_id' => $this->owner->id]);
    Reservation::factory()->create();

    Sanctum::actingAs($this->owner, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.index', [
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($own->id);
});

test('each reservation lists the services it has', function () {
    $reservation = Reservation::factory()->create(['user_id' => $this->owner->id]);
    $serviceA = Service::factory()->create();
    $serviceB = Service::factory()->create();
    $reservation->services()->attach([
        $serviceA->id => ['service_name' => $serviceA->sub_cat_name, 'price' => 1000, 'duration' => 30],
        $serviceB->id => ['service_name' => $serviceB->sub_cat_name, 'price' => 2000, 'duration' => 45],
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.index', [
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $services = $response->json('data.0.services');

    expect($services)->toHaveCount(2);
    expect(collect($services)->pluck('sub_cat_name')->all())
        ->toEqualCanonicalizing([$serviceA->sub_cat_name, $serviceB->sub_cat_name]);
});
