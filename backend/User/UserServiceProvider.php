<?php

namespace User;

use Illuminate\Support\ServiceProvider;
use User\Application\Persistence\EloquentHallRepository;
use User\Domain\Repositories\HallRepositoryInterface;

class UserServiceProvider extends ServiceProvider
{
    public array $bindings = [
        HallRepositoryInterface::class => EloquentHallRepository::class,
    ];
}
