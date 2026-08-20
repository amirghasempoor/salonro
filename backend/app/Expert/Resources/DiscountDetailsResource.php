<?php

namespace App\Expert\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountDetailsResource extends JsonResource
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
            'user_id' => $this->resource->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->resource->user?->first_name.' '.$this->resource->user?->last_name),
            'amount_type' => $this->resource->amount_type,
            'amount' => $this->resource->amount,
            'starts_at' => $this->resource->starts_at,
            'ends_at' => $this->resource->ends_at,
            'usage_limit' => $this->resource->usage_limit,
            'used_count' => $this->resource->used_count,
            'is_active' => $this->resource->is_active,
        ];
    }
}
