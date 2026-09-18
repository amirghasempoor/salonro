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

test('manager cannot accept an application for a job offer they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.job_offers.acceptApplication', [$this->application->id]))
        ->assertForbidden();
});

test('accepting an application onboards the expert to the hall', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $applicant = Expert::query()->find($this->application->expert_id);
    $hall = $this->application->jobOffer->hall;

    expect($applicant->hasRole('expert'))->toBeFalse();
    $this->assertDatabaseMissing('expert_hall', [
        'expert_id' => $applicant->id,
        'hall_id' => $hall->id,
    ]);

    $this->postJson(route('expert.job_offers.acceptApplication', [$this->application->id]))
        ->assertOk();

    expect($applicant->fresh()->hasRole('expert'))->toBeTrue();
    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $applicant->id,
        'hall_id' => $hall->id,
    ]);
});

test('accepting an already processed application fails', function () {
    $this->manager->assignRole('manager');
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->application->update(['status' => JobOfferApplication::STATUS_REJECTED]);

    $response = $this->postJson(route('expert.job_offers.acceptApplication', [$this->application->id]));

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.job_offer_application_not_pending'));
});
