<?php

namespace App\Models;

use App\Support\Geo;
use Database\Factories\HallFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Hall extends Model
{
    /** @use HasFactory<HallFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['pivot'];

    public function experts(): BelongsToMany
    {
        return $this->belongsToMany(Expert::class);
    }

    public function workingHours(): MorphMany
    {
        return $this->morphMany(WorkingHour::class, 'hourable');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('description', 'price', 'duration');
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Expert::class, 'owner_id');
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $box = Geo::boundingBox($lat, $lng, $radiusKm);
        [$distanceSql, $bindings] = Geo::distanceInKmSql('lat', 'lng', $lat, $lng);

        return $query
            ->fromSub(
                static::query()
                    ->where('is_active', true)
                    ->whereBetween('lat', [$box['latMin'], $box['latMax']])
                    ->whereBetween('lng', [$box['lngMin'], $box['lngMax']])
                    ->selectRaw("halls.*, $distanceSql as distance", $bindings),
                $this->getTable(),
            )
            ->where('distance', '<=', $radiusKm);
    }
}
