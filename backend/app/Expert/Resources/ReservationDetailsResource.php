<?php

namespace App\Expert\Resources;

use App\Enums\Roles;
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
            'expert_name' => $this->resource->expert_name,
            'user_name' => $this->resource->user_name,
            'total_price' => $this->resource->total_price,
            'start_time' => $this->resource->start_time,
            'finish_time' => $this->resource->finish_time,
            'hall_name' => $this->resource->hall_name,
        ];
    }
}
