<?php

namespace Expert\Application\Services\Manager;

use App\Facades\DataTable\DataTableFacade;
use App\Models\Hall;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use App\Models\Profession;
use Expert\Application\Http\Requests\Manager\JobOffer\StoreRequest;
use Expert\Application\Http\Requests\Manager\JobOffer\UpdateRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class JobOfferManagementService
{
    public function index(Request $request, Hall $hall): array
    {
        return DataTableFacade::run(
            $hall->jobOffers(),
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: ['id', 'hall_id', 'hall_name', 'profession_id', 'description', 'is_active', 'created_at'],
        );
    }

    public function store(StoreRequest $request, Hall $hall): void
    {
        $profession = Profession::query()->find($request->profession_id);

        JobOffer::query()->create([
            'hall_id' => $hall->id,
            'hall_name' => $hall->name,
            'expert_id' => $hall->owner_id,
            'profession_id' => $profession->id,
            'profession_name' => $profession->name,
            'description' => $request->description,
            'province_id' => $hall->province_id,
            'province_name' => $hall->province_name,
            'city_id' => $hall->city_id,
            'city_name' => $hall->city_name,
        ]);
    }

    public function update(UpdateRequest $request, JobOffer $jobOffer): void
    {
        $jobOffer->update($request->validated());
    }

    public function destroy(JobOffer $jobOffer): void
    {
        $jobOffer->delete();
    }

    public function applications(JobOffer $jobOffer): Collection
    {
        return $jobOffer->applications()->with('expert')->get();
    }

    public function rejectApplication(JobOfferApplication $application): void
    {
        $application->update(['status' => JobOfferApplication::STATUS_REJECTED]);
    }
}
