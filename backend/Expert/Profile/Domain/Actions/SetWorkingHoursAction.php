<?php

namespace Expert\Profile\Domain\Actions;

use App\Models\Expert;
use Expert\Profile\Domain\DTOs\SetWorkingHoursDto;
use Expert\Profile\Domain\Exceptions\ExpertNotAssignedToHallException;
use Expert\Profile\Domain\Exceptions\WorkingHourOutsideHallScheduleException;
use Expert\Profile\Domain\Repositories\ExpertHallRepositoryInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Record an expert's working hours at a hall they are actively assigned to.
 */
readonly class SetWorkingHoursAction
{
    public function __construct(
        private ExpertHallRepositoryInterface $expertHallRepository,
    ) {}

    /**
     * @throws ExpertNotAssignedToHallException when the expert has no active membership at the hall
     * @throws WorkingHourOutsideHallScheduleException when a requested slot falls outside the hall's own hours for that day
     * @throws Throwable
     */
    public function execute(Expert $expert, int $hallId, SetWorkingHoursDto $dto): void
    {
        $expertHall = $this->expertHallRepository->findActiveMembership($expert, $hallId);

        throw_if($expertHall === null, ExpertNotAssignedToHallException::forHall($hallId));

        foreach ($dto->workingHours as $workingHour) {
            $this->assertWithinHallSchedule($hallId, $workingHour);
        }

        $this->expertHallRepository->addWorkingHours($expertHall, $dto->workingHours);
    }

    /**
     * @param  array{day: string, from: string, to: string}  $workingHour
     *
     * @throws Throwable
     */
    private function assertWithinHallSchedule(int $hallId, array $workingHour): void
    {
        $hallSchedule = $this->expertHallRepository->hallWorkingHourForDay($hallId, $workingHour['day']);

        $fits = $hallSchedule !== null
            && Carbon::parse($workingHour['from'])->gte(Carbon::parse($hallSchedule->from))
            && Carbon::parse($workingHour['to'])->lte(Carbon::parse($hallSchedule->to));

        throw_unless($fits, WorkingHourOutsideHallScheduleException::forDay($workingHour['day']));
    }
}
