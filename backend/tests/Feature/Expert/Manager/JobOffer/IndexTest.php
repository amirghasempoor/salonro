<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\JobOffer;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->hall = Hall::factory()->create();
    $this->manager = Expert::query()->find($this->hall->owner_id);
});

test('manager should be authenticated to see the hall job offers', function () {
    $this->getJson(route('expert.job_offers.index', [$this->hall->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.job_offers.index', [$this->hall->id]))->assertUnauthorized();
});

test('manager can see the hall job offers', function () {
    JobOffer::factory()->count(2)->create(['hall_id' => $this->hall->id]);

    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->getJson(route('expert.job_offers.index', [
        'hall' => $this->hall->id,
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'profession_id',
                'description',
                'is_active',
                'created_at',
            ],
        ],
        'meta' => [
            'totalRowCount',
        ],
    ]);
    $response->assertJsonPath('meta.totalRowCount', 2);
});
