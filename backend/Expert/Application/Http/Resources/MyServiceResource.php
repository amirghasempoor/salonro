<?php

namespace Expert\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyServiceResource extends JsonResource
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
            'price' => $this->price,
            'duration' => $this->duration,
            'service' => [
                'id' => $this->service->id,
                'sub_cat_name' => $this->service->sub_cat_name,
            ],
        ];
    }
}
