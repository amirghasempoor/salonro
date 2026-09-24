<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Expert\Domain\Actions\Reservation\BookReservationAction;
use Expert\Domain\DTOs\Reservation\BookReservationDto;
use Expert\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use Expert\Domain\Exceptions\Reservation\ReservationConflictException;
use Expert\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;
use Illuminate\Support\Carbon;

beforeEach(function () {
    seedReservationStates();

    $this->hall = Hall::factory()->create();
    $this->expert = Expert::query()->find($this->hall->owner_id);
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 60,
    ]);

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->start = Carbon::now()->addDay()->setTime(10, 0)->toImmutable();
    $this->finish = $this->start->addHour();
});

function bookDto(object $test, array $overrides = []): BookReservationDto
{
    return new BookReservationDto(...array_merge([
        'hallId' => $test->hall->id,
        'expertId' => $test->expert->id,
        'phoneNumber' => '09120000000',
        'firstName' => 'Ali',
        'lastName' => 'Ahmadi',
        'serviceIds' => [$test->service->id],
        'start' => $test->start,
        'finish' => $test->finish,
    ], $overrides));
}

test('it books a reservation priced from the hall services', function () {
    $reservation = app(BookReservationAction::class)->execute(bookDto($this));

    expect($reservation->id)->not->toBeNull()
        ->and($reservation->baseTotal)->toBe(200000);

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'hall_id' => $this->hall->id,
        'hall_name' => $this->hall->name,
        'expert_id' => $this->expert->id,
        'expert_name' => $this->expert->full_name,
        'user_name' => 'Ali Ahmadi',
        'state_id' => ReservationStates::Reserve->value,
        'state_name' => ReservationStates::Reserve->label(),
        'total_price' => 200000,
        'discount_amount' => 0,
        'discount_id' => null,
    ]);

    $this->assertDatabaseHas('reservation_services', [
        'reservation_id' => $reservation->id,
        'service_id' => $this->service->id,
        'service_name' => $this->service->sub_cat_name,
        'price' => 200000,
        'duration' => 60,
    ]);
});

test('it creates the customer when the phone number is new', function () {
    app(BookReservationAction::class)->execute(bookDto($this));

    $this->assertDatabaseHas('users', [
        'phone_number' => '09120000000',
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
    ]);
});

test('it reuses an existing customer and keeps their stored name', function () {
    $user = User::factory()->create(['phone_number' => '09120000000', 'first_name' => 'Sara', 'last_name' => 'Karimi']);

    $reservation = app(BookReservationAction::class)->execute(bookDto($this));

    expect(User::query()->where('phone_number', '09120000000')->count())->toBe(1);
    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'user_id' => $user->id,
        'user_name' => 'Sara Karimi',
    ]);
});

test('it grants the best matching discount', function () {
    Discount::factory()->holiday()->percentage(10)->create([
        'hall_id' => $this->hall->id,
        'starts_at' => now()->subDays(2)->toDateString(),
        'ends_at' => now()->addDays(2)->toDateString(),
    ]);

    $reservation = app(BookReservationAction::class)->execute(bookDto($this));

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'discount_amount' => 20000,
        'total_price' => 180000,
    ]);
});

test('it rejects a slot outside the expert working hours and persists nothing', function () {
    $this->expertHall->workingHours()->delete();

    expect(fn () => app(BookReservationAction::class)->execute(bookDto($this)))
        ->toThrow(OutsideWorkingHoursException::class);

    expect(Reservation::query()->count())->toBe(0)
        ->and(User::query()->where('phone_number', '09120000000')->exists())->toBeFalse();
});

test('it rejects a slot overlapping the expert existing reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->start->subMinutes(30),
        'finish_time' => $this->start->addMinutes(30),
    ]);

    expect(fn () => app(BookReservationAction::class)->execute(bookDto($this)))
        ->toThrow(ReservationConflictException::class);

    expect(Reservation::query()->count())->toBe(1)
        ->and(User::query()->where('phone_number', '09120000000')->exists())->toBeFalse();
});

test('it rejects a slot overlapping the customer reservation at another hall', function () {
    $user = User::factory()->create(['phone_number' => '09120000000']);
    Reservation::factory()->create([
        'user_id' => $user->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->start->subMinutes(30),
        'finish_time' => $this->start->addMinutes(30),
    ]);

    app(BookReservationAction::class)->execute(bookDto($this));
})->throws(ReservationConflictException::class);

test('a cancelled reservation does not block the slot', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Cancel->value,
        'start_time' => $this->start,
        'finish_time' => $this->finish,
    ]);

    $reservation = app(BookReservationAction::class)->execute(bookDto($this));

    expect($reservation->id)->not->toBeNull();
});

test('it rejects a service the hall does not offer and persists nothing', function () {
    $other = Service::factory()->create();

    expect(fn () => app(BookReservationAction::class)->execute(bookDto($this, ['serviceIds' => [$other->id]])))
        ->toThrow(ServiceNotOfferedByHallException::class);

    expect(Reservation::query()->count())->toBe(0);
});
