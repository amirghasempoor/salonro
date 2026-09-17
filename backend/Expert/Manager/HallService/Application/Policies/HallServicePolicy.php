<?php

namespace Expert\Manager\HallService\Application\Policies;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;

class HallServicePolicy
{
    public function view(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function store(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function show(Expert $expert, HallService $hallService): bool
    {
        return $expert->id === $hallService->hall->owner_id;
    }

    public function update(Expert $expert, HallService $hallService): bool
    {
        return $expert->id === $hallService->hall->owner_id;
    }

    public function delete(Expert $expert, HallService $hallService): bool
    {
        return $expert->id === $hallService->hall->owner_id;
    }
}
