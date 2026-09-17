<?php

namespace Expert\Manager\HallService;

use Expert\Manager\HallService\Application\Policies\HallServicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class HallServiceServiceProvider extends ServiceProvider
{
    /**
     * Gate abilities are registered explicitly with a module-prefixed name (not via
     * Gate::policy(), which binds a single class to the whole Hall model) so this
     * module's authorization stays self-contained and can't collide with another
     * module's abilities for the same Hall/HallService models.
     */
    public function boot(): void
    {
        Gate::define('hallService.view', [HallServicePolicy::class, 'view']);
        Gate::define('hallService.store', [HallServicePolicy::class, 'store']);
        Gate::define('hallService.show', [HallServicePolicy::class, 'show']);
        Gate::define('hallService.update', [HallServicePolicy::class, 'update']);
        Gate::define('hallService.delete', [HallServicePolicy::class, 'delete']);
    }
}
