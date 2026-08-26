<?php

namespace App\Expert\Resources;

use App\Models\JobOfferApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobOfferApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'status' => $this->resource->status,
            'status_label' => match ($this->resource->status) {
                JobOfferApplication::STATUS_PENDING => 'pending',
                JobOfferApplication::STATUS_ACCEPTED => 'accepted',
                JobOfferApplication::STATUS_REJECTED => 'rejected',
            },
            'created_at' => $this->resource->created_at,
            'expert' => [
                'id' => $this->resource->expert->id,
                'first_name' => $this->resource->expert->first_name,
                'last_name' => $this->resource->expert->last_name,
                'phone_number' => $this->resource->expert->phone_number,
                'avatar' => $this->resource->expert->avatar,
                'portfolio' => $this->resource->expert->images,
            ],
        ];
    }
}
