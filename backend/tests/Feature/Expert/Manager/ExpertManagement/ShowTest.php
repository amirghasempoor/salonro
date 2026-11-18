<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->expertHall = ExpertHall::factory()->create();
    $this->staff = Expert::query()->find($this->expertHall->expert_id);
    $this->hall = Hall::query()->find($this->expertHall->hall_id);
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->owner->assignRole('manager');
});

function staffDetails(Expert $staff, mixed $hallId)
{
    return test()->getJson(route('expert.staff.show', ['expert' => $staff->id, 'hall_id' => $hallId]));
}

test('manager should be authenticated to see a staff', function () {
    $this->getJson(route('expert.staff.show', [$this->staff->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.staff.show', [$this->staff->id]))->assertUnauthorized();
});

test('manager can see a staff details', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = staffDetails($this->staff, $this->hall->id);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'first_name',
            'last_name',
            'avatar',
            'phone_number',
            'working_hours',
            'portfolio',
        ],
    ]);
});

test('staff details include the expert portfolio images', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->staff->images()->create(['url' => 'portfolios/1/photo.jpg', 'title' => 'Haircut']);

    $response = staffDetails($this->staff, $this->hall->id);

    $response->assertOk();
    $response->assertJsonPath('data.portfolio.0.title', 'Haircut');
    $response->assertJsonPath('data.portfolio.0.url', 'portfolios/1/photo.jpg');
});

test('staff details list the expert working hours at that hall as real data', function () {
    $this->expertHall->workingHours()->create(['day' => 'sat', 'from' => '09:00', 'to' => '17:00']);
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = staffDetails($this->staff, $this->hall->id);

    $response->assertOk();
    $response->assertJsonCount(1, 'data.working_hours');
    $response->assertJsonPath('data.working_hours.0.day', 'sat');
});

test('a manager cannot read an expert who does not work in her hall', function () {
    $stranger = Expert::factory()->create();
    $stranger->assignRole('manager');
    Sanctum::actingAs($stranger, ['*'], 'expert');

    $ownHall = Hall::factory()->create(['owner_id' => $stranger->id]);

    staffDetails($this->staff, $ownHall->id)->assertForbidden();
});

test('a manager cannot read an expert through someone elses hall', function () {
    $stranger = Expert::factory()->create();
    $stranger->assignRole('manager');
    Sanctum::actingAs($stranger, ['*'], 'expert');

    staffDetails($this->staff, $this->hall->id)->assertForbidden();
});

test('the owner of a hall cannot read an expert who is not on her staff', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $outsider = Expert::factory()->create();

    staffDetails($outsider, $this->hall->id)->assertForbidden();
});

test('the hall id is required', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->getJson(route('expert.staff.show', ['expert' => $this->staff->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('hall_id');
});

test('a hall that does not exist is a 404', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    staffDetails($this->staff, 999999)->assertNotFound();
});

test('a non numeric hall id is rejected, not a server error', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    staffDetails($this->staff, 'abc')->assertStatus(422);
});
