<?php

namespace Expert\Domain\Repositories;

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

    /**
     * The expert's own working windows at the hall.
     *
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function workingHours(int $expertId, int $hallId): array;
}
