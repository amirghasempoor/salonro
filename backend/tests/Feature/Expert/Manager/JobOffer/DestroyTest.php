<?php

use App\Models\Expert;
use App\Models\JobOffer;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->jobOffer = JobOffer::factory()->create();
    $this->manager = Expert::query()->find($this->jobOffer->hall->owner_id);
});

test('manager should be authenticated to delete a job offer', function () {
    $this->deleteJson(route('expert.job_offers.destroy', [$this->jobOffer->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->deleteJson(route('expert.job_offers.destroy', [$this->jobOffer->id]))->assertUnauthorized();
});

test('manager can delete a job offer', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $response = $this->deleteJson(route('expert.job_offers.destroy', [$this->jobOffer->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseMissing('job_offers', [
        'id' => $this->jobOffer->id,
    ]);
});
