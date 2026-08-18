<?php

namespace App\Models;

use Database\Factories\ExpertHallFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpertHall extends Model
{
    /** @use HasFactory<ExpertHallFactory> */
    use HasFactory;

    protected $table = 'expert_hall';

    protected $guarded = ['id'];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function workingHours(): MorphMany
    {
        return $this->morphMany(WorkingHour::class, 'hourable');
    }

    public function services(): MorphMany
    {
        return $this->morphMany(ExpertService::class, 'serviceable');
    }
}
