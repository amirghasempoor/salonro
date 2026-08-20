<?php

namespace App\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'hall_id' => $this->resource->hall_id,
            'type' => $this->resource->type,
            'title' => $this->resource->title,
            'amount_type' => $this->resource->amount_type,
            'amount' => $this->resource->amount,
            'starts_at' => $this->resource->starts_at,
            'ends_at' => $this->resource->ends_at,
        ];
    }
}
