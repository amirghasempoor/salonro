<?php

namespace App\Expert\Policies;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;

class HallPolicy
{
    public function view(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function update(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function delete(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function serviceView(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function serviceStore(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    public function serviceShow(Expert $expert, Hall $hall, HallService $hallService): bool
    {
        return $expert->id === $hall->owner_id
            && $hallService->hall_id === $hall->id;
    }

    public function serviceUpdate(Expert $expert, Hall $hall, HallService $hallService): bool
    {
        return $expert->id === $hall->owner_id
            && $hallService->hall_id === $hall->id;
    }

    public function serviceDelete(Expert $expert, Hall $hall, HallService $hallService): bool
    {
        return $expert->id === $hall->owner_id
            && $hallService->hall_id === $hall->id;
    }
}
