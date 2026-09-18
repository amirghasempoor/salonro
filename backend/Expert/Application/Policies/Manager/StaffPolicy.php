<?php

namespace Expert\Application\Policies\Manager;

use App\Models\Expert;
use App\Models\Hall;

class StaffPolicy
{
    public function view(Expert $manager, Hall $hall): bool
    {
        return $manager->id === $hall->owner_id;
    }

    public function store(Expert $manager, Hall $hall): bool
    {
        return $manager->id === $hall->owner_id;
    }

    public function update(Expert $manager, Hall $hall, Expert $expert): bool
    {
        return $manager->id === $hall->owner_id
            && $hall->experts()->where('experts.id', $expert->id)->exists();
    }

    public function delete(Expert $manager, Hall $hall, Expert $expert): bool
    {
        return $manager->id === $hall->owner_id
            && $hall->experts()->where('experts.id', $expert->id)->exists();
    }
}
