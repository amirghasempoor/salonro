<?php

use App\Models\Otp as OtpModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Shared\Facades\Otp\Otp;
use Shared\Facades\Sms\Sms;

const PHONE = '09120000001';

beforeEach(function () {
    // The real SMS provider must never be called from a test.
    Sms::shouldReceive('send')->andReturnNull();
    RateLimiter::clear('otp-guesses:'.PHONE);
});

function makeOtp(array $overrides = []): OtpModel
{
    return OtpModel::query()->create([
        'phone_number' => PHONE,
        'verification_code' => 1234,
        'used' => 0,
        'expired_at' => now()->addMinutes(2),
        ...$overrides,
    ]);
}

test('a live code logs in on both apps', function (string $route) {
    makeOtp();

    $this->postJson(route($route), ['phone_number' => PHONE, 'verification_code' => '1234'])->assertOk();
})->with(['expert.auth.loginWithOtp', 'user.auth.loginWithOtp']);

test('an expired code is rejected on both apps', function (string $route) {
    makeOtp(['expired_at' => now()->subSecond(), 'created_at' => now()->subHours(5)]);

    $this->postJson(route($route), ['phone_number' => PHONE, 'verification_code' => '1234'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.incorrect_otp'));
})->with(['expert.auth.loginWithOtp', 'user.auth.loginWithOtp']);

test('a code can only be used once', function () {
    makeOtp();

    $this->postJson(route('user.auth.loginWithOtp'), ['phone_number' => PHONE, 'verification_code' => '1234'])->assertOk();
    $this->postJson(route('user.auth.loginWithOtp'), ['phone_number' => PHONE, 'verification_code' => '1234'])->assertStatus(422);
});

test('sending a new code makes the previous one stop working', function () {
    app(Otp::class)->generate(PHONE, 'code:');
    $first = OtpModel::query()->where('phone_number', PHONE)->latest('id')->first();

    app(Otp::class)->generate(PHONE, 'code:');

    expect(app(Otp::class)->verify(PHONE, $first->verification_code))->toBeFalse()
        ->and(OtpModel::query()->where('phone_number', PHONE)->where('used', 0)->count())->toBe(1);
});

test('a generated code has four digits and expires in a few minutes', function () {
    app(Otp::class)->generate(PHONE, 'code:');

    $otp = OtpModel::query()->where('phone_number', PHONE)->firstOrFail();

    $expiresAt = Carbon::parse($otp->expired_at);

    expect($otp->verification_code)->toBeGreaterThanOrEqual(1000)->toBeLessThanOrEqual(9999)
        ->and($expiresAt->isFuture())->toBeTrue()
        ->and($expiresAt->lessThanOrEqualTo(now()->addMinutes(5)->addSecond()))->toBeTrue();
});

test('five wrong guesses lock the number even for the right code', function () {
    makeOtp();

    foreach (range(1, 5) as $attempt) {
        expect(app(Otp::class)->verify(PHONE, '0000'))->toBeFalse();
    }

    expect(app(Otp::class)->verify(PHONE, '1234'))->toBeFalse();
});

test('fewer than five wrong guesses do not lock the number', function () {
    makeOtp();

    foreach (range(1, 4) as $attempt) {
        app(Otp::class)->verify(PHONE, '0000');
    }

    expect(app(Otp::class)->verify(PHONE, '1234'))->toBeTrue();
});

test('guesses against one number do not lock another', function () {
    makeOtp();
    foreach (range(1, 5) as $attempt) {
        app(Otp::class)->verify('09120000002', '0000');
    }

    expect(app(Otp::class)->verify(PHONE, '1234'))->toBeTrue();
});

test('a correct code resets the wrong guess counter', function () {
    makeOtp();
    foreach (range(1, 4) as $attempt) {
        app(Otp::class)->verify(PHONE, '0000');
    }
    app(Otp::class)->verify(PHONE, '1234');

    foreach (range(1, 4) as $attempt) {
        app(Otp::class)->verify(PHONE, '0000');
    }

    expect(app(Otp::class)->verify(PHONE, '1234'))->toBeTrue();
});
