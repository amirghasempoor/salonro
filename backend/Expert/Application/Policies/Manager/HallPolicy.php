<?php

namespace Expert\Application\Policies\Manager;

use App\Models\Expert;
use App\Models\Hall;

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
}
