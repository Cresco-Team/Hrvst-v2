<?php

namespace App\Models\Product;

use App\Models\Marketplace\PostItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['category_id', 'vegetable_name', 'variety_name', 'local_name'])]
class Vegetable extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $with = ['media'];

    /* ---------- relations ---------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function postItems(): HasMany
    {
        return $this->hasMany(PostItem::class);
    }

    /* ---------- scopes ---------- */

    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, fn (Builder $q) => $q->where(
            fn (Builder $inner) => $inner
                ->where('vegetable_name', 'ilike', "%{$search}%")
                ->orWhere('variety_name', 'ilike', "%{$search}%")
                ->orWhere('local_name', 'ilike', "%{$search}%")
        ));
    }

    /* ---------- accessors ---------- */

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variety_name
                ? "{$this->vegetable_name}: {$this->variety_name}"
                : $this->vegetable_name,
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('vegetable_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useFallbackUrl(asset('images/placeholder.jpg'));
    }

    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('vegetable_image')
        );
    }

    public function registerMediaConversions(?Media $media = null): void
    {
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
