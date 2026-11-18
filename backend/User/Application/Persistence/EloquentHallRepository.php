<?php

namespace User\Application\Persistence;

use App\Models\ExpertHall;
use App\Models\Hall as HallModel;
use App\Models\Service;
use User\Domain\Entities\Hall;
use User\Domain\Repositories\HallRepositoryInterface;

class EloquentHallRepository implements HallRepositoryInterface
{
    public function find(int $hallId): Hall
    {
        // A deactivated hall is gone for customers: no catalog, staff or booking.
        $hall = HallModel::query()->where('is_active', true)->findOrFail($hallId);

        // The catalog is only what the hall currently offers: a service the manager
        // switched off or removed is not bookable, whatever an expert still has priced.
        $services = $hall->services()->wherePivot('is_active', true)->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'cat_id' => $service->cat_id,
                'cat_name' => $service->cat_name,
                'sub_cat_id' => $service->sub_cat_id,
                'sub_cat_name' => $service->sub_cat_name,
                'icon' => $service->icon,
            ])
            ->all();

        $staff = ExpertHall::query()
            ->where('hall_id', '=', $hallId)
            // Staff the manager switched off, or who switched themselves off, cannot be booked.
            ->where('is_active', true)
            ->whereHas('expert', fn ($query) => $query->where('is_active', true))
            ->with(['expert:id,first_name,last_name,avatar', 'services:services.id,sub_cat_name'])
            ->get()
            ->map(fn (ExpertHall $expertHall) => [
                'id' => $expertHall->expert->id,
                'first_name' => $expertHall->expert->first_name,
                'last_name' => $expertHall->expert->last_name,
                'avatar' => $expertHall->expert->avatar,
                'services' => $expertHall->services->map(fn (Service $service) => [
                    'id' => $service->id,
                    'sub_cat_name' => $service->sub_cat_name,
                    'price' => $service->pivot->price,
                    'duration' => $service->pivot->duration,
                ])->all(),
            ])
            ->all();

        return new Hall($hall->id, $hall->name, $services, $staff);
    }
}
