<?php

namespace App\Expert\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profession_id' => $this->profession_id,
            'profession' => $this->whenLoaded('profession', fn () => [
                'id' => $this->profession->id,
                'name' => $this->profession->name,
            ]),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'hall' => [
                'id' => $this->hall->id,
                'name' => $this->hall->name,
            ],
            'applications_count' => $this->whenCounted('applications'),
            'applications' => JobOfferApplicationResource::collection($this->whenLoaded('applications')),
        ];
    }
}
