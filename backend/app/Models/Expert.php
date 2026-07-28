<?php

namespace App\Models;

use Database\Factories\ExpertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expert extends Authenticatable
{
    /** @use HasFactory<ExpertFactory> */
    use HasFactory, HasRoles, HasApiTokens;

    protected $guarded = ['id'];

    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function workingHours(): MorphMany
    {
        return $this->morphMany(WorkingHour::class, 'hourable');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }
}
