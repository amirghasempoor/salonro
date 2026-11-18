<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

// Working hours must be real, ordered, non-overlapping clock windows.
beforeEach(function () {
    seedRoles();
    $this->expert = Expert::factory()->create();
    $this->expert->assignRole('manager');
    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    ExpertHall::factory()->create(['expert_id' => $this->expert->id, 'hall_id' => $this->hall->id]);
    foreach (['sat', 'sun'] as $day) {
        $this->hall->workingHours()->create(['day' => $day, 'from' => '00:00:00', 'to' => '23:59:59']);
    }

    Sanctum::actingAs($this->expert, ['*'], 'expert');
});

dataset('bad windows', [
    'not a time' => [[['day' => 'sat', 'from' => 'abc', 'to' => '12:00']]],
    'hour out of range' => [[['day' => 'sat', 'from' => '25:00', 'to' => '26:00']]],
    'ends before it starts' => [[['day' => 'sat', 'from' => '14:00', 'to' => '09:00']]],
    'zero length' => [[['day' => 'sat', 'from' => '09:00', 'to' => '09:00']]],
    'overlapping on one day' => [[
        ['day' => 'sat', 'from' => '09:00', 'to' => '13:00'],
        ['day' => 'sat', 'from' => '12:00', 'to' => '17:00'],
    ]],
]);

it('rejects nonsense windows when the expert sets her own hours', function (array $windows) {
    $this->postJson(route('expert.profile.defineWorkingHour', $this->hall->id), ['workingHours' => $windows])
        ->assertStatus(422)->assertJsonValidationErrors('workingHours');
})->with('bad windows');

it('rejects nonsense windows when the manager sets the hall hours', function (array $windows) {
    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), ['workingHours' => $windows])
        ->assertStatus(422)->assertJsonValidationErrors('workingHours');
})->with('bad windows');

it('accepts split shifts, adjacent windows and unpadded times', function () {
    $this->postJson(route('expert.profile.defineWorkingHour', $this->hall->id), ['workingHours' => [
        ['day' => 'sat', 'from' => '8:00', 'to' => '12:00'],
        ['day' => 'sat', 'from' => '12:00', 'to' => '17:30:00'],
        ['day' => 'sun', 'from' => '09:00', 'to' => '13:00'],
    ]])->assertOk();
});
