<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExpertService extends Model
{
    use HasFactory;

    protected $table = 'expert_service';

    protected $guarded = ['id'];

    protected $hidden = ['pivot', 'serviceable_type', 'serviceable_id'];

    public function serviceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
