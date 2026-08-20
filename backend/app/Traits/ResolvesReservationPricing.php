<?php

namespace App\Traits;

use App\Models\HallService;

trait ResolvesReservationPricing
{
    /**
     * Resolve a hall's offered services into priced pivot rows plus the base total.
     *
     * Prices and durations come from hall_service (server-authoritative, never the
     * client), and the service label from the related service. Throws when a
     * requested service is not offered by the hall.
     *
     * @param  array<int, array<string, mixed>>  $services
     * @return array{pivot: array<int|string, array<string, mixed>>, baseTotal: int}
     */
    protected function priceReservationServices(int $hallId, array $services): array
    {
        $serviceIds = collect($services)->pluck('service_id')->unique()->values();

        $hallServices = HallService::query()
            ->where('hall_id', $hallId)
            ->whereIn('service_id', $serviceIds)
            ->with('service')
            ->get()
            ->keyBy('service_id');

        $pivot = [];
        $baseTotal = 0;

        foreach ($serviceIds as $serviceId) {
            $hallService = $hallServices->get($serviceId);

            if ($hallService === null) {
                throw new \RuntimeException('One or more selected services are not offered by this hall.');
            }

            $pivot[$serviceId] = [
                'service_name' => $hallService->service->sub_cat_name,
                'price' => $hallService->price,
                'duration' => $hallService->duration,
            ];

            $baseTotal += (int) $hallService->price;
        }

        return ['pivot' => $pivot, 'baseTotal' => $baseTotal];
    }
}
