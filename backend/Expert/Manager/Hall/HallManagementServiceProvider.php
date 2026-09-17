<?php

namespace Expert\Manager\Hall;

use Expert\Manager\Hall\Application\Policies\HallPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class HallManagementServiceProvider extends ServiceProvider
{
    /**
     * Gate abilities are registered explicitly with a module-prefixed name (not via
     * Gate::policy(), which binds a single class to the whole Hall model) so this
     * module's authorization stays self-contained and can't collide with another
     * module's abilities for the same Hall model.
     */
    public function boot(): void
    {
        Gate::define('hall.view', [HallPolicy::class, 'view']);
        Gate::define('hall.update', [HallPolicy::class, 'update']);
        Gate::define('hall.delete', [HallPolicy::class, 'delete']);
    }
}
