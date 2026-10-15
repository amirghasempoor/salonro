<?php

namespace User\Domain\Entities;

use User\Domain\Exceptions\Reservation\ServiceNotPricedByExpertException;

/**
 * A hall as seen by a customer: the services it offers, and the staff
 * assigned there, each priced individually by the staff member themself.
 */
final readonly class Hall
{
    /**
     * @param  list<array{id: int, cat_id: int, cat_name: string, sub_cat_id: int, sub_cat_name: string, icon: ?string}>  $services  the hall's own service catalog, for browsing — carries no price of its own, which is set per expert
     * @param  list<array{id: int, first_name: string, last_name: string, avatar: ?string, services: list<array{id: int, sub_cat_name: string, price: mixed, duration: mixed}>}>  $staff  staff assigned to the hall, each with the services they personally price
     */
    public function __construct(
        public int $id,
        public string $name,
        private array $services,
        private array $staff,
    ) {}

    /**
     * The hall's catalog, each service annotated with the cheapest and
     * priciest a staff member at this hall charges for it — null for a
     * service no staff member has priced yet.
     *
     * @return list<array{id: int, cat_id: int, cat_name: string, sub_cat_id: int, sub_cat_name: string, icon: ?string, price_min: ?int, price_max: ?int}>
     */
    public function services(): array
    {
        $priceRange = $this->priceRangeByService();

        return array_map(
            fn (array $service) => [
                ...$service,
                'price_min' => $priceRange[$service['id']]['min'] ?? null,
                'price_max' => $priceRange[$service['id']]['max'] ?? null,
            ],
            $this->services,
        );
    }

    /**
     * @return array<int, array{min: int, max: int}>
     */
    private function priceRangeByService(): array
    {
        $priceRange = [];

        foreach ($this->staff as $member) {
            foreach ($member['services'] as $service) {
                if ($service['price'] === null) {
                    continue;
                }

                $price = (int) $service['price'];
                $priceRange[$service['id']]['min'] = min($priceRange[$service['id']]['min'] ?? $price, $price);
                $priceRange[$service['id']]['max'] = max($priceRange[$service['id']]['max'] ?? $price, $price);
            }
        }

        return $priceRange;
    }

    /**
     * Staff who provide at least one of the requested services — a customer
     * choosing several services is shown anyone who can do part of the
     * booking, not only someone who can do all of it alone.
     *
     * @param  list<int>  $serviceIds
     * @return list<array{id: int, first_name: string, last_name: string, avatar: ?string, services: list<array{id: int, sub_cat_name: string, price: mixed, duration: mixed}>}>
     */
    public function staffOffering(array $serviceIds): array
    {
        return array_values(array_filter(
            $this->staff,
            fn (array $member) => array_intersect(array_column($member['services'], 'id'), $serviceIds) !== [],
        ));
    }

    /**
     * Price the requested services from the chosen expert's own prices.
     * Prices and durations always come from the expert, never from client
     * input — there is no hall-level fallback.
     *
     * @param  list<int>  $serviceIds
     * @return array{lines: array<int, array{service_name: string, price: int, duration: int|null}>, baseTotal: int}
     *
     * @throws ServiceNotPricedByExpertException when the expert has not priced a requested service
     */
    public function priceServices(array $serviceIds, int $expertId): array
    {
        $member = $this->findStaff($expertId);

        $lines = [];
        $baseTotal = 0;

        foreach (array_unique($serviceIds) as $serviceId) {
            $priced = $member === null ? null : $this->findPricedService($member['services'], $serviceId);

            throw_if($priced === null, ServiceNotPricedByExpertException::forService($serviceId));

            $lines[$serviceId] = [
                'service_name' => $priced['sub_cat_name'],
                'price' => (int) $priced['price'],
                'duration' => $priced['duration'] === null ? null : (int) $priced['duration'],
            ];
            $baseTotal += (int) $priced['price'];
        }

        return ['lines' => $lines, 'baseTotal' => $baseTotal];
    }

    private function findStaff(int $expertId): ?array
    {
        foreach ($this->staff as $member) {
            if ($member['id'] === $expertId) {
                return $member;
            }
        }

        return null;
    }

    /**
     * @param  list<array{id: int, sub_cat_name: string, price: mixed, duration: mixed}>  $services
     */
    private function findPricedService(array $services, int $serviceId): ?array
    {
        foreach ($services as $service) {
            if ($service['id'] === $serviceId) {
                return $service;
            }
        }

        return null;
    }
}
