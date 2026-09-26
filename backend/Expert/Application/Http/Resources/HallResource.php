<?php

namespace Expert\Application\Http\Resources;

use App\Models\Expert;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class HallResource extends JsonResource
{
    public function __construct($resource, private readonly ?Expert $expert = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'working_hours' => $this->expert
                ?->workingHoursAtHall($this->id)
                ->get(['day', 'from', 'to']),
            'services' => $this->expertServices(),
        ];
    }

    /**
     * The services this expert provides at this hall.
     */
    private function expertServices(): ?Collection
    {
        return $this->expert
            ?->expertHalls()
            ->where('hall_id', $this->id)
            ->first()
            ?->services()
            ->get(['services.id', 'services.sub_cat_name'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->sub_cat_name,
            ]);
    }
}
