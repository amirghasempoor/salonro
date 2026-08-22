<?php

namespace App\Models;

use Database\Factories\ExpertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Expert extends Authenticatable
{
    /** @use HasFactory<ExpertFactory> */
    use HasApiTokens, HasFactory, HasRoles;

    protected $guarded = ['id'];

    protected $hidden = ['pivot', 'password'];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class);
    }

    public function expertHalls(): HasMany
    {
        return $this->hasMany(ExpertHall::class);
    }

    public function workingHoursAtHall(int $hallId): Builder
    {
        return WorkingHour::query()
            ->where('hourable_type', ExpertHall::class)
            ->whereIn('hourable_id', $this->expertHalls()->where('hall_id', $hallId)->select('id'));
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function services(): MorphMany
    {
        return $this->morphMany(ExpertService::class, 'serviceable');
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobOfferApplication::class);
    }

    protected function workingHours(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expertHalls()
                ->with('workingHours')
                ->get()
                ->map(fn (ExpertHall $expertHall) => [
                    'hall_id' => $expertHall->hall_id,
                    'working_hours' => $expertHall->workingHours,
                ]),
        );
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value == null ? null : Storage::disk('public')->url($value)
        );
    }

    public function ownedHalls(): HasMany
    {
        return $this->hasMany(Hall::class, 'owner_id');
    }
}
