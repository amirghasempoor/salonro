<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->expertHall = ExpertHall::factory()->create();
    $this->staff = Expert::query()->find($this->expertHall->expert_id);
});

test('manager should be authenticated to delete a staff', function () {
    $this->deleteJson(route('expert.staff.destroy', [$this->expertHall->hall_id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->deleteJson(route('expert.staff.destroy', [$this->expertHall->hall_id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager can delete a staff', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');

    $service = Service::factory()->create();
    $this->staff->services()->attach($service->id);

    $expertHall = ExpertHall::query()
        ->where('expert_id', $this->staff->id)
        ->where('hall_id', $this->expertHall->hall_id)
        ->first();
    $expertHall->services()->attach($service->id);

    $response = $this->delete(route('expert.staff.destroy', [$this->expertHall->hall_id, $this->staff->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseMissing('experts', [
        'id' => $this->staff->id,
    ]);

    $this->assertDatabaseMissing('expert_hall', [
        'expert_id' => $this->staff->id,
    ]);

    $this->assertDatabaseMissing('expert_service', [
        'serviceable_type' => Expert::class,
        'serviceable_id' => $this->staff->id,
    ]);

    $this->assertDatabaseMissing('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $expertHall->id,
    ]);
});
