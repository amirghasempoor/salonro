<?php

use Expert\Domain\Entities\Hall;
use Expert\Domain\Exceptions\HallDoesNotOfferServiceException;
use Expert\Domain\Exceptions\Reservation\ServiceNotPricedByExpertException;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @param  list<int>  $catalog  the services the hall offers; the expert has priced only 1 and 2
 */
function hall(array $catalog = [1, 2]): Hall
{
    return new Hall(7, 'Rose Salon', [
        1 => ['name' => 'Haircut', 'price' => 100, 'duration' => 30],
        2 => ['name' => 'Color', 'price' => 250, 'duration' => 90],
    ], $catalog);
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

test('a service the hall offers but the expert has not priced is rejected', function () {
    hall([1, 2, 99])->priceServices([1, 99]);
})->throws(ServiceNotPricedByExpertException::class);

test('a service the hall does not offer is rejected, even when the expert still has a price for it', function () {
    hall([1])->priceServices([2]);
})->throws(HallDoesNotOfferServiceException::class);

test('the rejection carries the localized message', function () {
    try {
        hall([1, 2, 99])->priceServices([99]);
        $this->fail('expected an exception');
    } catch (ServiceNotPricedByExpertException $e) {
        expect($e->getMessage())->toBe(__('messages.service_not_priced_by_expert'))
            ->and($e->getMessage())->not->toBe('messages.service_not_priced_by_expert');
    }
});
