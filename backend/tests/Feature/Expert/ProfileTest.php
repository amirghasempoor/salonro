<?php

use App\Models\Expert;
use App\Models\Hall;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    seedRoles();

    $this->expert = Expert::factory()->create(['password' => Hash::make(validPassword())]);
    $this->actingAs($this->expert, 'expert');
});

test('expert can fetch profile info', function () {
    $this->getJson('/expert/profile/info')
        ->assertOk()
        ->assertJsonPath('data.first_name', $this->expert->first_name)
        ->assertJsonPath('data.phone_number', $this->expert->phone_number);
});

test('expert can update profile', function () {
    $this->postJson('/expert/profile/update', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'password' => 'newpassword123',
        'bio' => 'Senior barber',
    ])->assertOk();

    $this->expert->refresh();

    expect($this->expert->first_name)->toBe('New')
        ->and($this->expert->bio)->toBe('Senior barber')
        ->and($this->expert->is_verified)->toBe(1)
        ->and(Hash::check('newpassword123', $this->expert->password))->toBeTrue();
});

test('expert profile update fails with a weak password', function () {
    $this->postJson('/expert/profile/update', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'password' => 'password',
        'bio' => 'Senior barber',
    ])->assertStatus(422);
});

test('expert can update profile with an avatar', function () {
    Storage::fake('public');

    $this->post('/expert/profile/update', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'password' => 'newpassword123',
        'avatar' => UploadedFile::fake()->image('avatar.png'),
    ])->assertOk();

    $this->expert->refresh();
    expect($this->expert->avatar)->not->toBeNull();
});

test('expert can change password', function () {
    $this->postJson('/expert/profile/change_password', [
        'current_password' => validPassword(),
        'new_password' => 'newpassword123',
    ])->assertOk();

    $this->expert->refresh();
    expect(Hash::check('newpassword123', $this->expert->password))->toBeTrue();
});

test('change password fails with a wrong current password', function () {
    $this->postJson('/expert/profile/change_password', [
        'current_password' => 'wrongpassword1',
        'new_password' => 'newpassword123',
    ])->assertStatus(422);
});

test('expert can change avatar', function () {
    Storage::fake('public');

    $this->post('/expert/profile/change_avatar', [
        'avatar' => UploadedFile::fake()->image('avatar.png'),
    ])->assertOk();

    $this->expert->refresh();
    expect($this->expert->avatar)->not->toBeNull();
});

test('expert can upload portfolio images', function () {
    Storage::fake('public');

    $this->post('/expert/profile/upload_portfolio', [
        'portfolio' => [
            ['image' => UploadedFile::fake()->image('work1.jpg'), 'title' => 'Haircut'],
            ['image' => UploadedFile::fake()->image('work2.jpg'), 'title' => 'Beard trim'],
        ],
    ])->assertOk();

    expect($this->expert->images()->count())->toBe(2);
});

test('expert can define working hours for a hall', function () {
    $hall = Hall::factory()->create();
    $this->expert->halls()->attach($hall->id, ['joined_at' => now()]);

    $this->postJson('/expert/profile/define_working_hour', [
        'hall_id' => $hall->id,
        'workingHours' => [
            ['day' => 'saturday', 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'sunday', 'from' => '10:00', 'to' => '16:00'],
        ],
    ])->assertOk();

    expect($this->expert->workingHoursAtHall($hall->id)->count())->toBe(2);
});

test('expert cannot define working hours for a hall they do not work in', function () {
    $hall = Hall::factory()->create();

    $this->postJson('/expert/profile/define_working_hour', [
        'hall_id' => $hall->id,
        'workingHours' => [
            ['day' => 'saturday', 'from' => '09:00', 'to' => '18:00'],
        ],
    ])->assertStatus(422);
});

test('expert can define role', function () {
    $this->postJson('/expert/profile/define_role', ['role' => 3])->assertOk();

    expect($this->expert->hasRole('manager'))->toBeTrue();
});
