<?php

use App\Models\Expert;
use App\Models\JobOfferApplication;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
});

test('expert should be authenticated to see their applications', function () {
    $this->getJson(route('expert.jobs.myApplications'))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.jobs.myApplications'))->assertUnauthorized();
});

test('expert sees only their own job applications', function () {
    JobOfferApplication::factory()->count(2)->create(['expert_id' => $this->expert->id]);
    JobOfferApplication::factory()->count(3)->create();

    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $response = $this->getJson(route('expert.jobs.myApplications'));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});
