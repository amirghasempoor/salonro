<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

// Prices and durations are whole numbers inside sane limits, so nothing is
// silently truncated or stored as a negative or absurd value.
beforeEach(function () {
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();
    $this->service = Service::factory()->create();
    HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);
    ExpertHall::factory()->create(['expert_id' => $this->expert->id, 'hall_id' => $this->hall->id]);

    Sanctum::actingAs($this->expert, ['*'], 'expert');
});

dataset('bad values', [
    'negative price' => [['price' => -1, 'duration' => 30], 'services.0.price'],
    'fractional price' => [['price' => 100.5, 'duration' => 30], 'services.0.price'],
    'absurd price' => [['price' => 1e15, 'duration' => 30], 'services.0.price'],
    'zero duration' => [['price' => 1000, 'duration' => 0], 'services.0.duration'],
    'fractional duration' => [['price' => 1000, 'duration' => 30.5], 'services.0.duration'],
    'duration over a day' => [['price' => 1000, 'duration' => 1441], 'services.0.duration'],
]);

it('rejects unreasonable price or duration on store', function (array $values, string $field) {
    $this->postJson(route('expert.myService.store', [$this->hall->id]), [
        'services' => [['service_id' => $this->service->id, ...$values]],
    ])->assertStatus(422)->assertJsonValidationErrors($field);
})->with('bad values');
