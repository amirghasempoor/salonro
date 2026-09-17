<?php

namespace Expert\Profile;

use Expert\Profile\Application\Persistence\EloquentExpertHallRepository;
use Expert\Profile\Domain\Repositories\ExpertHallRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ProfileServiceProvider extends ServiceProvider
{
    /**
     * Interface-to-implementation bindings resolved by the container.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ExpertHallRepositoryInterface::class => EloquentExpertHallRepository::class,
    ];
}
