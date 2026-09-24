<?php

use App\Models\Expert;
use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    Storage::fake('public');
    $this->manager = Expert::factory()->create();
    $this->manager->assignRole('manager');
    $this->service = Service::factory()->create();
    $this->payload = [
        'cat_id' => 5,
        'cat_name' => 'Skin',
        'sub_cat_id' => 501,
        'sub_cat_name' => 'Facial',
    ];
});

test('manager should be authenticated to update a service category', function () {
    $this->postJson(route('expert.service_categories.update', $this->service->id))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->manager, ['*'], 'user');
    $this->postJson(route('expert.service_categories.update', $this->service->id))->assertUnauthorized();
});

test('the category and sub category fields are required', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->postJson(route('expert.service_categories.update', $this->service->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['cat_id', 'cat_name', 'sub_cat_id', 'sub_cat_name']);
});

test('manager can update a service category with a new icon', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->postJson(route('expert.service_categories.update', $this->service->id), [
        ...$this->payload,
        'icon' => UploadedFile::fake()->image('icon.png'),
    ])->assertOk();

    $this->assertDatabaseHas('services', ['id' => $this->service->id, ...$this->payload]);
    Storage::disk('public')->assertExists($this->service->fresh()->icon);
});
