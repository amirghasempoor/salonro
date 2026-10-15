<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

function myHallsQuery(): array
{
    return [
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ];
}

test('expert should be authenticated to see her halls', function () {
    $this->getJson(route('expert.profile.myHalls'))->assertUnauthorized();
});

test('a hall is marked manager when the expert owns it and expert when she only works there', function () {
    $expert = Expert::factory()->create();

    $ownHall = Hall::factory()->create(['owner_id' => $expert->id]);
    ExpertHall::factory()->create(['expert_id' => $expert->id, 'hall_id' => $ownHall->id]);

    $otherHall = Hall::factory()->create();
    ExpertHall::factory()->create(['expert_id' => $expert->id, 'hall_id' => $otherHall->id]);

    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.profile.myHalls', myHallsQuery()));

    $response->assertOk();
    $roles = collect($response->json('data'))->pluck('role', 'id');

    expect($roles)->toHaveCount(2)
        ->and($roles[$ownHall->id])->toBe('manager')
        ->and($roles[$otherHall->id])->toBe('expert');
});

test('a solo hall is returned as manager with its solo flag', function () {
    $expert = Expert::factory()->create();
    $hall = Hall::factory()->solo()->create(['owner_id' => $expert->id]);
    ExpertHall::factory()->create(['expert_id' => $expert->id, 'hall_id' => $hall->id]);

    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.profile.myHalls', myHallsQuery()));

    $response->assertOk();
    $response->assertJsonPath('data.0.role', 'manager');
    expect($response->json('data.0.is_solo'))->toBeTruthy();
});
