<?php

namespace User\Domain\Repositories;

use User\Domain\Entities\Hall;

interface HallRepositoryInterface
{
    public function find(int $hallId): Hall;
}
