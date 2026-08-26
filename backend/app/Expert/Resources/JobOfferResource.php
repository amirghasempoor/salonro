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
            'profession_name' => $this->profession_name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'hall_id' => $this->hall_id,
            'hall_name' => $this->hall_name,
        ];
    }
}
