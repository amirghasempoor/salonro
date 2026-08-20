<?php

namespace App\Models;

use App\Enums\DiscountAmountType;
use App\Enums\DiscountType;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'amount_type' => DiscountAmountType::class,
            'amount' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Expert::class, 'created_by');
    }

    /**
     * @param  Builder<Discount>  $query
     * @return Builder<Discount>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasRemainingUses(): bool
    {
        return $this->usage_limit === null || $this->used_count < $this->usage_limit;
    }
}
