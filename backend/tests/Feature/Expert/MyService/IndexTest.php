<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create(['is_verified' => true]);
    $this->hall = Hall::factory()->create();
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
});

test('expert should be authenticated to list her services', function () {
    $this->getJson(route('expert.myService.index', [$this->hall->id]))->assertUnauthorized();
});

test('an expert sees only her own priced services at the hall', function () {
    $mine = ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
    ]);

    $otherExpert = Expert::factory()->create(['is_verified' => true]);
    $otherExpertHall = ExpertHall::factory()->create(['expert_id' => $otherExpert->id, 'hall_id' => $this->hall->id]);
    ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $otherExpertHall->id,
    ]);

    $otherHall = Hall::factory()->create();
    $myOtherHall = ExpertHall::factory()->create(['expert_id' => $this->expert->id, 'hall_id' => $otherHall->id]);
    ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $myOtherHall->id,
    ]);

    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.myService.index', [$this->hall->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $mine->id);
});
