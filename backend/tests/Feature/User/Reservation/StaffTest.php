<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->serviceA = Service::factory()->create();
    $this->serviceB = Service::factory()->create();

    $this->expertWithA = Expert::factory()->create();
    $expertHallA = ExpertHall::factory()->create([
        'expert_id' => $this->expertWithA->id,
        'hall_id' => $this->hall->id,
    ]);
    $expertHallA->services()->attach($this->serviceA->id);

    $this->expertWithB = Expert::factory()->create();
    $expertHallB = ExpertHall::factory()->create([
        'expert_id' => $this->expertWithB->id,
        'hall_id' => $this->hall->id,
    ]);
    $expertHallB->services()->attach($this->serviceB->id);
});

test('the user should be authenticated to see a hall staff', function () {
    $this->getJson(route('user.reservation.staff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id],
    ]))->assertUnauthorized();
});

test('the user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->user, ['*'], 'expert');

    $this->getJson(route('user.reservation.staff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id],
    ]))->assertUnauthorized();
});

test('it requires service_ids', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staff', $this->hall->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids']);
});

test('it validates service_ids', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staff', [$this->hall->id, 'service_ids' => ['not-an-id']]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids.0']);
});

test('it returns only staff offering the requested service', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id],
    ]));

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->expertWithA->id);
    expect($data[0]['services'])->toBe([$this->serviceA->sub_cat_name]);
});

test('it returns staff matching any of several requested services', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id, $this->serviceB->id],
    ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('services only lists the requested services a staff member actually offers', function () {
    $thirdService = Service::factory()->create();
    ExpertHall::query()
        ->where('expert_id', $this->expertWithA->id)
        ->first()
        ->services()
        ->attach($thirdService->id);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id],
    ]));

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['services'])->toBe([$this->serviceA->sub_cat_name]);
});
