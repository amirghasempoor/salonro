<?php

namespace App\Expert\Resources;

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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'avatar' => $this->avatar,
            'phone_number' => $this->phone_number,
            'role' => $this->getRoleNames()->first(),
            'halls' => HallResource::collection($this->halls)
        ];
    }
}
