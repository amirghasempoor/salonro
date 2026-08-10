<?php

use App\Models\Expert;
use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');
});

test('expert can create a service category', function () {
    Storage::fake('public');

    $this->post('/expert/service_categories', [
        'cat_id' => 1,
        'cat_name' => 'Hair',
        'sub_cat_id' => 101,
        'sub_cat_name' => 'Haircut',
        'icon' => UploadedFile::fake()->image('icon.png'),
    ])->assertOk();

    $this->assertDatabaseHas('services', ['cat_id' => 1, 'sub_cat_name' => 'Haircut']);
});

test('expert can list service categories', function () {
    Service::factory()->count(3)->create();

    $this->getJson('/expert/service_categories?filters=[]&sorting=[]')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('expert can get the grouped service category list', function () {
    Service::factory()->create(['cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 101, 'sub_cat_name' => 'Haircut']);
    Service::factory()->create(['cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 102, 'sub_cat_name' => 'Shave']);

    $this->getJson('/expert/service_categories/list')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.cat_id', 1)
        ->assertJsonCount(2, '0.templates');
});

test('expert can view a service category', function () {
    $service = Service::factory()->create();

    $this->getJson("/expert/service_categories/{$service->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $service->id);
});

test('expert can update a service category', function () {
    Storage::fake('public');
    $service = Service::factory()->create();

    $this->post("/expert/service_categories/{$service->id}", [
        'cat_id' => 2,
        'cat_name' => 'Skin',
        'sub_cat_id' => 201,
        'sub_cat_name' => 'Facial',
        'icon' => UploadedFile::fake()->image('icon2.png'),
    ])->assertOk();

    $this->assertDatabaseHas('services', ['id' => $service->id, 'sub_cat_name' => 'Facial']);
});

test('expert can delete a service category', function () {
    $service = Service::factory()->create();

    $this->deleteJson("/expert/service_categories/{$service->id}")->assertOk();

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});
