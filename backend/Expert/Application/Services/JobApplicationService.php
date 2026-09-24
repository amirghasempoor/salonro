<?php

namespace Expert\Application\Services;

use App\Facades\DataTable\DataTableFacade;
use App\Models\Expert;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class JobApplicationService
{
    public function index(Request $request): array
    {
        return DataTableFacade::run(
            JobOffer::query()->where('is_active', true),
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'hall_id', 'hall_name', 'profession_id', 'profession_name', 'description',
                'created_at', 'province_id', 'province_name', 'city_id', 'city_name',
            ],
        );
    }

    /**
     * @return bool false when the expert has already applied to this job offer
     */
    public function apply(JobOffer $jobOffer, Expert $expert): bool
    {
        $alreadyApplied = JobOfferApplication::query()
            ->where('job_offer_id', $jobOffer->id)
            ->where('expert_id', $expert->id)
            ->exists();

        if ($alreadyApplied) {
            return false;
        }

        $jobOffer->applications()->create([
            'expert_id' => $expert->id,
        ]);

        return true;
    }

    public function myApplications(Expert $expert): Collection
    {
        return $expert->jobApplications()->with('jobOffer')->get();
    }
}
