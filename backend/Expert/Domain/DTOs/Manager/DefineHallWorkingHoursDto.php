<?php

namespace Expert\Domain\DTOs\Manager;

final readonly class DefineHallWorkingHoursDto
{
    /**
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    public function __construct(
        public array $workingHours,
    ) {}
}
