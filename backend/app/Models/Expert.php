<?php

namespace App\Models;

use Database\Factories\ExpertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Expert extends Authenticatable
{
    /** @use HasFactory<ExpertFactory> */
    use HasApiTokens, HasFactory, HasRoles;

    protected $guarded = ['id'];

    protected $hidden = ['pivot'];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

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
