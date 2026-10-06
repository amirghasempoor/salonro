<?php

use User\Domain\Entities\Hall;

function staff(int $id, array $serviceIds): array
{
    return ['id' => $id, 'first_name' => "Expert{$id}", 'last_name' => 'Test', 'avatar' => null, 'service_ids' => $serviceIds];
}

test('it returns the services it was given as-is', function () {
    $services = [
        ['id' => 1, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Cut', 'icon' => null, 'price' => 100, 'duration' => 30],
    ];

    $hall = new Hall(1, 'Rose Salon', $services, []);

    expect($hall->services())->toBe($services);
});

test('staff offering at least one requested service is included', function () {
    $hall = new Hall(1, 'Rose Salon', [], [staff(1, [10]), staff(2, [20])]);

    $result = $hall->staffOffering([10]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['id'])->toBe(1);
});

test('a staff member matching any of several requested services is included, not only one matching all', function () {
    $hall = new Hall(1, 'Rose Salon', [], [staff(1, [10]), staff(2, [20]), staff(3, [30])]);

    $result = $hall->staffOffering([10, 20]);

    expect(array_column($result, 'id'))->toBe([1, 2]);
});

test('a staff member offering none of the requested services is excluded', function () {
    $hall = new Hall(1, 'Rose Salon', [], [staff(1, [10])]);

    expect($hall->staffOffering([99]))->toBe([]);
});

test('an empty roster returns no staff', function () {
    $hall = new Hall(1, 'Rose Salon', [], []);

    expect($hall->staffOffering([10]))->toBe([]);
});
