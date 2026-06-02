<?php

namespace App\Models;

use Database\Factories\HallFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hall extends Model
{
    /** @use HasFactory<HallFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function experts(): BelongsToMany
    {
        return $this->belongsToMany(Expert::class);
    }
}
