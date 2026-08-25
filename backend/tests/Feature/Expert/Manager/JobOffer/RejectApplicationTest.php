<?php

use App\Models\Expert;
use App\Models\JobOfferApplication;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->application = JobOfferApplication::factory()->create();
    $this->manager = Expert::query()->find($this->application->jobOffer->hall->owner_id);
});

test('manager should be authenticated to reject an application', function () {
    $this->postJson(route('expert.job_offers.rejectApplication', [$this->application->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.job_offers.rejectApplication', [$this->application->id]))->assertUnauthorized();
});

test('manager can reject an application', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $response = $this->postJson(route('expert.job_offers.rejectApplication', [$this->application->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('job_offer_applications', [
        'id' => $this->application->id,
        'status' => JobOfferApplication::STATUS_REJECTED,
    ]);
});
