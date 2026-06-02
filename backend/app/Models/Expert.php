<?php

namespace App\Models;

use Database\Factories\ExpertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expert extends Model
{
    /** @use HasFactory<ExpertFactory> */
    use HasFactory, HasRoles;

    protected $guarded = ['id'];

    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class);
    }
}
