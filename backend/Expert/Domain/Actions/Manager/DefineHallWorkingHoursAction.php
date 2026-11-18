<?php

namespace Expert\Domain\Actions\Manager;

use App\Models\Hall;
use Expert\Domain\DTOs\Manager\DefineHallWorkingHoursDto;
use Expert\Domain\Exceptions\Manager\HallScheduleBelowStaffScheduleException;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        // A solo hall has no staff, and the owner's own hours are replaced below to
        // match, so there is nothing to protect from being stranded.
        if (! $hall->is_solo) {
            foreach ($this->expertHallRepository->staffWorkingHours($hall->id) as $staffWindow) {
                $this->assertCoveredByNewSchedule($staffWindow, $newSchedule);
            }
        }

        DB::transaction(function () use ($hall, $dto) {
            $this->expertHallRepository->replaceHallWorkingHours($hall, $dto->workingHours);

            if ($hall->is_solo) {
                $this->mirrorOntoOwner($hall, $dto->workingHours);
            }
        });
    }

    /**
     * A solo hall is its owner's own practice, so the hall's hours are the owner's
     * hours. Bookings are checked against the expert's personal hours, which a solo
     * owner has no other screen to set.
     *
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    private function mirrorOntoOwner(Hall $hall, array $workingHours): void
    {
        $membership = $this->expertHallRepository->findActiveMembership($hall->owner, $hall->id);

        if ($membership !== null) {
            $this->expertHallRepository->replaceWorkingHours($membership, $workingHours);
        }
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
