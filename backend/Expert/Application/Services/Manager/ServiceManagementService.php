<?php

namespace Expert\Application\Services\Manager;

use App\Facades\DataTable\DataTableFacade;
use App\Facades\File\File;
use App\Models\Service;
use Expert\Application\Http\Requests\Manager\ServiceCategory\StoreRequest;
use Expert\Application\Http\Requests\Manager\ServiceCategory\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ServiceManagementService
{
    public function index(Request $request): array
    {
        return DataTableFacade::run(
            Service::query(),
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'cat_id', 'cat_name', 'sub_cat_id', 'sub_cat_name', 'icon',
            ]
        );
    }

    /**
     * Every service grouped under its category.
     */
    public function list(): Collection
    {
        return Service::query()
            ->orderBy('cat_id')
            ->orderBy('sub_cat_id')
            ->get()
            ->groupBy('cat_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'cat_id' => $first->cat_id,
                    'title' => $first->cat_name,
                    'icon' => $first->icon,
                    'templates' => $group->map(fn ($row) => [
                        'id' => $row->id,
                        'sub_cat_id' => $row->sub_cat_id,
                        'name' => $row->sub_cat_name,
                    ])->values(),
                ];
            })
            ->values();
    }

    public function store(StoreRequest $request): void
    {
        Service::query()->create([
            'cat_id' => $request->cat_id,
            'cat_name' => $request->cat_name,
            'sub_cat_id' => $request->sub_cat_id,
            'sub_cat_name' => $request->sub_cat_name,
            'icon' => File::save($request->icon, '/categories'),
        ]);
    }

    public function update(UpdateRequest $request, Service $category): void
    {
        $category->update([
            'cat_id' => $request->cat_id,
            'cat_name' => $request->cat_name,
            'sub_cat_id' => $request->sub_cat_id,
            'sub_cat_name' => $request->sub_cat_name,
            'icon' => File::save($request->icon, '/categories'),
        ]);
    }

    public function destroy(Service $category): void
    {
        $category->delete();
    }
}
