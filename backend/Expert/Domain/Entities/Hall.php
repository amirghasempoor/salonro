<?php

namespace Expert\Domain\Entities;

use Expert\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;

/**
 * A hall and the services it offers. Prices and durations always come from
 * here, never from client input.
 */
final readonly class Hall
{
    /**
     * @param  array<int, array{name: string, price: int, duration: int|null}>  $offeredServices  keyed by service id
     */
    public function __construct(
        public int $id,
        public string $name,
        private array $offeredServices,
    ) {}

    /**
     * Price the requested services from what the hall offers.
     *
     * @param  array<int, int>  $serviceIds
     * @return array{lines: array<int, array{service_name: string, price: int, duration: int|null}>, baseTotal: int}
     *
     * @throws ServiceNotOfferedByHallException when a requested service is not offered by this hall
     */
    public function priceServices(array $serviceIds): array
    {
        $lines = [];
        $baseTotal = 0;

        foreach (array_unique($serviceIds) as $serviceId) {
            $service = $this->offeredServices[$serviceId] ?? null;

            throw_if($service === null, ServiceNotOfferedByHallException::forService($serviceId));

            $lines[$serviceId] = [
                'service_name' => $service['name'],
                'price' => $service['price'],
                'duration' => $service['duration'],
            ];

            $baseTotal += $service['price'];
        }

        return ['lines' => $lines, 'baseTotal' => $baseTotal];
    }
}
