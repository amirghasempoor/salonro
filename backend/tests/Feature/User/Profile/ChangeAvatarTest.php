<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create(['avatar' => null]);
    Sanctum::actingAs($this->user, ['*'], 'user');
});

test('user should be authenticated to change the avatar', function () {
    $this->app['auth']->forgetGuards();
    Sanctum::actingAs(User::factory()->create(), ['*'], 'expert');

    $this->postJson(route('user.profile.changeAvatar'))->assertUnauthorized();
});

test('avatar is required', function () {
    $response = $this->postJson(route('user.profile.changeAvatar'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('avatar');
});

test('avatar should be an image', function () {
    $response = $this->postJson(route('user.profile.changeAvatar'), [
        'avatar' => fake()->word(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.image', ['attribute' => 'avatar']),
    ]);
});

test('avatar should be one of the allowed mime types', function () {
    $response = $this->postJson(route('user.profile.changeAvatar'), [
        'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('avatar');
});

test('avatar should not be larger than 2048 kilobytes', function () {
    $response = $this->postJson(route('user.profile.changeAvatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.max.file', ['attribute' => 'avatar', 'max' => 2048]),
    ]);
});

test('user can change the avatar', function () {
    $response = $this->postJson(route('user.profile.changeAvatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $avatar = $this->user->fresh()->getRawOriginal('avatar');

    expect($avatar)->not->toBeNull()
        ->and($avatar)->not->toContain('/storage/');
    Storage::disk('public')->assertExists($avatar);
});

test('changing the avatar removes the previous one', function () {
    $oldAvatar = UploadedFile::fake()->image('old.jpg')->store('users/avatars', 'public');
    $this->user->update(['avatar' => $oldAvatar]);

    Storage::disk('public')->assertExists($oldAvatar);

    $response = $this->postJson(route('user.profile.changeAvatar'), [
        'avatar' => UploadedFile::fake()->image('new.jpg'),
    ]);

    $response->assertOk();

    $newAvatar = $this->user->fresh()->getRawOriginal('avatar');

    expect($newAvatar)->not->toBe($oldAvatar)
        ->and($newAvatar)->not->toContain('/storage/');
    Storage::disk('public')->assertExists($newAvatar);
    Storage::disk('public')->assertMissing($oldAvatar);
});
