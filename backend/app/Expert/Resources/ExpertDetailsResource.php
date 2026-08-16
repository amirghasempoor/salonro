<?php

namespace App\Expert\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertDetailsResource extends JsonResource
{
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
            'working_hours' => $this->resource->workingHoursAtHall($request->query('hall_id')),
            'portfolio' => $this->resource->portfolio,
        ];
    }
}
