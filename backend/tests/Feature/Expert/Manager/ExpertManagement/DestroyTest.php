<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $this->staff = Expert::factory()->create();
    $this->hall->experts()->attach($this->staff->id, ['joined_at' => now()]);
});

test('manager should be authenticated to delete a staff', function () {
    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager can delete a staff', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $service = Service::factory()->create();
    $this->staff->services()->attach($service->id);

    $expertHall = ExpertHall::query()
        ->where('expert_id', $this->staff->id)
        ->where('hall_id', $this->hall->id)
        ->first();
    $expertHall->services()->attach($service->id);

    $response = $this->delete(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]));

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

test('manager cannot delete a staff on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]))
        ->assertForbidden();
});

test('manager cannot delete a staff that does not belong to their hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $foreignStaff = Expert::factory()->create();

    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $foreignStaff->id]))
        ->assertForbidden();
});
