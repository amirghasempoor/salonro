<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use User\Domain\Entities\Hall as DomainHall;
use User\Domain\Exceptions\Reservation\ServiceNotPricedByExpertException;

// An expert hired by a manager starts with her services attached but without the
// price and duration she still has to set herself.
beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();
    $this->service = Service::factory()->create();

    HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);

    $this->expertHall = ExpertHall::factory()->create(['expert_id' => $this->expert->id, 'hall_id' => $this->hall->id]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->expertService = ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 60,
    ]);

    $this->payload = [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'services' => [$this->service->id],
        'start_time' => now()->addDay()->toDateTimeString(),
        'finish_time' => now()->addDay()->addHour()->toDateTimeString(),
    ];
});

test('booking an expert who has not set her price is a clean 422, not a server error', function (string $missing) {
    $this->expertService->update([$missing => null]);
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->postJson(route('user.reservation.store'), $this->payload)
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.service_not_priced_by_expert'));

    $this->assertDatabaseCount('reservations', 0);
})->with(['price', 'duration']);

test('the domain refuses to price a service the expert has not fully priced', function (string $missing) {
    $member = [
        'id' => $this->expert->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'avatar' => null,
        'services' => [['id' => $this->service->id, 'sub_cat_name' => 'Cut', 'price' => 100, 'duration' => 30, $missing => null]],
    ];

    $catalog = [['id' => $this->service->id, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 1, 'sub_cat_name' => 'Cut', 'icon' => null]];

    (new DomainHall($this->hall->id, 'Rose', $catalog, [$member]))->priceServices([$this->service->id], $this->expert->id);
})->with(['price', 'duration'])->throws(ServiceNotPricedByExpertException::class);

test('an expert who has not priced the service is not offered to the customer', function (string $missing) {
    $this->expertService->update([$missing => null]);
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staff', [
        'hall' => $this->hall->id,
        'service_ids' => [$this->service->id],
    ]))->assertOk()->assertJsonCount(0, 'data');
})->with(['price', 'duration']);

test('a fully priced expert is still offered and bookable', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staff', [
        'hall' => $this->hall->id,
        'service_ids' => [$this->service->id],
    ]))->assertOk()->assertJsonCount(1, 'data');

    $this->postJson(route('user.reservation.store'), $this->payload)->assertOk();
});

test('the hall catalog shows no price range for a service only an unpriced expert offers', function () {
    $this->expertService->update(['price' => null]);

    $this->getJson(route('user.home.hallServices', $this->hall))->assertOk()->assertJsonCount(0, 'data');
});
