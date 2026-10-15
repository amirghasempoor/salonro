<?php

namespace Expert\Application\Persistence;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\WorkingHour;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;

class EloquentExpertHallRepository implements ExpertHallRepositoryInterface
{
    public function findActiveMembership(Expert $expert, int $hallId): ?ExpertHall
    {
        return $expert->expertHalls()
            ->where('hall_id', '=', $hallId)
            ->where('is_active', '=', true)
            ->first();
    }

    /**
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    public function replaceWorkingHours(ExpertHall $expertHall, array $workingHours): void
    {
        $expertHall->workingHours()->delete();

        foreach ($workingHours as $workingHour) {
            $expertHall->workingHours()->create($workingHour);
        }
    }

    public function hallWorkingHourForDay(int $hallId, string $day): ?WorkingHour
    {
        return WorkingHour::query()
            ->where('hourable_type', '=', Hall::class)
            ->where('hourable_id', '=', $hallId)
            ->where('day', '=', $day)
            ->first();
    }

    /**
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function workingHours(int $expertId, int $hallId): array
    {
        return Expert::query()->findOrFail($expertId)
            ->workingHoursAtHall($hallId)
            ->get(['day', 'from', 'to'])
            ->map(fn (WorkingHour $window) => [
                'day' => $window->day,
                'from' => $window->from,
                'to' => $window->to,
            ])
            ->all();
    }

    /**
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function staffWorkingHours(int $hallId): array
    {
        return WorkingHour::query()
            ->where('hourable_type', '=', ExpertHall::class)
            ->whereIn('hourable_id', ExpertHall::query()->where('hall_id', '=', $hallId)->select('id'))
            ->get(['day', 'from', 'to'])
            ->map(fn (WorkingHour $window) => [
                'day' => $window->day,
                'from' => $window->from,
                'to' => $window->to,
            ])
            ->all();
    }

    /**
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    public function replaceHallWorkingHours(Hall $hall, array $workingHours): void
    {
        $hall->workingHours()->delete();

        foreach ($workingHours as $workingHour) {
            $hall->workingHours()->create($workingHour);
        }
    }

    public function hallOffersService(int $hallId, int $serviceId): bool
    {
        return HallService::query()
            ->where('hall_id', '=', $hallId)
            ->where('service_id', '=', $serviceId)
            ->where('is_active', '=', true)
            ->exists();
    }

    public function defineService(ExpertHall $expertHall, int $serviceId, float $price, float $duration): ExpertService
    {
        return ExpertService::query()->updateOrCreate(
            [
                'serviceable_type' => ExpertHall::class,
                'serviceable_id' => $expertHall->id,
                'service_id' => $serviceId,
            ],
            [
                'price' => $price,
                'duration' => $duration,
            ],
        );
    }
}
