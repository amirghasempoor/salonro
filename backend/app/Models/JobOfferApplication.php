<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOfferApplication extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const int STATUS_PENDING = 0;

    const int STATUS_ACCEPTED = 1;

    const int STATUS_REJECTED = 2;

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }
}
