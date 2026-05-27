<?php

namespace App\Models\Product;

use App\Models\Interaction\VarietyHeart;
use App\Models\Marketplace\PostItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Variety extends Model
{
    use HasFactory;

    protected $fillable = [
        'vegetable_id',
        'name',
        'hearts_count',
    ];

    protected $with = ['vegetable.category', 'vegetable.media'];

    /* ---------- relations ---------- */

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }

    public function postItems(): HasMany
    {
        return $this->hasMany(PostItem::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(PriceHistory::class)->latest('recorded_at');
    }

    public function recentPrices(): HasMany
    {
        return $this->hasMany(PriceHistory::class)
            ->latest('recorded_at')
            ->limit(12);
    }

    public function lastTwoPrices(): HasMany
    {
        return $this->hasMany(PriceHistory::class)
            ->latest('recorded_at')
            ->limit(2);
    }

    public function hearts(): HasMany
    {
        return $this->hasMany(VarietyHeart::class);
    }

    /* ---------- scopes ---------- */

    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
    }
}
