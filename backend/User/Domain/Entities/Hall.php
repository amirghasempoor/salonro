<?php

namespace User\Domain\Entities;

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
}
