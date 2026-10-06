<?php

namespace User;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use User\Application\Persistence\EloquentHallRepository;
use User\Application\Persistence\Reservation\EloquentReservationRepository;
use User\Application\Policies\ReservationPolicy;
use User\Domain\Repositories\HallRepositoryInterface;
use User\Domain\Repositories\Reservation\ReservationRepositoryInterface;

class UserServiceProvider extends ServiceProvider
{
    public array $bindings = [
        HallRepositoryInterface::class => EloquentHallRepository::class,
        ReservationRepositoryInterface::class => EloquentReservationRepository::class,
    ];

    public function boot(): void
    {
        // Prefixed distinctly from the Expert module's own 'reservation.*'
        // abilities, which govern the same Reservation model for a different actor.
        Gate::define('userReservation.show', [ReservationPolicy::class, 'owns']);
        Gate::define('userReservation.update', [ReservationPolicy::class, 'owns']);
        Gate::define('userReservation.delete', [ReservationPolicy::class, 'owns']);
    }
}
