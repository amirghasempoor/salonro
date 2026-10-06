<?php

namespace User\Domain\Entities;

use User\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;

/**
 * A hall as seen by a customer: the services it offers, priced for that hall,
 * and the staff assigned there.
 */
final readonly class Hall
{
    /**
     * @param  list<array{id: int, cat_id: int, cat_name: string, sub_cat_id: int, sub_cat_name: string, icon: ?string, price: mixed, duration: mixed}>  $services  services the hall offers, priced for this hall
     * @param  list<array{id: int, first_name: string, last_name: string, avatar: ?string, service_ids: list<int>}>  $staff  staff assigned to the hall, with the services each of them provides here
     */
    public function __construct(
        public int $id,
        public string $name,
        private array $services,
        private array $staff,
    ) {}

    /**
     * @return list<array{id: int, cat_id: int, cat_name: string, sub_cat_id: int, sub_cat_name: string, icon: ?string, price: mixed, duration: mixed}>
     */
    public function services(): array
    {
        return $this->services;
    }

    /**
     * Staff who provide at least one of the requested services — a customer
     * choosing several services is shown anyone who can do part of the
     * booking, not only someone who can do all of it alone.
     *
     * @param  list<int>  $serviceIds
     * @return list<array{id: int, first_name: string, last_name: string, avatar: ?string, service_ids: list<int>}>
     */
    public function staffOffering(array $serviceIds): array
    {
        return array_values(array_filter(
            $this->staff,
            fn (array $member) => array_intersect($member['service_ids'], $serviceIds) !== [],
        ));
    }

    /**
     * Price the requested services from what the hall offers. Prices and
     * durations always come from here, never from client input.
     *
     * @param  list<int>  $serviceIds
     * @return array{lines: array<int, array{service_name: string, price: int, duration: int|null}>, baseTotal: int}
     *
     * @throws ServiceNotOfferedByHallException when a requested service is not offered by this hall
     */
    public function priceServices(array $serviceIds): array
    {
        $lines = [];
        $baseTotal = 0;

        foreach (array_unique($serviceIds) as $serviceId) {
            $service = $this->findOfferedService($serviceId);

            throw_if($service === null, ServiceNotOfferedByHallException::forService($serviceId));

            $lines[$serviceId] = [
                'service_name' => $service['sub_cat_name'],
                'price' => (int) $service['price'],
                'duration' => $service['duration'] === null ? null : (int) $service['duration'],
            ];
            $baseTotal += (int) $service['price'];
        }

        return ['lines' => $lines, 'baseTotal' => $baseTotal];
    }

    private function findOfferedService(int $serviceId): ?array
    {
        foreach ($this->services as $service) {
            if ($service['id'] === $serviceId) {
                return $service;
            }
        }

        return null;
    }
}
