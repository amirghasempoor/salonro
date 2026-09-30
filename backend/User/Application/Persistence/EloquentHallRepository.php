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
        $hall = HallModel::query()->findOrFail($hallId);

        $services = $hall->services()->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'cat_id' => $service->cat_id,
                'cat_name' => $service->cat_name,
                'sub_cat_id' => $service->sub_cat_id,
                'sub_cat_name' => $service->sub_cat_name,
                'icon' => $service->icon,
                'price' => $service->pivot->price,
                'duration' => $service->pivot->duration,
            ])
            ->all();

        $staff = ExpertHall::query()
            ->where('hall_id', '=', $hallId)
            ->with(['expert:id,first_name,last_name,avatar', 'services:services.id'])
            ->get()
            ->map(fn (ExpertHall $expertHall) => [
                'id' => $expertHall->expert->id,
                'first_name' => $expertHall->expert->first_name,
                'last_name' => $expertHall->expert->last_name,
                'avatar' => $expertHall->expert->avatar,
                'service_ids' => $expertHall->services->pluck('id')->all(),
            ])
            ->all();

        return new Hall($hall->id, $hall->name, $services, $staff);
    }
}
