<?php

namespace App\Models\Product;

use App\Models\Marketplace\Post;
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

#[Fillable(['category_id', 'name'])]
class Vegetable extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $with = ['media'];

    /* ---------- relations ---------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function varieties(): HasMany
    {
        return $this->hasMany(Variety::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /* ---------- scopes ---------- */

    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, fn (Builder $q) => $q->where('name', 'ilike', "%{$search}%"));
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
