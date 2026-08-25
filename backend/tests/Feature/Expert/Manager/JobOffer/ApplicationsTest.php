<?php

use App\Models\Expert;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->jobOffer = JobOffer::factory()->create();
    $this->manager = Expert::query()->find($this->jobOffer->hall->owner_id);
});

test('manager should be authenticated to see the job offer applications', function () {
    $this->getJson(route('expert.job_offers.applications', [$this->jobOffer->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.job_offers.applications', [$this->jobOffer->id]))->assertUnauthorized();
});

test('manager can see the job offer applications', function () {
    JobOfferApplication::factory()->count(2)->create(['job_offer_id' => $this->jobOffer->id]);

    Sanctum::actingAs($this->manager, ['*'], 'expert');
    $response = $this->getJson(route('expert.job_offers.applications', [$this->jobOffer->id]));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'status',
                'status_label',
                'created_at',
                'expert' => [
                    'id',
                    'first_name',
                    'last_name',
                    'phone_number',
                    'avatar',
                    'portfolio',
                ],
            ],
        ],
    ]);
});
