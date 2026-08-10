<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Service;

beforeEach(function () {
    seedRoles();

    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');

    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $this->service = Service::factory()->create();
});

test('expert can add a staff member', function () {
    $this->postJson("/expert/staff/{$this->hall->id}", [
        'first_name' => 'Sara',
        'last_name' => 'Ahmadi',
        'phone_number' => '09123456789',
        'services' => [$this->service->id],
    ])->assertOk();

    $staff = Expert::firstWhere('phone_number', '09123456789');

    expect($staff)->not->toBeNull()
        ->and($staff->hasRole('expert'))->toBeTrue()
        ->and($staff->halls()->where('halls.id', $this->hall->id)->exists())->toBeTrue()
        ->and($staff->services()->where('services.id', $this->service->id)->exists())->toBeTrue();
});

test('expert can list hall staff', function () {
    $staff = Expert::factory()->create();
    $this->hall->experts()->attach($staff->id);

    $this->getJson("/expert/staff/{$this->hall->id}?filters=[]&sorting=[]")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('expert can view a staff member', function () {
    $staff = Expert::factory()->create();
    $staff->services()->attach($this->service->id);

    $this->getJson("/expert/staff/{$this->hall->id}/{$staff->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $staff->id);
});

test('expert can update a staff member', function () {
    $staff = Expert::factory()->create();

    $this->postJson("/expert/staff/{$this->hall->id}/{$staff->id}", [
        'first_name' => 'Updated',
        'last_name' => 'Staff',
        'phone_number' => '09129998877',
    ])->assertOk();

    $this->assertDatabaseHas('experts', ['id' => $staff->id, 'first_name' => 'Updated']);
});

test('expert can remove a staff member', function () {
    $staff = Expert::factory()->create();
    $this->hall->experts()->attach($staff->id);
    $staff->services()->attach($this->service->id);

    $this->deleteJson("/expert/staff/{$this->hall->id}/{$staff->id}")->assertOk();

    $this->assertDatabaseMissing('experts', ['id' => $staff->id]);
});
