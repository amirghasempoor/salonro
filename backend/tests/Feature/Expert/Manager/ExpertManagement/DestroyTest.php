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

test('manager can remove a staff member from the hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $portfolioService = Service::factory()->create();
    $this->staff->services()->attach($portfolioService->id);

    $hallService = Service::factory()->create();
    $expertHall = ExpertHall::query()
        ->where('expert_id', $this->staff->id)
        ->where('hall_id', $this->hall->id)
        ->first();
    $expertHall->services()->attach($hallService->id);

    $response = $this->delete(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    // the expert account itself is untouched
    $this->assertDatabaseHas('experts', [
        'id' => $this->staff->id,
    ]);

    // removed from this hall only
    $this->assertDatabaseMissing('expert_hall', [
        'expert_id' => $this->staff->id,
        'hall_id' => $this->hall->id,
    ]);

    // that hall membership's own services are cleared
    $this->assertDatabaseMissing('expert_service', [
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $expertHall->id,
    ]);

    // the expert's portfolio-level services are untouched
    $this->assertDatabaseHas('expert_service', [
        'serviceable_type' => Expert::class,
        'serviceable_id' => $this->staff->id,
        'service_id' => $portfolioService->id,
    ]);
});

test('removing a staff member from one hall does not affect their membership at another hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $otherHall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $otherHall->experts()->attach($this->staff->id, ['joined_at' => now()]);

    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]))
        ->assertOk();

    $this->assertDatabaseMissing('expert_hall', [
        'expert_id' => $this->staff->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $this->staff->id,
        'hall_id' => $otherHall->id,
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
