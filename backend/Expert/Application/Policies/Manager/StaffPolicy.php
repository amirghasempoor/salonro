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

    /**
     * A manager may read the details of an expert who works in a hall she owns,
     * and nobody else's.
     */
    public function show(Expert $manager, Hall $hall, Expert $expert): bool
    {
        return $manager->id === $hall->owner_id
            && $hall->experts()->where('experts.id', $expert->id)->exists();
    }

    /**
     * The owner can switch staff off, but never herself: a hall without its
     * owner among the active members cannot be managed or booked.
     */
    public function toggleActivation(Expert $manager, Hall $hall, Expert $expert): bool
    {
        return $manager->id === $hall->owner_id
            && $expert->id !== $hall->owner_id
            && $hall->experts()->where('experts.id', $expert->id)->exists();
    }

    /**
     * The owner can remove staff, but never herself — she deletes the hall instead.
     */
    public function delete(Expert $manager, Hall $hall, Expert $expert): bool
    {
        return $manager->id === $hall->owner_id
            && $expert->id !== $hall->owner_id
            && $hall->experts()->where('experts.id', $expert->id)->exists();
    }
}
