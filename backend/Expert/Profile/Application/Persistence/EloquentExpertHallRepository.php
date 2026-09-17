<?php

namespace Expert\Profile\Application\Persistence;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\WorkingHour;
use Expert\Profile\Domain\Repositories\ExpertHallRepositoryInterface;

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
    public function addWorkingHours(ExpertHall $expertHall, array $workingHours): void
    {
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
}
