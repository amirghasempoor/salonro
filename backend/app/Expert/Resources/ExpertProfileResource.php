<?php

namespace App\Expert\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertProfileResource extends JsonResource
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
            'role' => $this->resource->getRoleNames()->first(),
            'halls' => HallResource::collection($this->resource->halls),
            'is_verified' => $this->resource->is_verified,
            'bio' => $this->resource->bio,
            'is_active' => $this->resource->is_active,
        ];
    }
}
