<?php

namespace Expert\Domain\Actions\Manager;

use App\Models\Hall;
use Expert\Domain\DTOs\Manager\DefineHallWorkingHoursDto;
use Expert\Domain\Exceptions\Manager\HallScheduleBelowStaffScheduleException;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Replace a hall's weekly schedule, but never below the hours the hall's own
 * staff are already scheduled to work — shrinking the hall's hours must not
 * silently strand an expert's existing working hours outside them.
 */
readonly class DefineHallWorkingHoursAction
{
    public function __construct(
        private ExpertHallRepositoryInterface $expertHallRepository,
    ) {}

    /**
     * @throws HallScheduleBelowStaffScheduleException when a staff member's existing working hours no longer fit the new schedule
     * @throws Throwable
     */
    public function execute(Hall $hall, DefineHallWorkingHoursDto $dto): void
    {
        $newSchedule = collect($dto->workingHours);

        foreach ($this->expertHallRepository->staffWorkingHours($hall->id) as $staffWindow) {
            $this->assertCoveredByNewSchedule($staffWindow, $newSchedule);
        }

        $this->expertHallRepository->replaceHallWorkingHours($hall, $dto->workingHours);
    }

    /**
     * @param  array{day: string, from: string, to: string}  $staffWindow
     * @param  Collection<int, array{day: string, from: string, to: string}>  $newSchedule
     *
     * @throws Throwable
     */
    private function assertCoveredByNewSchedule(array $staffWindow, Collection $newSchedule): void
    {
        $covered = $newSchedule
            ->where('day', $staffWindow['day'])
            ->contains(fn (array $window) => Carbon::parse($window['from'])->lte(Carbon::parse($staffWindow['from']))
                && Carbon::parse($window['to'])->gte(Carbon::parse($staffWindow['to'])));

        throw_unless($covered, HallScheduleBelowStaffScheduleException::forDay($staffWindow['day']));
    }
}
