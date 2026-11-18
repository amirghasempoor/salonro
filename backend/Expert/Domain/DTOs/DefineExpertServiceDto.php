<?php

namespace Expert\Domain\DTOs;

final readonly class DefineExpertServiceDto
{
    /**
     * @param  array<int, array{service_id: int, price: int, duration: int}>  $services
     */
    public function __construct(
        public array $services,
    ) {}
}
