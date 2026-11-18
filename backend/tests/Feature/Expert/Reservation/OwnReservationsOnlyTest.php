<?php

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();
    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);

    $this->staff = Expert::factory()->create();
    ExpertHall::factory()->create(['expert_id' => $this->staff->id, 'hall_id' => $this->hall->id]);

    $this->colleague = Expert::factory()->create();
    ExpertHall::factory()->create(['expert_id' => $this->colleague->id, 'hall_id' => $this->hall->id]);

    $this->mine = Reservation::factory()->create(['hall_id' => $this->hall->id, 'expert_id' => $this->staff->id]);
    $this->colleaguesOne = Reservation::factory()->create(['hall_id' => $this->hall->id, 'expert_id' => $this->colleague->id]);
});

function destroyUrl(Hall $hall, Reservation $reservation): string
{
    return route('expert.reservation.destroy', ['hall' => $hall->id, 'reservation' => $reservation->id]);
}

test('a staff member can see her own reservation', function () {
    Sanctum::actingAs($this->staff, ['*'], 'expert');

    $this->getJson(route('expert.reservation.show', $this->mine->id))->assertOk();
});

test('a staff member cannot see a colleagues reservation', function () {
    Sanctum::actingAs($this->staff, ['*'], 'expert');

    $this->getJson(route('expert.reservation.show', $this->colleaguesOne->id))->assertForbidden();
});

test('a staff member cannot reschedule a colleagues reservation', function () {
    Sanctum::actingAs($this->staff, ['*'], 'expert');

    $this->postJson(
        route('expert.reservation.update', ['hall' => $this->hall->id, 'reservation' => $this->colleaguesOne->id]),
        ['start_time' => '2030-01-01 10:00:00', 'finish_time' => '2030-01-01 11:00:00'],
    )->assertForbidden();

    expect($this->colleaguesOne->fresh()->start_time->toDateString())->not->toBe('2030-01-01');
});

test('a staff member cannot delete a colleagues reservation', function () {
    Sanctum::actingAs($this->staff, ['*'], 'expert');

    $this->deleteJson(destroyUrl($this->hall, $this->colleaguesOne))->assertForbidden();

    $this->assertDatabaseHas('reservations', ['id' => $this->colleaguesOne->id]);
});

test('a staff member can delete her own reservation', function () {
    Sanctum::actingAs($this->staff, ['*'], 'expert');

    $this->deleteJson(destroyUrl($this->hall, $this->mine))->assertOk();

    $this->assertDatabaseHas('reservations', ['id' => $this->mine->id, 'state_id' => ReservationStates::Cancel->value]);
});

test('the hall owner still manages every reservation in her hall', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->getJson(route('expert.reservation.show', $this->colleaguesOne->id))->assertOk();
    $this->deleteJson(destroyUrl($this->hall, $this->colleaguesOne))->assertOk();

    $this->assertDatabaseHas('reservations', ['id' => $this->colleaguesOne->id, 'state_id' => ReservationStates::Cancel->value]);
});

test('a reservation from another hall is still a 404 through the hall route', function () {
    $otherHall = Hall::factory()->create(['owner_id' => $this->staff->id]);
    ExpertHall::factory()->create(['expert_id' => $this->staff->id, 'hall_id' => $otherHall->id]);
    Sanctum::actingAs($this->staff, ['*'], 'expert');

    $this->deleteJson(destroyUrl($otherHall, $this->mine))->assertNotFound();
});
