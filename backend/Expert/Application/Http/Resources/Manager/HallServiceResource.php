<?php

namespace Expert\Application\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HallServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'description' => $this->description,
            'duration' => $this->duration,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'service' => [
                'id' => $this->service->id,
                'sub_cat_name' => $this->service->sub_cat_name,
            ],
        ];
    }
}
