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
    $this->payload = [
        'cat_id' => 1,
        'cat_name' => 'Hair',
        'sub_cat_id' => 101,
        'sub_cat_name' => 'Cut',
    ];
});

test('manager should be authenticated to create a service category', function () {
    $this->postJson(route('expert.service_categories.store'))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->manager, ['*'], 'user');
    $this->postJson(route('expert.service_categories.store'))->assertUnauthorized();
});

test('the category and sub category fields are required', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->postJson(route('expert.service_categories.store'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['cat_id', 'cat_name', 'sub_cat_id', 'sub_cat_name']);
});

test('the icon must be an image', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $this->postJson(route('expert.service_categories.store'), [
        ...$this->payload,
        'icon' => UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrorFor('icon');
});

test('manager can create a service category with an icon', function () {
    Sanctum::actingAs($this->manager, ['*'], 'expert');

    $response = $this->postJson(route('expert.service_categories.store'), [
        ...$this->payload,
        'icon' => UploadedFile::fake()->image('icon.png'),
    ]);

    $response->assertOk();
    $response->assertExactJson(['message' => __('messages.successful')]);

    $this->assertDatabaseHas('services', [
        'cat_id' => 1,
        'cat_name' => 'Hair',
        'sub_cat_id' => 101,
        'sub_cat_name' => 'Cut',
    ]);

    $icon = Service::query()->where('sub_cat_id', 101)->value('icon');
    Storage::disk('public')->assertExists($icon);
});
