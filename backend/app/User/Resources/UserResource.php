<?php

namespace App\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'avatar' => $this->resource->avatar,
            'phone_number' => $this->resource->phone_number,
            'kyc_status' => $this->resource->kyc_status,
        ];
    }
}
