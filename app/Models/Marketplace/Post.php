<?php

namespace App\Models\Marketplace;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Interaction\PostHeart;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'variety_id',
        'type',
        'status',
        'quantity_kg',
        'offered_price',
        'price_flag',
        'scheduled_date',
        'time_slot',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'int',
            'offered_price' => 'decimal:2',
            'price_flag' => PostPriceFlag::class,
            'type' => PostType::class,
            'status' => PostStatus::class,
            'scheduled_date' => 'date',
            'time_slot' => PostTimeSlot::class,
        ];
    }

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }

    public function hearts(): HasMany
    {
        return $this->hasMany(PostHeart::class);
    }

    public function farmerProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            FarmerProfile::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id',
        );
    }

    public function dealerProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            DealerProfile::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id',
        );
    }

    /* ---------- scopes ---------- */

    public function scopeSupply(Builder $query): Builder
    {
        return $query->where('type', PostType::Supply);
    }

    public function scopeDemand(Builder $query): Builder
    {
        return $query->where('type', PostType::Demand);
    }

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Ongoing);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Archived);
    }

    public function scopeFulfilled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Fulfilled);
    }

    public function scopeOfType(Builder $query, PostType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus(Builder $query, PostStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /* ---------- actions ---------- */

    public function markAsArchived(): void
    {
        $this->status = PostStatus::Archived;
        $this->save();
    }

    public function markAsFulfilled(): void
    {
        $this->status = PostStatus::Fulfilled;
        $this->save();
    }

    /* ---------- accessors ---------- */

    public function daysUntilArchive(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== PostStatus::Ongoing) {
                    return null;
                }
            }
        );
    }

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
        // Only supply posts carry an image; demands do not.
        // The collection is registered on Post for both types but only populated
        // by CreateSupplyAction / UpdateSupplyAction.
        $this->addMediaCollection('post_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(400)
            ->nonQueued();
    }
}
