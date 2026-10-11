<?php

namespace User\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationDetailsResource extends JsonResource
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
            'expert_name' => $this->resource->expert_name,
            'hall_name' => $this->resource->hall_name,
            'state_name' => $this->resource->state_name,
            'start_time' => $this->resource->start_time,
            'finish_time' => $this->resource->finish_time,
            'total_price' => $this->resource->total_price,
            'discount_amount' => $this->resource->discount_amount,
            'services' => $this->resource->services,
        ];
    }
}
