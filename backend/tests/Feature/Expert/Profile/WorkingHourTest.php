<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $this->hall = Hall::factory()->create(['id' => 3]);

    $this->expert->expertHalls()->create([
        'hall_id' => $this->hall->id,
        'is_active' => true,
    ]);

    $this->hall->workingHours()->create(['day' => 'sun', 'from' => '00:00:00', 'to' => '23:59:59']);
    $this->hall->workingHours()->create(['day' => 'mon', 'from' => '00:00:00', 'to' => '23:59:59']);
});

test('working hours is required', function () {
    $response = $this->postJson(route('expert.profile.defineWorkingHour', 3));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('workingHours');
});

test('working hours should be array', function () {
    $response = $this->postJson(route('expert.profile.defineWorkingHour', 3), [
        'workingHours' => fake()->lexify(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'workingHours' => __('validation.array', ['attribute' => 'working hours']),
    ]);
});

test('expert can upload the working hours', function () {
    $response = $this->postJson(route('expert.profile.defineWorkingHour', 3), [
        'workingHours' => [
            [
                'day' => 'sun',
                'from' => '8:00',
                'to' => '14:00',
            ],
            [
                'day' => 'mon',
                'from' => '9:00',
                'to' => '13:00',
            ],
        ],
    ]);

    $response->assertOk();

    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseCount('working_hours', 4);

    $this->assertDatabaseHas('working_hours', [
        'day' => 'sun',
        'from' => '8:00',
        'to' => '14:00',
    ]);

    $this->assertDatabaseHas('working_hours', [
        'day' => 'mon',
        'from' => '9:00',
        'to' => '13:00',
    ]);
});

test('expert cannot define working hours for a hall they are not actively assigned to', function () {
    $otherHall = Hall::factory()->create(['id' => 4]);

    $response = $this->postJson(route('expert.profile.defineWorkingHour', $otherHall->id), [
        'workingHours' => [
            ['day' => 'sun', 'from' => '8:00', 'to' => '14:00'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.expert_not_in_hall'));
    $this->assertDatabaseCount('working_hours', 2);
});

test('expert cannot set working hours outside the hall schedule', function () {
    $this->hall->workingHours()->create(['day' => 'tue', 'from' => '10:00:00', 'to' => '12:00:00']);

    $response = $this->postJson(route('expert.profile.defineWorkingHour', $this->hall->id), [
        'workingHours' => [
            ['day' => 'tue', 'from' => '9:00', 'to' => '13:00'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.working_hour_outside_hall_schedule'));
    $this->assertDatabaseMissing('working_hours', ['day' => 'tue', 'hourable_type' => ExpertHall::class]);
});

test('expert cannot set working hours for a day the hall has no schedule for', function () {
    $response = $this->postJson(route('expert.profile.defineWorkingHour', $this->hall->id), [
        'workingHours' => [
            ['day' => 'wed', 'from' => '9:00', 'to' => '13:00'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.working_hour_outside_hall_schedule'));
});

test('expert can set working hours that fit inside the hall schedule', function () {
    $this->hall->workingHours()->create(['day' => 'tue', 'from' => '10:00:00', 'to' => '18:00:00']);

    $response = $this->postJson(route('expert.profile.defineWorkingHour', $this->hall->id), [
        'workingHours' => [
            ['day' => 'tue', 'from' => '11:00', 'to' => '15:00'],
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('working_hours', ['day' => 'tue', 'from' => '11:00', 'to' => '15:00']);
});
