<?php

use App\Models\Expert;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->expert = Expert::factory()->create();
    $this->jobOffer = JobOffer::factory()->create();
});

test('expert should be authenticated to apply to a job offer', function () {
    $this->postJson(route('expert.jobs.apply', [$this->jobOffer->id]))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.jobs.apply', [$this->jobOffer->id]))->assertUnauthorized();
});

test('expert can apply to a job offer', function () {
    $this->expert->assignRole('expert');
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->postJson(route('expert.jobs.apply', [$this->jobOffer->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('job_offer_applications', [
        'job_offer_id' => $this->jobOffer->id,
        'expert_id' => $this->expert->id,
        'status' => JobOfferApplication::STATUS_PENDING,
    ]);
});

test('expert cannot apply twice to the same job offer', function () {
    JobOfferApplication::factory()->create([
        'job_offer_id' => $this->jobOffer->id,
        'expert_id' => $this->expert->id,
    ]);

    $this->expert->assignRole('expert');
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->postJson(route('expert.jobs.apply', [$this->jobOffer->id]));

    $response->assertStatus(422);
    $response->assertJson([
        'type' => 'logical_exception',
        'message' => __('messages.already_applied'),
    ]);

    $this->assertDatabaseCount('job_offer_applications', 1);
});
