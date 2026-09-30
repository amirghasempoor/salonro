<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $this->hall->workingHours()->create(['day' => 'sat', 'from' => '00:00:00', 'to' => '23:59:59']);
});

test('manager should be authenticated to define working hours', function () {
    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id))->assertUnauthorized();
});

test('working hours is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id))
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('workingHours');
});

test('each working hour needs a valid day, from and to', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [['day' => 'someday']],
    ])->assertStatus(422)->assertJsonValidationErrors(['workingHours.0.day', 'workingHours.0.from', 'workingHours.0.to']);
});

test('manager can define the hall working hours', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [
            ['day' => 'sat', 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'sun', 'from' => '09:00', 'to' => '18:00'],
        ],
    ]);

    $response->assertOk();
    $response->assertExactJson(['message' => __('messages.successful')]);

    $this->assertDatabaseCount('working_hours', 2);
    $this->assertDatabaseHas('working_hours', [
        'hourable_type' => Hall::class,
        'hourable_id' => $this->hall->id,
        'day' => 'sat',
        'from' => '09:00',
        'to' => '18:00',
    ]);
    $this->assertDatabaseHas('working_hours', [
        'hourable_type' => Hall::class,
        'hourable_id' => $this->hall->id,
        'day' => 'sun',
        'from' => '09:00',
        'to' => '18:00',
    ]);
});

test('defining working hours replaces the previous schedule instead of appending to it', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [['day' => 'mon', 'from' => '10:00', 'to' => '16:00']],
    ])->assertOk();

    $this->assertDatabaseCount('working_hours', 1);
    $this->assertDatabaseMissing('working_hours', ['hourable_type' => Hall::class, 'hourable_id' => $this->hall->id, 'day' => 'sat']);
    $this->assertDatabaseHas('working_hours', ['hourable_type' => Hall::class, 'hourable_id' => $this->hall->id, 'day' => 'mon']);
});

test('manager cannot define working hours on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [['day' => 'mon', 'from' => '10:00', 'to' => '16:00']],
    ])->assertForbidden();

    $this->assertDatabaseCount('working_hours', 1);
});

test('manager cannot shrink the hall schedule below an assigned staff member working hours', function () {
    $staff = Expert::factory()->create();
    $this->hall->experts()->attach($staff->id, ['joined_at' => now()]);
    $expertHall = ExpertHall::query()->where('expert_id', $staff->id)->where('hall_id', $this->hall->id)->first();
    $expertHall->workingHours()->create(['day' => 'sat', 'from' => '09:00:00', 'to' => '18:00:00']);

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [
            ['day' => 'sat', 'from' => '10:00', 'to' => '16:00'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.hall_schedule_below_staff_schedule'));

    $this->assertDatabaseHas('working_hours', [
        'hourable_type' => Hall::class,
        'hourable_id' => $this->hall->id,
        'day' => 'sat',
        'from' => '00:00:00',
        'to' => '23:59:59',
    ]);
});

test('manager cannot remove a day a staff member is scheduled on', function () {
    $staff = Expert::factory()->create();
    $this->hall->experts()->attach($staff->id, ['joined_at' => now()]);
    $expertHall = ExpertHall::query()->where('expert_id', $staff->id)->where('hall_id', $this->hall->id)->first();
    $expertHall->workingHours()->create(['day' => 'sat', 'from' => '09:00:00', 'to' => '18:00:00']);

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [
            ['day' => 'sun', 'from' => '09:00', 'to' => '18:00'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.hall_schedule_below_staff_schedule'));
});

test('manager can widen the hall schedule while keeping staff working hours covered', function () {
    $staff = Expert::factory()->create();
    $this->hall->experts()->attach($staff->id, ['joined_at' => now()]);
    $expertHall = ExpertHall::query()->where('expert_id', $staff->id)->where('hall_id', $this->hall->id)->first();
    $expertHall->workingHours()->create(['day' => 'sat', 'from' => '10:00:00', 'to' => '16:00:00']);

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.hall.defineWorkingHours', $this->hall->id), [
        'workingHours' => [
            ['day' => 'sat', 'from' => '08:00', 'to' => '20:00'],
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('working_hours', [
        'hourable_type' => Hall::class,
        'hourable_id' => $this->hall->id,
        'day' => 'sat',
        'from' => '08:00',
        'to' => '20:00',
    ]);
});
