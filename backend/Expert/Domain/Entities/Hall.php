<?php

namespace Expert\Domain\Entities;

use Expert\Domain\Exceptions\Reservation\ServiceNotPricedByExpertException;

/**
 * The services the assigned expert offers at this hall, each priced by her.
 * Prices and durations always come from her, never from client input, and
 * never from the hall.
 */
final readonly class Hall
{
    /**
     * @param  array<int, array{name: string, price: int, duration: int|null}>  $offeredServices  keyed by service id — the expert's own priced services at this hall
     */
    public function __construct(
        public int $id,
        public string $name,
        private array $offeredServices,
    ) {}

    /**
     * Price the requested services from the expert's own prices.
     *
     * @param  array<int, int>  $serviceIds
     * @return array{lines: array<int, array{service_name: string, price: int, duration: int|null}>, baseTotal: int}
     *
     * @throws ServiceNotPricedByExpertException when the expert has not priced a requested service
     */
    public function priceServices(array $serviceIds): array
    {
        $lines = [];
        $baseTotal = 0;

        foreach (array_unique($serviceIds) as $serviceId) {
            $service = $this->offeredServices[$serviceId] ?? null;

            throw_if($service === null, ServiceNotPricedByExpertException::forService($serviceId));

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
