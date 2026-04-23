<?php

namespace App\Models\Product;

use App\Models\Interaction\VarietyHeart;
use App\Models\Marketplace\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Variety extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'vegetable_id',
        'name',
        'hearts_count',
    ];

    protected $with = ['vegetable.category'];

    /* ---------- relations ---------- */

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
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

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variety_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useFallbackUrl(asset('images/placeholder.jpg'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Replaces the manual WebP encode + scale logic in ImageUploadService
        $this->addMediaConversion('medium')
            ->width(800)
            ->height(800)
            ->quality(85)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();
    }
}
