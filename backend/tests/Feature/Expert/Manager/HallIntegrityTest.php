<?php

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use App\Models\Profession;
use App\Models\Reservation;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

// A hall must stay consistent: solo halls stay solo, the owner stays in, and
// nothing is removed from under customers who are booked.
beforeEach(function () {
    seedRoles();
    seedReservationStates();

    $this->owner = Expert::factory()->create();
    $this->owner->assignRole('manager');
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $this->ownerMembership = ExpertHall::factory()->create(['expert_id' => $this->owner->id, 'hall_id' => $this->hall->id]);

    $this->staff = Expert::factory()->create(['phone_number' => '09121112233']);
    $this->staffMembership = ExpertHall::factory()->create(['expert_id' => $this->staff->id, 'hall_id' => $this->hall->id]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');
});

function bookedAt($test, $expert, ?string $state = null): Reservation
{
    return Reservation::factory()->create([
        'expert_id' => $expert->id,
        'hall_id' => $test->hall->id,
        'state_id' => $state ?? ReservationStates::Reserve->value,
        'start_time' => now()->addDay(),
        'finish_time' => now()->addDay()->addHour(),
    ]);
}

test('a solo hall cannot create a job offer', function () {
    $this->hall->update(['is_solo' => true]);

    $this->postJson(route('expert.job_offers.store', [$this->hall->id]), [
        'profession_id' => Profession::factory()->create()->id,
        'description' => 'help wanted',
    ])->assertStatus(422)->assertJsonPath('message', __('messages.solo_hall_cannot_have_staff'));
});

test('an old offer cannot bring staff into a hall that became solo', function () {
    $application = JobOfferApplication::factory()->create();
    $hall = $application->jobOffer->hall;
    $hall->update(['is_solo' => true]);
    $manager = Expert::query()->find($hall->owner_id);
    $manager->assignRole('manager');
    Sanctum::actingAs($manager, ['*'], 'expert');

    $this->postJson(route('expert.job_offers.acceptApplication', [$application->id]))
        ->assertStatus(422)->assertJsonPath('message', __('messages.solo_hall_cannot_have_staff'));

    $this->assertDatabaseMissing('expert_hall', ['hall_id' => $hall->id, 'expert_id' => $application->expert_id]);
});

test('the owner cannot deactivate or remove herself', function () {
    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->owner->id]), ['is_active' => false])->assertForbidden();
    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->owner->id]))->assertForbidden();

    $this->assertDatabaseHas('expert_hall', ['id' => $this->ownerMembership->id, 'is_active' => true]);
});

test('adding someone who already works in the hall is a conflict', function () {
    $this->postJson(route('expert.staff.store', [$this->hall->id]), [
        'phone_number' => $this->staff->phone_number,
        'services' => [Service::factory()->create()->id],
    ])->assertStatus(409)->assertJsonPath('message', __('messages.expert_already_in_hall'));

    expect(ExpertHall::query()->where('hall_id', $this->hall->id)->where('expert_id', $this->staff->id)->count())->toBe(1);
});

test('a junk phone number is rejected when adding staff', function () {
    foreach (['abc', '123', '0912', '09123456789012', '+989121234567'] as $phone) {
        $this->postJson(route('expert.staff.store', [$this->hall->id]), ['phone_number' => $phone, 'services' => []])
            ->assertStatus(422)->assertJsonValidationErrors('phone_number');
    }
});

test('staff with upcoming reservations cannot be switched off or removed', function () {
    bookedAt($this, $this->staff);

    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]), ['is_active' => false])
        ->assertStatus(409)->assertJsonPath('message', __('messages.expert_has_upcoming_reservations'));
    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]))->assertStatus(409);

    $this->assertDatabaseHas('expert_hall', ['id' => $this->staffMembership->id, 'is_active' => true]);
});

test('staff whose reservations are cancelled can be removed', function () {
    bookedAt($this, $this->staff, (string) ReservationStates::Cancel->value);

    $this->deleteJson(route('expert.staff.destroy', [$this->hall->id, $this->staff->id]))->assertOk();
    $this->assertDatabaseMissing('expert_hall', ['id' => $this->staffMembership->id]);
});

test('a hall with upcoming reservations cannot be deleted', function () {
    bookedAt($this, $this->staff);

    $this->deleteJson(route('expert.hall.destroy', [$this->hall->id]))
        ->assertStatus(409)->assertJsonPath('message', __('messages.hall_has_upcoming_reservations'));

    $this->assertDatabaseHas('halls', ['id' => $this->hall->id]);
});

test('deleting a hall also removes its job offers and working hours', function () {
    $this->hall->workingHours()->create(['day' => 'sat', 'from' => '09:00', 'to' => '17:00']);
    $this->staffMembership->workingHours()->create(['day' => 'sat', 'from' => '09:00', 'to' => '12:00']);
    JobOffer::factory()->create(['hall_id' => $this->hall->id, 'expert_id' => $this->owner->id]);

    $this->deleteJson(route('expert.hall.destroy', [$this->hall->id]))->assertOk();

    $this->assertDatabaseMissing('halls', ['id' => $this->hall->id]);
    $this->assertDatabaseMissing('job_offers', ['hall_id' => $this->hall->id]);
    $this->assertDatabaseMissing('expert_hall', ['hall_id' => $this->hall->id]);
    $this->assertDatabaseCount('working_hours', 0);
});

test('a service already in the hall catalog cannot be added twice', function () {
    $service = Service::factory()->create();
    $payload = ['service_id' => $service->id, 'is_active' => true];

    $this->postJson(route('expert.services.store', [$this->hall->id]), $payload)->assertSuccessful();
    $this->postJson(route('expert.services.store', [$this->hall->id]), $payload)
        ->assertStatus(422)->assertJsonValidationErrors('service_id');

    $this->assertDatabaseCount('hall_service', 1);
});
