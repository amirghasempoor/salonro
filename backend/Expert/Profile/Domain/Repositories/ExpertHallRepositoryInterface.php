<?php

namespace Expert\Profile\Domain\Repositories;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\WorkingHour;

interface ExpertHallRepositoryInterface
{
    public function findActiveMembership(Expert $expert, int $hallId): ?ExpertHall;

    /**
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    public function addWorkingHours(ExpertHall $expertHall, array $workingHours): void;

    public function hallWorkingHourForDay(int $hallId, string $day): ?WorkingHour;
}
