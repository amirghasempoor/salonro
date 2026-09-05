<?php

use App\Models\Expert;
use App\Models\JobOfferApplication;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->application = JobOfferApplication::factory()->create();
    $this->manager = Expert::query()->find($this->application->jobOffer->hall->owner_id);
});

test('manager should be authenticated to accept an application', function () {
    $this->postJson(route('expert.job_offers.acceptApplication', [$this->application->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.job_offers.acceptApplication', [$this->application->id]))->assertUnauthorized();
});

test('manager can accept an application', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $response = $this->postJson(route('expert.job_offers.acceptApplication', [$this->application->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('job_offer_applications', [
        'id' => $this->application->id,
        'status' => JobOfferApplication::STATUS_ACCEPTED,
    ]);
});
