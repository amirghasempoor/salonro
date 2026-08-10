<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');

    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $this->service = Service::factory()->create();
});

test('expert can add a service to a hall', function () {
    $this->postJson("/expert/services/{$this->hall->id}", [
        'service_id' => $this->service->id,
        'duration' => 60,
        'price' => 150000,
        'description' => 'Full haircut',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['hall_service_id']]);

    $this->assertDatabaseHas('hall_service', [
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
    ]);
});

test('expert can list hall services', function () {
    HallService::factory()->count(3)->create(['hall_id' => $this->hall->id]);

    $this->getJson("/expert/services/{$this->hall->id}?filters=[]&sorting=[]")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('expert can view a hall service', function () {
    $hallService = HallService::factory()->create(['hall_id' => $this->hall->id]);

    $this->getJson("/expert/services/{$this->hall->id}/{$hallService->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $hallService->id);
});

test('expert can update a hall service', function () {
    $hallService = HallService::factory()->create(['hall_id' => $this->hall->id]);

    $this->postJson("/expert/services/{$this->hall->id}/{$hallService->id}", [
        'service_id' => $this->service->id,
        'duration' => 90,
        'price' => 200000,
        'is_active' => false,
    ])->assertOk();

    $this->assertDatabaseHas('hall_service', [
        'id' => $hallService->id,
        'price' => 200000,
        'is_active' => false,
    ]);
});

test('expert can remove a hall service', function () {
    $hallService = HallService::factory()->create(['hall_id' => $this->hall->id]);

    $this->deleteJson("/expert/services/{$this->hall->id}/{$hallService->id}")->assertOk();

    $this->assertDatabaseMissing('hall_service', ['id' => $hallService->id]);
});
