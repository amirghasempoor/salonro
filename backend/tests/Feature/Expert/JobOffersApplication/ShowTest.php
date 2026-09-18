<?php

use App\Models\Expert;
use App\Models\JobOffer;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->expert = Expert::factory()->create();
    $this->jobOffer = JobOffer::factory()->create();
});

test('expert should be authenticated to see job offer details', function () {
    $this->getJson(route('expert.jobs.show', [$this->jobOffer->id]))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.jobs.show', [$this->jobOffer->id]))->assertUnauthorized();
});

test('expert can see job offer details', function () {
    $this->expert->assignRole('expert');
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->getJson(route('expert.jobs.show', [$this->jobOffer->id]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'hall_id',
            'hall_name',
            'expert_id',
            'profession_id',
            'profession_name',
            'province_id',
            'province_name',
            'city_id',
            'city_name',
        ],
    ]);
});
