<?php

namespace App\Models\Marketplace;

use App\Enums\PostStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['user_id',
    'vegetable_id',
    'type',
    'status',
    'expected_harvest_month',
    'scheduled_date',
    'time_slot',
    'estimated_total_weight',
])]
class Post extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'time_slot' => PostTimeSlot::class,
            'scheduled_date' => 'date:F j, Y',
            'estimated_total_weight' => 'decimal:2',
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

    public function postItems(): HasMany
    {
        return $this->hasMany(PostItem::class);
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

    public function scopeGrowing(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Growing);
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Ready);
    }

    /* ---------- lifecycle ---------- */

    public function markAsReady(string $scheduledDate): void
    {
        $this->status = PostStatus::Ready;
        $this->scheduled_date = $scheduledDate;
        $this->save();
    }

    public function markAsUnsettled(): void
    {
        $this->postItems()->each(fn (PostItem $item) => $item->markAsUnsettled());
    }

    /* ---------- helpers ---------- */

    public function isGrowing(): bool
    {
        return $this->status === PostStatus::Growing;
    }

    public function createdAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (Post $post): void {
            if (! $post->isForceDeleting()) {
                return;
            }

            $post->postItems()->each(fn (PostItem $item) => $item->delete());
        });
    }

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
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
