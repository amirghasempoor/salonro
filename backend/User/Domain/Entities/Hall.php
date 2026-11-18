<?php

namespace User\Domain\Entities;

use User\Domain\Exceptions\Reservation\HallDoesNotOfferServiceException;
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
     * The services a customer can actually book here, each annotated with the
     * cheapest and priciest a staff member at this hall charges for it. A
     * service no staff member has priced yet is left out: it is in the hall's
     * catalog but cannot be booked, and it returns as soon as someone prices it.
     *
     * @return list<array{id: int, cat_id: int, cat_name: string, sub_cat_id: int, sub_cat_name: string, icon: ?string, price_min: int, price_max: int}>
     */
    public function services(): array
    {
        $priceRange = $this->priceRangeByService();

        return array_values(array_map(
            fn (array $service) => [
                ...$service,
                'price_min' => $priceRange[$service['id']]['min'],
                'price_max' => $priceRange[$service['id']]['max'],
            ],
            array_filter($this->services, fn (array $service) => isset($priceRange[$service['id']])),
        ));
    }

    /**
     * @return array<int, array{min: int, max: int}>
     */
    private function priceRangeByService(): array
    {
        $priceRange = [];

        foreach ($this->staff as $member) {
            foreach ($member['services'] as $service) {
                if (! $this->isBookable($service)) {
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
        $offering = [];

        foreach ($this->staff as $member) {
            // Only services she can really be booked for: priced by her (a hired
            // expert starts with none) and still offered by the hall.
            $priced = array_values(array_filter($member['services'], $this->isBookable(...)));

            if (array_intersect(array_column($priced, 'id'), $serviceIds) !== []) {
                $offering[] = [...$member, 'services' => $priced];
            }
        }

        return $offering;
    }

    /**
     * A service is priced once the expert has set both her price and her duration;
     * either one missing means she has not offered it yet.
     *
     * @param  array{id: int, sub_cat_name: string, price: mixed, duration: mixed}  $service
     */
    private function isPriced(array $service): bool
    {
        return $service['price'] !== null && $service['duration'] !== null;
    }

    /**
     * Whether the hall's own catalog still offers this service.
     */
    private function offers(int $serviceId): bool
    {
        return in_array($serviceId, array_column($this->services, 'id'), true);
    }

    /**
     * Bookable means both: the expert has priced it and the hall still offers it.
     *
     * @param  array{id: int, sub_cat_name: string, price: mixed, duration: mixed}  $service
     */
    private function isBookable(array $service): bool
    {
        return $this->isPriced($service) && $this->offers($service['id']);
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
            throw_unless($this->offers($serviceId), HallDoesNotOfferServiceException::forService($serviceId));

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
            if ($service['id'] === $serviceId && $this->isPriced($service)) {
                return $service;
            }
        }

        return null;
    }
}
