<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;

test('the app timezone is Tehran', function () {
    expect(config('app.timezone'))->toBe('Asia/Tehran');
});

test('a reservation time keeps its wall-clock value and carries the Tehran offset instead of a Z', function () {
    $reservation = Reservation::factory()->create([
        'start_time' => '2026-11-16 10:00:00',
        'finish_time' => '2026-11-16 11:00:00',
    ]);

    $json = $reservation->fresh()->toArray();

    expect($json['start_time'])->toBe('2026-11-16T10:00:00+03:30')
        ->and($json['finish_time'])->toBe('2026-11-16T11:00:00+03:30');
});

test('the expert reservation list returns times with the offset', function () {
    $hall = Hall::factory()->create();
    Reservation::factory()->create([
        'hall_id' => $hall->id,
        'start_time' => '2026-11-16 10:00:00',
        'finish_time' => '2026-11-16 11:00:00',
    ]);

    Sanctum::actingAs(Expert::query()->find($hall->owner_id), ['*'], 'expert');
    $response = $this->getJson(route('expert.reservation.index', [
        'hall' => $hall->id,
        'start' => 0,
        'size' => 10,
    ]));

    $response->assertOk();
    $response->assertJsonPath('data.0.start_time', '2026-11-16T10:00:00+03:30');
});
