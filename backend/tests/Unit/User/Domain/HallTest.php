<?php

use User\Domain\Entities\Hall;

function staff(int $id, array $serviceIds): array
{
    return [
        'id' => $id,
        'first_name' => "Expert{$id}",
        'last_name' => 'Test',
        'avatar' => null,
        'services' => array_map(
            fn (int $serviceId) => ['id' => $serviceId, 'sub_cat_name' => "Service{$serviceId}", 'price' => 100, 'duration' => 30],
            $serviceIds,
        ),
    ];
}

/**
 * The hall's catalog entries for these service ids.
 *
 * @param  list<int>  $ids
 */
function catalogOf(array $ids): array
{
    return array_map(
        fn (int $id) => ['id' => $id, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => $id, 'sub_cat_name' => "Service{$id}", 'icon' => null],
        $ids,
    );
}

test('a service no staff member has priced yet is left out of the catalog', function () {
    $services = [
        ['id' => 1, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Cut', 'icon' => null],
        ['id' => 2, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 11, 'sub_cat_name' => 'Color', 'icon' => null],
    ];
    $staff = staff(1, [2]);

    $hall = new Hall(1, 'Rose Salon', $services, [$staff]);

    expect(array_column($hall->services(), 'id'))->toBe([2]);
});

test('a staff service with a null price does not count as priced', function () {
    $services = [
        ['id' => 1, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Cut', 'icon' => null],
    ];
    $unpriced = staff(1, [1]);
    $unpriced['services'][0]['price'] = null;

    $hall = new Hall(1, 'Rose Salon', $services, [$unpriced]);

    expect($hall->services())->toBe([]);
});

test('a hall with no staff has an empty catalog', function () {
    $services = [
        ['id' => 1, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Cut', 'icon' => null],
    ];

    expect((new Hall(1, 'Rose Salon', $services, []))->services())->toBe([]);
});

test('a service is annotated with the min and max price staff charge for it', function () {
    $services = [
        ['id' => 10, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Cut', 'icon' => null],
    ];
    $staffA = staff(1, [10]);
    $staffA['services'][0]['price'] = 100;
    $staffB = staff(2, [10]);
    $staffB['services'][0]['price'] = 300;

    $hall = new Hall(1, 'Rose Salon', $services, [$staffA, $staffB]);

    $result = $hall->services();

    expect($result[0]['price_min'])->toBe(100)
        ->and($result[0]['price_max'])->toBe(300);
});

test('a staff member whose only matching service has no price yet is not offered', function () {
    $unpriced = staff(1, [10]);
    $unpriced['services'][0]['price'] = null;

    $hall = new Hall(1, 'Rose Salon', catalogOf([10]), [$unpriced]);

    expect($hall->staffOffering([10]))->toBe([]);
});

test('a staff member whose only matching service has no duration yet is not offered', function () {
    $unpriced = staff(1, [10]);
    $unpriced['services'][0]['duration'] = null;

    $hall = new Hall(1, 'Rose Salon', catalogOf([10]), [$unpriced]);

    expect($hall->staffOffering([10]))->toBe([]);
});

test('a staff member is offered only with the services she has priced', function () {
    $member = staff(1, [10, 20]);
    $member['services'][1]['price'] = null;

    $result = (new Hall(1, 'Rose Salon', catalogOf([10, 20]), [$member]))->staffOffering([10, 20]);

    expect($result)->toHaveCount(1)
        ->and(array_column($result[0]['services'], 'id'))->toBe([10]);
});

test('a service the hall no longer offers is not bookable, even if the expert still has a price for it', function () {
    // Service 20 was removed from the hall's catalog; the expert still has it priced.
    $member = staff(1, [10, 20]);
    $hall = new Hall(1, 'Rose Salon', catalogOf([10]), [$member]);

    $result = $hall->staffOffering([10, 20]);

    expect(array_column($result[0]['services'], 'id'))->toBe([10])
        ->and($hall->staffOffering([20]))->toBe([])
        ->and(array_column($hall->services(), 'id'))->toBe([10]);
});

test('a service only an expert has priced but the hall does not offer is not in the price range', function () {
    $hall = new Hall(1, 'Rose Salon', catalogOf([10]), [staff(1, [10, 20])]);

    expect(array_column($hall->services(), 'id'))->toBe([10]);
});

test('a service with a price but no duration does not count towards the price range', function () {
    $services = [
        ['id' => 10, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Cut', 'icon' => null],
    ];
    $member = staff(1, [10]);
    $member['services'][0]['duration'] = null;

    expect((new Hall(1, 'Rose Salon', $services, [$member]))->services())->toBe([]);
});

test('staff offering at least one requested service is included', function () {
    $hall = new Hall(1, 'Rose Salon', catalogOf([10, 20]), [staff(1, [10]), staff(2, [20])]);

    $result = $hall->staffOffering([10]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['id'])->toBe(1);
});

test('a staff member matching any of several requested services is included, not only one matching all', function () {
    $hall = new Hall(1, 'Rose Salon', catalogOf([10, 20, 30]), [staff(1, [10]), staff(2, [20]), staff(3, [30])]);

    $result = $hall->staffOffering([10, 20]);

    expect(array_column($result, 'id'))->toBe([1, 2]);
});

test('a staff member offering none of the requested services is excluded', function () {
    $hall = new Hall(1, 'Rose Salon', catalogOf([10]), [staff(1, [10])]);

    expect($hall->staffOffering([99]))->toBe([]);
});

test('an empty roster returns no staff', function () {
    $hall = new Hall(1, 'Rose Salon', [], []);

    expect($hall->staffOffering([10]))->toBe([]);
});
