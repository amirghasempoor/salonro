<?php

use App\Models\Expert;
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

    $this->assertDatabaseCount('working_hours', 2);

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
