<?php

use App\Models\Expert;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    Sanctum::actingAs($this->expert, ['*'], 'expert');
});

test('expert should be authenticated to update the account', function () {
    $this->app['auth']->forgetGuards();

    $this->postJson(route('expert.profile.update'))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $this->app['auth']->forgetGuards();
    Sanctum::actingAs(User::factory()->create(), ['*'], 'user');

    $this->postJson(route('expert.profile.update'))->assertUnauthorized();
});

test('first name is required', function () {
    $response = $this->postJson(route('expert.profile.update'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $response = $this->postJson(route('expert.profile.update'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('avatar should be a valid image type', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'avatar' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.image', ['attribute' => 'avatar']),
    ]);
});

test('avatar should be one of the allowed mime types', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('avatar');
});

test('avatar should not be larger than 2048 kilobytes', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.max.file', ['attribute' => 'avatar', 'max' => 2048]),
    ]);
});

test('bio should be string', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'bio' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'bio' => __('validation.string', ['attribute' => 'bio']),
    ]);
});

test('bio should not exceed 255 characters', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'bio' => fake()->text(500),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'bio' => __('validation.max.string', ['attribute' => 'bio', 'max' => 255]),
    ]);
});

test('is active is required', function () {
    $response = $this->postJson(route('expert.profile.update'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('is_active');
});

test('is active should be boolean', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'is_active' => fake()->numerify('##'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'is_active' => __('validation.boolean', ['attribute' => 'is active']),
    ]);
});

test('expert can update the account', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'bio' => 'Senior stylist',
        'is_active' => true,
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('experts', [
        'id' => $this->expert->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'bio' => 'Senior stylist',
        'is_active' => true,
    ]);
});

test('expert can deactivate the account', function () {
    $this->expert->update(['is_active' => true]);

    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => $this->expert->first_name,
        'last_name' => $this->expert->last_name,
        'is_active' => false,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('experts', [
        'id' => $this->expert->id,
        'is_active' => false,
    ]);
});

test('omitting bio keeps the existing value', function () {
    $this->expert->update(['bio' => 'original bio']);

    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => $this->expert->first_name,
        'last_name' => $this->expert->last_name,
        'is_active' => true,
    ]);

    $response->assertOk();

    expect($this->expert->fresh()->bio)->toBe('original bio');
});

test('uploading an avatar stores it and replaces the previous one', function () {
    Storage::fake('public');

    $oldAvatar = UploadedFile::fake()->image('old.jpg')->store('experts/avatars', 'public');
    $this->expert->update(['avatar' => $oldAvatar]);

    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => $this->expert->first_name,
        'last_name' => $this->expert->last_name,
        'is_active' => true,
        'avatar' => UploadedFile::fake()->image('new.jpg'),
    ]);

    $response->assertOk();

    $newAvatar = $this->expert->fresh()->getRawOriginal('avatar');

    expect($newAvatar)->not->toBe($oldAvatar)
        ->and($newAvatar)->not->toContain('/storage/');
    Storage::disk('public')->assertExists($newAvatar);
    Storage::disk('public')->assertMissing($oldAvatar);
});

test('omitting the avatar leaves the underlying file in place', function () {
    Storage::fake('public');

    $existingAvatar = UploadedFile::fake()->image('keep.jpg')->store('experts/avatars', 'public');
    $this->expert->update(['avatar' => $existingAvatar]);

    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => $this->expert->first_name,
        'last_name' => $this->expert->last_name,
        'is_active' => true,
    ]);

    $response->assertOk();

    // update() with no new upload persists the accessor URL back into the column
    expect($this->expert->fresh()->getRawOriginal('avatar'))
        ->toBe(Storage::disk('public')->url($existingAvatar));

    // the stored file itself is untouched
    Storage::disk('public')->assertExists($existingAvatar);
});
