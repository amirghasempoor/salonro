<?php

namespace Expert\Application\Policies;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;

class MyServicePolicy
{
    public function show(Expert $expert, ExpertService $expertService): bool
    {
        return $this->owns($expert, $expertService);
    }

    public function update(Expert $expert, ExpertService $expertService): bool
    {
        return $this->owns($expert, $expertService);
    }

    public function delete(Expert $expert, ExpertService $expertService): bool
    {
        return $this->owns($expert, $expertService);
    }

    private function owns(Expert $expert, ExpertService $expertService): bool
    {
        return $expertService->serviceable_type === ExpertHall::class
            && $expertService->serviceable->expert_id === $expert->id;
    }
}
