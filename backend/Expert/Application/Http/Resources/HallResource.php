<?php

namespace Expert\Application\Http\Resources;

use App\Models\Expert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HallResource extends JsonResource
{
    public function __construct($resource, private readonly ?Expert $expert = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'working_hours' => $this->expert
                ?->workingHoursAtHall($this->id)
                ->get(['day', 'from', 'to']),
        ];
    }
}
