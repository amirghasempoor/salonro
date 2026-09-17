<?php

namespace Expert\Manager\Staff;

use Expert\Manager\Staff\Application\Policies\StaffPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class StaffServiceProvider extends ServiceProvider
{
    /**
     * Gate abilities are registered explicitly with a module-prefixed name (not via
     * Gate::policy()) so this module's authorization stays self-contained and can't
     * collide with another module's abilities for the same Hall/Expert models.
     */
    public function boot(): void
    {
        Gate::define('staff.view', [StaffPolicy::class, 'view']);
        Gate::define('staff.store', [StaffPolicy::class, 'store']);
        Gate::define('staff.update', [StaffPolicy::class, 'update']);
        Gate::define('staff.delete', [StaffPolicy::class, 'delete']);
    }
}
