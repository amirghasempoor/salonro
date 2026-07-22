<?php

namespace App\Models;

use Database\Factories\ExpertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expert extends Authenticatable
{
    /** @use HasFactory<ExpertFactory> */
    use HasFactory, HasRoles;

    protected $guarded = ['id'];

    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class);
    }

    public function images(): MorphToMany
    {
        return $this->morphToMany(Image::class, 'imageable');
    }

    public function workingHours(): MorphToMany
    {
        return $this->morphToMany(WorkingHour::class, 'hourable');
    }
}
