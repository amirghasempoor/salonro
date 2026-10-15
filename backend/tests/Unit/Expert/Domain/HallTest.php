<?php

use Expert\Domain\Entities\Hall;
use Expert\Domain\Exceptions\Reservation\ServiceNotPricedByExpertException;
use Tests\TestCase;

uses(TestCase::class);

function hall(): Hall
{
    return new Hall(7, 'Rose Salon', [
        1 => ['name' => 'Haircut', 'price' => 100, 'duration' => 30],
        2 => ['name' => 'Color', 'price' => 250, 'duration' => 90],
    ]);
}

test('it prices requested services from the hall and sums the base total', function () {
    $priced = hall()->priceServices([1, 2]);

    expect($priced['baseTotal'])->toBe(350)
        ->and($priced['lines'])->toBe([
            1 => ['service_name' => 'Haircut', 'price' => 100, 'duration' => 30],
            2 => ['service_name' => 'Color', 'price' => 250, 'duration' => 90],
        ]);
});

test('a duplicated service is priced once', function () {
    expect(hall()->priceServices([1, 1])['baseTotal'])->toBe(100);
});

test('a service the expert has not priced is rejected', function () {
    hall()->priceServices([1, 99]);
})->throws(ServiceNotPricedByExpertException::class);

test('the rejection carries the localized message', function () {
    try {
        hall()->priceServices([99]);
        $this->fail('expected an exception');
    } catch (ServiceNotPricedByExpertException $e) {
        expect($e->getMessage())->toBe(__('messages.service_not_priced_by_expert'))
            ->and($e->getMessage())->not->toBe('messages.service_not_priced_by_expert');
    }
});
