<?php

namespace Expert\Domain\Repositories;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
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
     * The expert's own working windows in the hall.
     *
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function workingHours(int $expertId, int $hallId): array;

    /**
     * The working windows of every staff member currently assigned to the hall.
     *
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function staffWorkingHours(int $hallId): array;

    /**
     * Replace the hall's own weekly schedule.
     *
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    public function replaceHallWorkingHours(Hall $hall, array $workingHours): void;
}
