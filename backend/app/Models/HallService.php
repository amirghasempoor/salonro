<?php

namespace App\Models;

use Database\Factories\HallServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HallService extends Model
{
    /** @use HasFactory<HallServiceFactory> */
    use HasFactory;

    protected $table = 'hall_service';

    protected $guarded = ['id'];

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
