<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    Storage::fake('public');
    $admin = Expert::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin, ['*'], 'expert');

    $this->payload = ['cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 101, 'sub_cat_name' => 'Cut'];
});

test('a service can be created without an icon', function () {
    $this->postJson(route('expert.service_categories.store'), $this->payload)->assertOk();

    $this->assertDatabaseHas('services', ['sub_cat_id' => 101, 'icon' => null]);
});

test('updating without a new icon keeps the existing one', function () {
    $service = Service::factory()->create(['icon' => 'categories/existing.png']);

    $this->postJson(route('expert.service_categories.update', $service->id), [
        ...$this->payload,
        'sub_cat_name' => 'Renamed',
    ])->assertOk();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'sub_cat_name' => 'Renamed',
        'icon' => 'categories/existing.png',
    ]);
});

test('a service that a hall offers cannot be deleted', function () {
    $service = Service::factory()->create();
    HallService::factory()->create(['service_id' => $service->id]);

    $this->deleteJson(route('expert.service_categories.destroy', $service->id))
        ->assertStatus(409)
        ->assertJsonPath('type', 'conflict')
        ->assertJsonPath('message', __('messages.service_in_use'));

    $this->assertDatabaseHas('services', ['id' => $service->id]);
});

test('a service an expert has priced cannot be deleted', function () {
    $service = Service::factory()->create();
    ExpertHall::factory()->create()->services()->attach($service->id, ['price' => 1000, 'duration' => 30]);

    $this->deleteJson(route('expert.service_categories.destroy', $service->id))->assertStatus(409);

    $this->assertDatabaseHas('services', ['id' => $service->id]);
});

test('a service on an existing reservation cannot be deleted', function () {
    $service = Service::factory()->create();
    $reservation = Reservation::factory()->create();
    DB::table('reservation_services')->insert([
        'reservation_id' => $reservation->id,
        'service_id' => $service->id,
        'service_name' => $service->sub_cat_name,
        'price' => 1000,
        'duration' => 30,
    ]);

    $this->deleteJson(route('expert.service_categories.destroy', $service->id))->assertStatus(409);

    $this->assertDatabaseHas('services', ['id' => $service->id]);
    $this->assertDatabaseCount('reservation_services', 1);
});

test('an unused service can still be deleted', function () {
    $service = Service::factory()->create();

    $this->deleteJson(route('expert.service_categories.destroy', $service->id))->assertOk();

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});
