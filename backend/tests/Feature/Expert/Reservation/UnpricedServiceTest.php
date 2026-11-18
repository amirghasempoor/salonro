<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();

    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->service = Service::factory()->create();

    HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);

    $this->expertHall = ExpertHall::factory()->create(['expert_id' => $this->owner->id, 'hall_id' => $this->hall->id]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->expertService = ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 60,
    ]);

    $this->payload = [
        'phone_number' => '09120000000',
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'services' => [['service_id' => $this->service->id]],
        'start_time' => now()->addDay()->toDateTimeString(),
        'finish_time' => now()->addDay()->addHour()->toDateTimeString(),
    ];
});

test('booking a service the expert has not fully priced is a clean 422 and never a free or broken booking', function (string $missing) {
    $this->expertService->update([$missing => null]);
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload)
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.service_not_priced_by_expert'));

    $this->assertDatabaseCount('reservations', 0);
})->with(['price', 'duration']);
