<?php

namespace Expert\Profile\Domain\DTOs;

final readonly class SetWorkingHoursDto
{
    /**
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    public function __construct(
        public array $workingHours,
    ) {}
}
