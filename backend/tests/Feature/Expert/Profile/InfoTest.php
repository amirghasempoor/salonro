<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\WorkingHour;
use Laravel\Sanctum\Sanctum;

test('expert should be authenticated to see the info', function () {
    $this->getJson(route('expert.profile.info'))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.profile.info'))->assertUnauthorized();
});

test('expert can see the profile info', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.profile.info'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'first_name',
            'last_name',
            'avatar',
            'phone_number',
            'role',
            'halls',
            'is_verified',
            'bio',
            'is_active',
        ],
    ]);
});

test('profile info includes the expert working hours for each hall', function () {
    $expert = Expert::factory()->create();
    $hall = Hall::factory()->create();
    $expertHall = ExpertHall::factory()->create([
        'expert_id' => $expert->id,
        'hall_id' => $hall->id,
    ]);
    WorkingHour::factory()->create([
        'hourable_id' => $expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'sat',
        'from' => '09:00:00',
        'to' => '18:00:00',
    ]);

    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.profile.info'));

    $response->assertOk();
    $response->assertJsonPath('data.halls.0.id', $hall->id);
    $response->assertJsonPath('data.halls.0.working_hours.0.day', 'sat');
    $response->assertJsonPath('data.halls.0.working_hours.0.from', '09:00:00');
    $response->assertJsonPath('data.halls.0.working_hours.0.to', '18:00:00');
});
