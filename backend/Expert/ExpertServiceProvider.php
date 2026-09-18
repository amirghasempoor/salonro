<?php

namespace Expert;

use Expert\Application\Persistence\EloquentExpertHallRepository;
use Expert\Application\Persistence\Manager\EloquentJobOfferApplicationRepository;
use Expert\Application\Policies\Manager\HallPolicy;
use Expert\Application\Policies\Manager\HallServicePolicy;
use Expert\Application\Policies\Manager\JobOfferPolicy;
use Expert\Application\Policies\Manager\StaffPolicy;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;
use Expert\Domain\Repositories\Manager\JobOfferApplicationRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ExpertServiceProvider extends ServiceProvider
{
    /**
     * Interface-to-implementation bindings resolved by the container.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ExpertHallRepositoryInterface::class => EloquentExpertHallRepository::class,
        JobOfferApplicationRepositoryInterface::class => EloquentJobOfferApplicationRepository::class,
    ];

    /**
     * Gate abilities are registered explicitly by name (not via Gate::policy(), which
     * binds a single class to a whole model) so each capability's policy can own a
     * distinct, prefixed slice of abilities without colliding with another
     * capability's abilities for the same underlying model.
     */
    public function boot(): void
    {
        Gate::define('hall.view', [HallPolicy::class, 'view']);
        Gate::define('hall.update', [HallPolicy::class, 'update']);
        Gate::define('hall.delete', [HallPolicy::class, 'delete']);

        Gate::define('hallService.view', [HallServicePolicy::class, 'view']);
        Gate::define('hallService.store', [HallServicePolicy::class, 'store']);
        Gate::define('hallService.show', [HallServicePolicy::class, 'show']);
        Gate::define('hallService.update', [HallServicePolicy::class, 'update']);
        Gate::define('hallService.delete', [HallServicePolicy::class, 'delete']);

        Gate::define('staff.view', [StaffPolicy::class, 'view']);
        Gate::define('staff.store', [StaffPolicy::class, 'store']);
        Gate::define('staff.update', [StaffPolicy::class, 'update']);
        Gate::define('staff.delete', [StaffPolicy::class, 'delete']);

        Gate::define('jobOffer.view', [JobOfferPolicy::class, 'forHall']);
        Gate::define('jobOffer.store', [JobOfferPolicy::class, 'forHall']);
        Gate::define('jobOffer.show', [JobOfferPolicy::class, 'forJobOffer']);
        Gate::define('jobOffer.update', [JobOfferPolicy::class, 'forJobOffer']);
        Gate::define('jobOffer.delete', [JobOfferPolicy::class, 'forJobOffer']);
        Gate::define('jobOffer.applications', [JobOfferPolicy::class, 'forJobOffer']);
        Gate::define('jobOffer.acceptApplication', [JobOfferPolicy::class, 'forApplication']);
        Gate::define('jobOffer.rejectApplication', [JobOfferPolicy::class, 'forApplication']);
    }
}
