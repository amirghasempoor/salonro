<?php

use App\Models\City;
use App\Models\Province;

beforeEach(function () {
    $this->province = Province::factory()->create();
    $this->cities = City::factory()->count(3)->create(['province_id' => $this->province->id]);
});

test('lists all provinces', function () {
    Province::factory()->count(2)->create();

    $this->getJson('/api/provinces/list')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('lists cities of a province', function () {
    $this->getJson("/api/cities/{$this->province->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.province_id', $this->province->id);
});

test('returns empty list for a province without cities', function () {
    $emptyProvince = Province::factory()->create();

    $this->getJson("/api/cities/{$emptyProvince->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
