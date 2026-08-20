<?php

use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->expert = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 150000,
        'duration' => 60,
    ]);
});

function bookReservation(array $overrides = []): array
{
    return array_merge([
        'expert_id' => test()->expert->id,
        'hall_id' => test()->hall->id,
        'services' => [['service_id' => test()->service->id]],
        'start_time' => '2026-08-20 10:00:00',
        'finish_time' => '2026-08-20 11:00:00',
    ], $overrides);
}

test('booking auto-applies the customer\'s manual discount', function () {
    Discount::factory()->manual($this->user)->fixed(50000)->create(['hall_id' => $this->hall->id]);

    $this->postJson('/reservations', bookReservation())->assertOk();

    $reservation = Reservation::query()->latest('id')->first();

    expect((int) $reservation->total_price)->toBe(100000)
        ->and($reservation->discount_amount)->toBe(50000);
});

test('booking auto-applies an in-window holiday discount', function () {
    Discount::factory()->holiday()->percentage(10)->create([
        'hall_id' => $this->hall->id,
        'starts_at' => '2026-08-19',
        'ends_at' => '2026-08-21',
    ]);

    $this->postJson('/reservations', bookReservation())->assertOk();

    $reservation = Reservation::query()->latest('id')->first();

    expect((int) $reservation->total_price)->toBe(135000)
        ->and($reservation->discount_amount)->toBe(15000);
});

test('booking applies only the larger when a manual and holiday both match', function () {
    Discount::factory()->holiday()->percentage(10)->create([
        'hall_id' => $this->hall->id,
        'starts_at' => '2026-08-19',
        'ends_at' => '2026-08-21',
    ]); // 15000
    Discount::factory()->manual($this->user)->fixed(60000)->create(['hall_id' => $this->hall->id]); // 60000

    $this->postJson('/reservations', bookReservation())->assertOk();

    $reservation = Reservation::query()->latest('id')->first();

    expect((int) $reservation->total_price)->toBe(90000)
        ->and($reservation->discount_amount)->toBe(60000);
});

test('booking stores the full server-side price when no discount applies', function () {
    $this->postJson('/reservations', bookReservation())->assertOk();

    $reservation = Reservation::query()->latest('id')->first();

    expect((int) $reservation->total_price)->toBe(150000)
        ->and($reservation->discount_amount)->toBe(0);
});

test('booking ignores any client-supplied price and uses hall_service prices', function () {
    $this->postJson('/reservations', bookReservation([
        'services' => [['service_id' => $this->service->id, 'price' => 1]],
        'total_price' => 1,
    ]))->assertOk();

    $this->assertDatabaseHas('reservation_services', [
        'service_id' => $this->service->id,
        'price' => 150000,
    ]);

    $reservation = Reservation::query()->latest('id')->first();
    expect((int) $reservation->total_price)->toBe(150000);
});

test('a manual discount increments used_count and stops applying once used up', function () {
    $discount = Discount::factory()->manual($this->user)->fixed(50000)->create([
        'hall_id' => $this->hall->id,
        'usage_limit' => 1,
    ]);

    $this->postJson('/reservations', bookReservation())->assertOk();

    expect($discount->fresh()->used_count)->toBe(1);

    $first = Reservation::query()->latest('id')->first();
    expect((int) $first->total_price)->toBe(100000)
        ->and($first->discount_amount)->toBe(50000);

    $this->postJson('/reservations', bookReservation())->assertOk();

    $second = Reservation::query()->latest('id')->first();
    expect((int) $second->total_price)->toBe(150000)
        ->and($second->discount_amount)->toBe(0);
    expect($discount->fresh()->used_count)->toBe(1);
});

test('booking a service the hall does not offer fails', function () {
    $other = Service::factory()->create();

    $this->postJson('/reservations', bookReservation([
        'services' => [['service_id' => $other->id]],
    ]))->assertStatus(422);
});
