<?php

namespace App\Expert\Resources;

use App\Enums\Roles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertResource extends JsonResource
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
            'halls' => $this->when($this->resource->getRoleNames()->first() == Roles::Manager->value,
                $this->resource->halls->pluck('id')->values()
            ),
            'is_verified' => $this->resource->is_verified
        ];
    }
}
