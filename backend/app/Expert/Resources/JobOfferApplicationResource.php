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
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                JobOfferApplication::STATUS_PENDING => 'pending',
                JobOfferApplication::STATUS_ACCEPTED => 'accepted',
                JobOfferApplication::STATUS_REJECTED => 'rejected',
            },
            'created_at' => $this->created_at,
            'expert' => [
                'id' => $this->expert->id,
                'first_name' => $this->expert->first_name,
                'last_name' => $this->expert->last_name,
                'phone_number' => $this->expert->phone_number,
                'avatar' => $this->expert->avatar,
                'portfolio' => $this->expert->images,
            ],
        ];
    }
}
