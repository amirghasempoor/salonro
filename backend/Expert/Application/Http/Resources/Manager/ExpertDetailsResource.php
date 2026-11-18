<?php

namespace Expert\Application\Http\Resources\Manager;

use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertDetailsResource extends JsonResource
{
    public function __construct($resource, private readonly Hall $hall)
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
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'avatar' => $this->resource->avatar,
            'phone_number' => $this->resource->phone_number,
            'working_hours' => $this->resource->workingHoursAtHall($this->hall->id)->get(['day', 'from', 'to']),
            'portfolio' => $this->resource->images,
        ];
    }
}
